<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Actions;

use App\Enums\CalculationStatus;
use App\Enums\PeriodType;
use App\Models\Compulsa\Calculation;
use App\Models\Compulsa\CalculationRecord;
use App\Models\Compulsa\GlobalSetting;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Motor de conciliación (Fase 4).
 *
 * Fórmula IMSS:
 *   Mensual (EMA): Cuota Fija (sobre UMA×días) + Excedente (SDI > 3 UMA) + Prest. Dinero
 *                 + Gastos Médicos y Pensionados + Invalidez y Vida + Riesgos de Trabajo
 *                 (prima_riesgo por WorkCenter) + Guarderías. Cada rama con tasa patronal
 *                 y obrera cargadas en GlobalSetting.
 *   Bimestral (EBA): Retiro + Cesantía y Vejez (patronal + obrera) + Infonavit.
 *
 * Los rates se toman del snapshot en Calculation.meta.ema_rates/eba_rates (guardado al crear
 * la compulsa). Si el snapshot no existe se hace fallback a GlobalSetting::forYear() vigente.
 *
 * Semáforo (estatus_conciliacion): faltante > falta_sua > supera_tope > diferencia_dias
 *                                  > diferencia_sdi > diferencia_cuota > ok
 */
class ReconcileCalculationAction
{
    public const EPSILON_MONEY = 0.01;

    /**
     * @return array<string, float|int>
     */
    public function execute(Calculation $calculation): array
    {
        $primaRiesgo = (float) ($calculation->prima_riesgo_aplicada ?? 0);
        $valorUma = (float) ($calculation->valor_uma_aplicado ?? 0);
        $topeUmaVeces = (float) ($calculation->tope_uma_veces_aplicado ?? 25);
        $topeSdi = $valorUma > 0 ? $valorUma * $topeUmaVeces : PHP_FLOAT_MAX;
        $salarioMinimo = (float) ($calculation->salario_minimo_aplicado ?? 0);

        $tipo = $calculation->tipo_periodo instanceof PeriodType
            ? $calculation->tipo_periodo
            : PeriodType::from((string) $calculation->tipo_periodo);

        [$emaRates, $ebaRates, $tablaCesantia, $exencionSm] = $this->resolveRates($calculation);

        $totales = [
            'total_empleados' => 0,
            'empleados_con_diferencias' => 0,
            'total_cuota_imss' => 0.0,
            'total_cuota_patron' => 0.0,
            'total_diferencia' => 0.0,
        ];

        try {
            $calculation->records()
                ->orderBy('id')
                ->chunkById(500, function ($chunk) use (
                    $tipo,
                    $emaRates,
                    $ebaRates,
                    $tablaCesantia,
                    $exencionSm,
                    $topeSdi,
                    $salarioMinimo,
                    $valorUma,
                    $primaRiesgo,
                    &$totales
                ) {
                    foreach ($chunk as $record) {
                        $result = $this->reconcileRecord(
                            $record,
                            $tipo,
                            $emaRates,
                            $ebaRates,
                            $tablaCesantia,
                            $exencionSm,
                            $topeSdi,
                            $salarioMinimo,
                            $valorUma,
                            $primaRiesgo,
                        );


                        $totales['total_empleados']++;
                        if ($result['tiene_diferencia']) {
                            $totales['empleados_con_diferencias']++;
                        }
                        $totales['total_cuota_imss'] += $result['cuota_imss'];
                        $totales['total_cuota_patron'] += $result['cuota_patron'];
                        $totales['total_diferencia'] += abs($result['diferencia_total']);
                    }
                });

            $calculation->update([
                'total_empleados' => $totales['total_empleados'],
                'empleados_con_diferencias' => $totales['empleados_con_diferencias'],
                'total_cuota_imss' => round($totales['total_cuota_imss'], 2),
                'total_cuota_patron' => round($totales['total_cuota_patron'], 2),
                'total_diferencia' => round($totales['total_diferencia'], 2),
                'estado' => CalculationStatus::Completed->value,
                'procesado_en' => now(),
                'meta' => array_merge($calculation->meta ?? [], [
                    'reconciled_at' => now()->toIso8601String(),
                    'tope_sdi' => $topeSdi === PHP_FLOAT_MAX ? null : $topeSdi,
                    'salario_minimo' => $salarioMinimo,
                ]),
            ]);

            return $totales;
        } catch (Throwable $e) {
            Log::error('Compulsa reconcile failed', [
                'calculation_id' => $calculation->id,
                'error' => $e->getMessage(),
            ]);

            $calculation->update([
                'estado' => CalculationStatus::Failed->value,
                'meta' => array_merge($calculation->meta ?? [], [
                    'reconcile_error' => $e->getMessage(),
                    'reconcile_failed_at' => now()->toIso8601String(),
                ]),
            ]);

            throw $e;
        }
    }

    /**
     * @return array{0: array<string, float>, 1: array<string, float>, 2: array, 3: bool}
     */
    private function resolveRates(Calculation $calculation): array
    {
        $meta = $calculation->meta ?? [];
        $ema = $meta['ema_rates'] ?? null;
        $eba = $meta['eba_rates'] ?? null;
        $tablaCesantia = $meta['tabla_cesantia_patronal'] ?? null;
        $exencionSm = $meta['exencion_cuota_obrera_sm'] ?? null;

        if ($ema === null || $eba === null || $tablaCesantia === null || $exencionSm === null) {
            $globals = GlobalSetting::forYear((int) $calculation->ejercicio);
            $ema ??= $globals?->emaRates() ?? [];
            $eba ??= $globals?->ebaRates() ?? [];
            $tablaCesantia ??= $globals?->tabla_cesantia_patronal ?? [];
            $exencionSm ??= $globals?->exencion_cuota_obrera_sm ?? true;
        }

        return [
            array_map('floatval', $ema),
            array_map('floatval', $eba),
            is_array($tablaCesantia) ? $tablaCesantia : [],
            (bool) $exencionSm,
        ];
    }

    /**
     * @param  array<string, float>  $emaRates
     * @param  array<string, float>  $ebaRates
     * @return array{cuota_imss: float, cuota_patron: float, diferencia_total: float, tiene_diferencia: bool}
     */
    private function reconcileRecord(
        CalculationRecord $record,
        PeriodType $tipo,
        array $emaRates,
        array $ebaRates,
        array $tablaCesantia,
        bool $exencionSm,
        float $topeSdi,
        float $salarioMinimo,
        float $valorUma,
        float $primaRiesgo,
    ): array {
        // Prioridad para lo "declarado por el patrón": Nómina > SUA > IMSS
        $sdiDeclaradoRaw = $this->firstNonNull($record->sdi_nomina, $record->sdi_sua, $record->sdi_imss);
        $diasDeclarado = $this->firstNonNull($record->dias_nomina, $record->dias_sua, $record->dias_imss);

        $sdiTopado = $this->applyCapAndFloor(
            $sdiDeclaradoRaw !== null ? (float) $sdiDeclaradoRaw : null,
            $topeSdi,
            $salarioMinimo,
        );

        $sdiImssTopado = $this->applyCapAndFloor(
            $record->sdi_imss !== null ? (float) $record->sdi_imss : null,
            $topeSdi,
            $salarioMinimo,
        );

        // 1. Cuota del IMSS: Usar cuotas extraídas directamente del EMA/EBA si existen; de lo contrario fallback a cálculo
        $extractedImssBreak = null;
        if (! empty($record->desglose_cuotas) && is_array($record->desglose_cuotas) && ! empty($record->desglose_cuotas['imss'])) {
            $extractedImssBreak = $record->desglose_cuotas['imss'];
        } elseif (! empty($record->desglose_cuotas) && is_string($record->desglose_cuotas)) {
            $decoded = json_decode($record->desglose_cuotas, true);
            if (! empty($decoded['imss'])) {
                $extractedImssBreak = $decoded['imss'];
            }
        }

        // Extracción de cuotas reportadas en el archivo SUA (si vinieron en la Cédula SUA)
        $extractedSuaBreak = null;
        if (! empty($record->desglose_sua) && is_array($record->desglose_sua)) {
            $extractedSuaBreak = $record->desglose_sua;
        } elseif (! empty($record->desglose_sua) && is_string($record->desglose_sua)) {
            $decodedSua = json_decode($record->desglose_sua, true);
            if (! empty($decodedSua)) {
                $extractedSuaBreak = $decodedSua;
            }
        }

        if ($tipo === PeriodType::Bimestral) {
            // Manejo Bimestral (EBA) con Bloque de Cuotas y Bloque de Crédito
            $imssRamas = $extractedImssBreak['desglose'] ?? [];
            $extractedAmortImss = (float) ($imssRamas['amortizacion'] ?? $record->amortizacion_imss ?? 0.0);
            $numeroCredito = $record->numero_credito ?? ($imssRamas['numero_credito'] ?? null);
            $amortizacionSua = (float) ($record->amortizacion_sua ?? 0.0);

            // Subtotal Cuotas Ordinarias IMSS (Retiro + Cesantía Pat + Cesantía Obr + Infonavit Pat 5%)
            if (! empty($imssRamas)) {
                $subtotalCuotasImss = round(
                    (float) ($imssRamas['retiro'] ?? 0) +
                    (float) ($imssRamas['cesantia_patronal'] ?? 0) +
                    (float) ($imssRamas['cesantia_obrero'] ?? 0) +
                    (float) ($imssRamas['infonavit'] ?? 0),
                    2
                );
                $cuotaImssBreak = [
                    'total' => $subtotalCuotasImss,
                    'patronal' => round((float) ($imssRamas['retiro'] ?? 0) + (float) ($imssRamas['cesantia_patronal'] ?? 0) + (float) ($imssRamas['infonavit'] ?? 0), 2),
                    'obrera' => round((float) ($imssRamas['cesantia_obrero'] ?? 0), 2),
                    'desglose' => [
                        'retiro' => round((float) ($imssRamas['retiro'] ?? 0), 2),
                        'cesantia_patronal' => round((float) ($imssRamas['cesantia_patronal'] ?? 0), 2),
                        'cesantia_obrero' => round((float) ($imssRamas['cesantia_obrero'] ?? 0), 2),
                        'infonavit' => round((float) ($imssRamas['infonavit'] ?? 0), 2),
                    ],
                ];
            } else {
                $cuotaImssBreak = ($sdiImssTopado !== null && $record->dias_imss !== null)
                    ? $this->calculateCuota($tipo, (float) $sdiImssTopado, (int) $record->dias_imss, $emaRates, $ebaRates, $valorUma, $primaRiesgo, $salarioMinimo, $tablaCesantia, $exencionSm)
                    : ['total' => 0.0, 'patronal' => 0.0, 'obrera' => 0.0, 'desglose' => []];
                $subtotalCuotasImss = round($cuotaImssBreak['total'], 2);
            }

            // Subtotal Cuotas Ordinarias Patrón
            $cuotaPatronBreak = ($sdiTopado !== null && $diasDeclarado !== null)
                ? $this->calculateCuota($tipo, (float) $sdiTopado, (int) $diasDeclarado, $emaRates, $ebaRates, $valorUma, $primaRiesgo, $salarioMinimo, $tablaCesantia, $exencionSm)
                : ['total' => 0.0, 'patronal' => 0.0, 'obrera' => 0.0, 'desglose' => []];
            $subtotalCuotasPatron = round($cuotaPatronBreak['total'], 2);

            $diferenciaCuotas = round($subtotalCuotasPatron - $subtotalCuotasImss, 2);

            // Bloque 2: Créditos INFONAVIT
            $hasCredit = ($extractedAmortImss > 0 || $amortizacionSua > 0 || ! empty($numeroCredito));
            $diffAmort = round($amortizacionSua - $extractedAmortImss, 2);
            // Tolerancia de 50 centavos para absorber diferencias de redondeo / seguro de daños
            $coincideCredito = abs($diffAmort) <= 0.50;

            // Absorber diferencia de hasta 2 centavos en cuotas por redondeo aritmético
            if (abs($diferenciaCuotas) <= 0.02) {
                $diferenciaCuotas = 0.0;
                $subtotalCuotasPatron = $subtotalCuotasImss;
            }

            // Total General
            $cuotaImss = round($subtotalCuotasImss + $extractedAmortImss, 2);
            $effectiveAmortPatron = $coincideCredito ? $extractedAmortImss : $amortizacionSua;
            $cuotaPatron = round($subtotalCuotasPatron + $effectiveAmortPatron, 2);
            $diferenciaTotal = round($cuotaPatron - $cuotaImss, 2);
            if (abs($diferenciaTotal) <= 0.02) {
                $cuotaPatron = $cuotaImss;
                $diferenciaTotal = 0.0;
            }

            // Alertas
            [$alertas, $observaciones, $superaTope] = $this->buildAlerts(
                $record,
                $sdiDeclaradoRaw !== null ? (float) $sdiDeclaradoRaw : null,
                $topeSdi,
                $diferenciaCuotas,
            );

            // Alertas de Crédito
            if ($hasCredit) {
                if ($extractedAmortImss > 0 && $amortizacionSua <= 0.0001) {
                    $alertas[] = 'credito_no_aplicado_sua';
                    $alertas[] = 'diferencia_credito';
                    $observaciones[] = sprintf(
                        'Crédito Infonavit %sfacturado por IMSS ($%s) no reflejado en SUA.',
                        $numeroCredito ? "({$numeroCredito}) " : '',
                        number_format($extractedAmortImss, 2),
                    );
                } elseif ($amortizacionSua > 0 && $extractedAmortImss <= 0.0001) {
                    $alertas[] = 'amortizacion_sin_credito_imss';
                    $alertas[] = 'diferencia_credito';
                    $observaciones[] = sprintf(
                        'SUA amortiza $%s de crédito %spero no aparece facturado en emisión EBA.',
                        number_format($amortizacionSua, 2),
                        $numeroCredito ? "({$numeroCredito}) " : '',
                    );
                } elseif (! $coincideCredito) {
                    $alertas[] = 'diferencia_credito';
                    $observaciones[] = sprintf(
                        'Diferencia en amortización de crédito %s: IMSS $%s vs SUA $%s (Dif: $%s).',
                        $numeroCredito ? "({$numeroCredito})" : '',
                        number_format($extractedAmortImss, 2),
                        number_format($amortizacionSua, 2),
                        number_format($diffAmort, 2),
                    );
                }
            }

            $estatus = $this->determineEstatus($alertas);
            $tieneDiferencia = $estatus !== 'ok';

            $record->update([
                'sdi_topado' => $sdiTopado,
                'cuota_imss' => $cuotaImss,
                'cuota_sua' => (float) ($extractedSuaBreak['total'] ?? $record->cuota_sua ?? 0.0),
                'cuota_patron' => $cuotaPatron,
                'amortizacion_imss' => $extractedAmortImss > 0 ? $extractedAmortImss : null,
                'amortizacion_sua' => $amortizacionSua > 0 ? $amortizacionSua : null,
                'numero_credito' => $numeroCredito,
                'diferencia_total' => $diferenciaTotal,
                'supera_tope_uma' => $superaTope,
                'alertas_json' => array_values(array_unique($alertas)),
                'observaciones' => $observaciones !== [] ? implode(' | ', $observaciones) : null,
                'desglose_cuotas' => [
                    'tipo' => $tipo->value,
                    'sdi_declarado' => $sdiDeclaradoRaw !== null ? (float) $sdiDeclaradoRaw : null,
                    'sdi_topado_declarado' => $sdiTopado,
                    'sdi_topado_imss' => $sdiImssTopado,
                    'dias_declarado' => $diasDeclarado,
                    'prima_riesgo' => $primaRiesgo,
                    'valor_uma' => $valorUma,
                    'patron' => array_merge($cuotaPatronBreak, [
                        'cuotas_subtotal' => $subtotalCuotasPatron,
                        'total' => $cuotaPatron,
                    ]),
                    'imss' => array_merge($cuotaImssBreak, [
                        'cuotas_subtotal' => $subtotalCuotasImss,
                        'total' => $cuotaImss,
                    ]),
                    'sua' => $extractedSuaBreak,
                    'credito_infonavit' => $hasCredit ? [
                        'numero_credito' => $numeroCredito,
                        'amortizacion_imss' => $extractedAmortImss,
                        'amortizacion_sua' => $amortizacionSua,
                        'diferencia' => $coincideCredito ? 0.0 : $diffAmort,
                        'coincide' => $coincideCredito,
                    ] : null,
                ],
                'estatus_conciliacion' => $estatus,
            ]);

            return [
                'cuota_imss' => $cuotaImss,
                'cuota_patron' => $cuotaPatron,
                'diferencia_total' => $diferenciaTotal,
                'tiene_diferencia' => $tieneDiferencia,
            ];
        }

        if ($extractedImssBreak !== null && ! empty($extractedImssBreak['desglose'])) {
            $cuotaImssBreak = $extractedImssBreak;
            $cuotaImss = round((float) ($cuotaImssBreak['total'] ?? $record->cuota_imss), 2);
        } else {
            $cuotaImssBreak = ($sdiImssTopado !== null && $record->dias_imss !== null)
                ? $this->calculateCuota($tipo, (float) $sdiImssTopado, (int) $record->dias_imss, $emaRates, $ebaRates, $valorUma, $primaRiesgo, $salarioMinimo, $tablaCesantia, $exencionSm)
                : ['total' => 0.0, 'patronal' => 0.0, 'obrera' => 0.0, 'desglose' => []];
            $cuotaImss = round($cuotaImssBreak['total'], 2);
        }

        // 2. Cuota del Patrón: calculada desde la declaración patronal (Nómina / SUA)
        $cuotaPatronBreak = ($sdiTopado !== null && $diasDeclarado !== null)
            ? $this->calculateCuota($tipo, (float) $sdiTopado, (int) $diasDeclarado, $emaRates, $ebaRates, $valorUma, $primaRiesgo, $salarioMinimo, $tablaCesantia, $exencionSm)
            : ['total' => 0.0, 'patronal' => 0.0, 'obrera' => 0.0, 'desglose' => []];

        $cuotaPatron = round($cuotaPatronBreak['total'], 2);
        $diferencia = round($cuotaPatron - $cuotaImss, 2);
        if (abs($diferencia) <= 0.02) {
            $cuotaPatron = $cuotaImss;
            $diferencia = 0.0;
        }

        [$alertas, $observaciones, $superaTope] = $this->buildAlerts(
            $record,
            $sdiDeclaradoRaw !== null ? (float) $sdiDeclaradoRaw : null,
            $topeSdi,
            $diferencia,
        );

        $estatus = $this->determineEstatus($alertas);
        $tieneDiferencia = $estatus !== 'ok';

        $record->update([
            'sdi_topado' => $sdiTopado,
            'cuota_imss' => $cuotaImss,
            'cuota_sua' => (float) ($extractedSuaBreak['total'] ?? $record->cuota_sua ?? 0.0),
            'cuota_patron' => $cuotaPatron,
            'diferencia_total' => $diferencia,
            'supera_tope_uma' => $superaTope,
            'alertas_json' => $alertas,
            'observaciones' => $observaciones !== [] ? implode(' | ', $observaciones) : null,
            'desglose_cuotas' => [
                'tipo' => $tipo->value,
                'sdi_declarado' => $sdiDeclaradoRaw !== null ? (float) $sdiDeclaradoRaw : null,
                'sdi_topado_declarado' => $sdiTopado,
                'sdi_topado_imss' => $sdiImssTopado,
                'dias_declarado' => $diasDeclarado,
                'prima_riesgo' => $primaRiesgo,
                'valor_uma' => $valorUma,
                'patron' => $cuotaPatronBreak,
                'imss' => $cuotaImssBreak,
                'sua' => $extractedSuaBreak,
            ],
            'estatus_conciliacion' => $estatus,
        ]);

        return [
            'cuota_imss' => $cuotaImss,
            'cuota_patron' => $cuotaPatron,
            'diferencia_total' => $diferencia,
            'tiene_diferencia' => $tieneDiferencia,
        ];
    }

    /**
     * Calcula la cuota total (patronal + obrera) con desglose por rama, según tipo de período.
     *
     * @param  array<string, float>  $emaRates
     * @param  array<string, float>  $ebaRates
     * @return array{total: float, patronal: float, obrera: float, desglose: array<string, float>}
     */
    private function calculateCuota(
        PeriodType $tipo,
        float $sdiTopado,
        int $dias,
        array $emaRates,
        array $ebaRates,
        float $valorUma,
        float $primaRiesgo,
        float $salarioMinimo,
        array $tablaCesantia,
        bool $exencionSm,
    ): array {
        return $tipo === PeriodType::Bimestral
            ? $this->calculateEba($sdiTopado, $dias, $ebaRates, $salarioMinimo, $valorUma, $tablaCesantia, $exencionSm)
            : $this->calculateEma($sdiTopado, $dias, $emaRates, $valorUma, $primaRiesgo, $salarioMinimo, $exencionSm);
    }

    /**
     * @param  array<string, float>  $rates
     * @return array{total: float, patronal: float, obrera: float, desglose: array<string, float>}
     */
    private function calculateEma(
        float $sdiTopado,
        int $dias,
        array $rates,
        float $valorUma,
        float $primaRiesgo,
        float $salarioMinimo,
        bool $exencionSm,
    ): array {
        $baseSdi = $sdiTopado * $dias;
        $baseUma = $valorUma * $dias;
        $tresUma = 3.0 * $valorUma;
        $baseExcedente = $sdiTopado > $tresUma ? ($sdiTopado - $tresUma) * $dias : 0.0;

        $cuotaFija = $baseUma * $this->pct($rates, 'cuota_fija_patronal');
        $excedentePatron = $baseExcedente * $this->pct($rates, 'excedente_patronal');
        $excedenteObrero = $baseExcedente * $this->pct($rates, 'excedente_obrero');
        $prestDineroPatron = $baseSdi * $this->pct($rates, 'prest_dinero_patronal');
        $prestDineroObrero = $baseSdi * $this->pct($rates, 'prest_dinero_obrero');
        $gmpPatron = $baseSdi * $this->pct($rates, 'gmp_patronal');
        $gmpObrero = $baseSdi * $this->pct($rates, 'gmp_obrero');
        $riesgos = $baseSdi * $primaRiesgo; // prima_riesgo ya es fracción (ej. 0.05470)
        $ivPatron = $baseSdi * $this->pct($rates, 'iv_patronal');
        $ivObrero = $baseSdi * $this->pct($rates, 'iv_obrero');
        $guarderias = $baseSdi * $this->pct($rates, 'guarderias_patronal');

        // Art. 36 LSS: Si salario mínimo, el patrón absorbe íntegramente las cuotas obreras
        if ($exencionSm && $salarioMinimo > 0 && $sdiTopado <= ($salarioMinimo + 0.01)) {
            $prestDineroPatron += $prestDineroObrero;
            $gmpPatron += $gmpObrero;
            $ivPatron += $ivObrero;
            $excedentePatron += $excedenteObrero;

            $prestDineroObrero = 0.0;
            $gmpObrero = 0.0;
            $ivObrero = 0.0;
            $excedenteObrero = 0.0;
        }

        $desglose = [
            'cuota_fija' => round($cuotaFija, 2),
            'excedente_patronal' => round($excedentePatron, 2),
            'excedente_obrero' => round($excedenteObrero, 2),
            'prest_dinero_patronal' => round($prestDineroPatron, 2),
            'prest_dinero_obrero' => round($prestDineroObrero, 2),
            'gmp_patronal' => round($gmpPatron, 2),
            'gmp_obrero' => round($gmpObrero, 2),
            'riesgos_trabajo' => round($riesgos, 2),
            'iv_patronal' => round($ivPatron, 2),
            'iv_obrero' => round($ivObrero, 2),
            'guarderias_patronal' => round($guarderias, 2),
        ];

        $patronal = round(
            $desglose['cuota_fija']
            + $desglose['excedente_patronal']
            + $desglose['prest_dinero_patronal']
            + $desglose['gmp_patronal']
            + $desglose['riesgos_trabajo']
            + $desglose['iv_patronal']
            + $desglose['guarderias_patronal'],
            2
        );
        $obrera = round(
            $desglose['excedente_obrero']
            + $desglose['prest_dinero_obrero']
            + $desglose['gmp_obrero']
            + $desglose['iv_obrero'],
            2
        );

        return [
            'total' => round($patronal + $obrera, 2),
            'patronal' => $patronal,
            'obrera' => $obrera,
            'desglose' => $desglose,
        ];
    }

    /**
     * @param  array<string, float>  $rates
     * @return array{total: float, patronal: float, obrera: float, desglose: array<string, float>}
     */
    private function calculateEba(
        float $sdiTopado,
        int $dias,
        array $rates,
        float $salarioMinimo,
        float $valorUma,
        array $tablaCesantia,
        bool $exencionSm,
    ): array {
        $baseSdi = $sdiTopado * $dias;

        $retiro = $baseSdi * $this->pct($rates, 'retiro_patronal');

        $cesantiaPatronRate = $this->resolveCesantiaRate($sdiTopado, $salarioMinimo, $valorUma, $tablaCesantia, $this->pct($rates, 'cesantia_patronal'));
        $cesantiaPatron = $baseSdi * $cesantiaPatronRate;
        $cesantiaObrero = $baseSdi * $this->pct($rates, 'cesantia_obrero');
        $infonavit = $baseSdi * $this->pct($rates, 'infonavit_patronal');

        // Art. 36 LSS: Exención de cuota obrera para Salario Mínimo
        if ($exencionSm && $salarioMinimo > 0 && $sdiTopado <= ($salarioMinimo + 0.01)) {
            $cesantiaPatron += $cesantiaObrero;
            $cesantiaObrero = 0.0;
        }

        $desglose = [
            'retiro' => round($retiro, 2),
            'cesantia_patronal' => round($cesantiaPatron, 2),
            'cesantia_obrero' => round($cesantiaObrero, 2),
            'infonavit' => round($infonavit, 2),
        ];

        $patronal = round($desglose['retiro'] + $desglose['cesantia_patronal'] + $desglose['infonavit'], 2);
        $obrera = $desglose['cesantia_obrero'];

        return [
            'total' => round($patronal + $obrera, 2),
            'patronal' => $patronal,
            'obrera' => $obrera,
            'desglose' => $desglose,
        ];
    }

    /**
     * Determina la tasa patronal de cesantía según la tabla dinámica o fallback.
     */
    private function resolveCesantiaRate(
        float $sdiTopado,
        float $salarioMinimo,
        float $valorUma,
        array $tablaCesantia,
        float $fallbackFraction,
    ): float {
        if (empty($tablaCesantia)) {
            return $fallbackFraction;
        }

        // 1. Si salario mínimo
        if ($salarioMinimo > 0 && $sdiTopado <= ($salarioMinimo + 0.01)) {
            foreach ($tablaCesantia as $row) {
                if (! empty($row['aplica_sm'])) {
                    return ((float) $row['porcentaje_patronal']) / 100.0;
                }
            }
        }

        // 2. Por rangos de UMA
        if ($valorUma > 0) {
            $vecesUma = round($sdiTopado / $valorUma, 4);

            foreach ($tablaCesantia as $row) {
                if (! empty($row['aplica_sm'])) {
                    continue;
                }

                $inf = isset($row['limite_inferior_umas']) ? (float) $row['limite_inferior_umas'] : 0.0;
                $sup = isset($row['limite_superior_umas']) && $row['limite_superior_umas'] !== null && $row['limite_superior_umas'] !== ''
                    ? (float) $row['limite_superior_umas']
                    : null;

                if ($sup === null) {
                    if ($vecesUma >= $inf) {
                        return ((float) $row['porcentaje_patronal']) / 100.0;
                    }
                } else {
                    if ($vecesUma >= $inf && $vecesUma <= ($sup + 0.0001)) {
                        return ((float) $row['porcentaje_patronal']) / 100.0;
                    }
                }
            }
        }

        return $fallbackFraction;
    }


    /**
     * @param  array<string, float>  $rates
     */
    private function pct(array $rates, string $key): float
    {
        return (float) ($rates[$key] ?? 0) / 100;
    }

    /**
     * @return array{0: array<int, string>, 1: array<int, string>, 2: bool}
     */
    private function buildAlerts(CalculationRecord $record, ?float $sdiDeclarado, float $topeSdi, float $diferencia): array
    {
        $alertas = [];
        $observaciones = [];

        if (! $record->presente_imss) {
            $alertas[] = 'falta_imss';
            $observaciones[] = 'Empleado no reportado en el archivo IMSS (posible alta no capturada en SUA).';
        }
        if (! $record->presente_nomina) {
            $alertas[] = 'falta_nomina';
            $observaciones[] = 'Empleado no reportado en la nómina interna (posible baja o error de captura).';
        }
        if (! $record->presente_sua) {
            $alertas[] = 'falta_sua';
            $observaciones[] = 'Empleado no reportado en SUA.';
        }

        $superaTope = $sdiDeclarado !== null && $sdiDeclarado > $topeSdi;
        if ($superaTope) {
            $alertas[] = 'supera_tope_uma';
            $observaciones[] = sprintf(
                'SDI declarado %.4f supera el tope de UMAs (%.4f).',
                $sdiDeclarado,
                $topeSdi,
            );
        }

        if ($record->presente_imss && ($record->presente_nomina || $record->presente_sua)) {
            $diasComparar = $record->dias_nomina ?? $record->dias_sua;
            if ($diasComparar !== null && $diasComparar !== $record->dias_imss) {
                $alertas[] = 'diferencia_dias';
                $observaciones[] = sprintf(
                    'Días distintos: IMSS %s vs %s %s (posible incapacidad o falta no capturada).',
                    $record->dias_imss ?? '—',
                    $record->presente_nomina ? 'Nómina' : 'SUA',
                    $diasComparar,
                );
            }

            $sdiComparar = $record->sdi_nomina ?? $record->sdi_sua;
            if ($sdiComparar !== null && $record->sdi_imss !== null
                && abs((float) $sdiComparar - (float) $record->sdi_imss) > 0.0001) {
                $alertas[] = 'diferencia_sdi';
                $observaciones[] = sprintf(
                    'SDI distinto: IMSS %.4f vs %s %.4f.',
                    (float) $record->sdi_imss,
                    $record->presente_nomina ? 'Nómina' : 'SUA',
                    (float) $sdiComparar,
                );
            }
        }

        if (abs($diferencia) > self::EPSILON_MONEY) {
            $alertas[] = 'diferencia_cuota';
        }

        return [$alertas, $observaciones, $superaTope];
    }

    /**
     * Jerarquía del semáforo: faltante > falta_sua > supera_tope > diferencia_dias
     *   > diferencia_sdi > diferencia_cuota > ok
     *
     * @param  array<int, string>  $alertas
     */
    private function determineEstatus(array $alertas): string
    {
        if (in_array('falta_imss', $alertas, true) || in_array('falta_nomina', $alertas, true)) {
            return 'faltante';
        }
        if (in_array('falta_sua', $alertas, true)) {
            return 'falta_sua';
        }
        if (in_array('supera_tope_uma', $alertas, true)) {
            return 'supera_tope';
        }
        if (in_array('diferencia_dias', $alertas, true)) {
            return 'diferencia_dias';
        }
        if (in_array('diferencia_sdi', $alertas, true)) {
            return 'diferencia_sdi';
        }
        if (in_array('diferencia_cuota', $alertas, true)) {
            return 'diferencia_cuota';
        }
        if (in_array('diferencia_credito', $alertas, true) || in_array('credito_no_aplicado_sua', $alertas, true) || in_array('amortizacion_sin_credito_imss', $alertas, true)) {
            return 'diferencia_credito';
        }
        return 'ok';
    }

    private function applyCapAndFloor(?float $sdi, float $topeSdi, float $salarioMinimo): ?float
    {
        if ($sdi === null) {
            return null;
        }

        $result = min($sdi, $topeSdi);
        if ($salarioMinimo > 0) {
            $result = max($result, $salarioMinimo);
        }

        return round($result, 4);
    }

    private function firstNonNull(mixed ...$values): mixed
    {
        foreach ($values as $v) {
            if ($v !== null) {
                return $v;
            }
        }
        return null;
    }
}
