<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Parsers;

use App\Enums\FileSource;
use App\Services\Compulsa\Dto\EmployeeRow;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Parser para exportaciones del SUA (Sistema Único de Autodeterminación).
 * Soporta tanto el formato nativo multirrenglón de "Cédula de Determinación de Cuotas"
 * como formatos planos tabulares.
 */
class SuaFileParser extends AbstractExcelParser
{
    public function source(): FileSource
    {
        return FileSource::Sua;
    }

    public function parseInChunks(string $absolutePath, int $chunkSize, callable $onChunk): void
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException(sprintf('Archivo no encontrado: %s', $absolutePath));
        }

        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($absolutePath);
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Detectar si es el formato oficial Cédula SUA multirrenglón
        $isCedulaFormat = $this->isSuaCedulaFormat($sheet);

        if ($isCedulaFormat) {
            $this->parseCedulaSua($sheet, $chunkSize, $onChunk);
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);
            return;
        }

        // 2. Si no es cédula, usar el parser estándar tabular
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);
        parent::parseInChunks($absolutePath, $chunkSize, $onChunk);
    }

    /**
     * Revisa si el archivo contiene las marcas típicas de la Cédula del SUA.
     */
    private function isSuaCedulaFormat(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): bool
    {
        $highestRow = min(25, $sheet->getHighestDataRow());
        $highestCol = $sheet->getHighestDataColumn();

        for ($r = 1; $r <= $highestRow; $r++) {
            $row = $sheet->rangeToArray("A{$r}:{$highestCol}{$r}", null, false, false)[0] ?? [];
            foreach ($row as $val) {
                $text = mb_strtoupper((string) $val);
                if (str_contains($text, 'SISTEMA ÚNICO DE AUTODETERMINACIÓN') ||
                    str_contains($text, 'SISTEMA UNICO DE AUTODETERMINACION') ||
                    str_contains($text, 'CÉDULA DE DETERMINACIÓN') ||
                    str_contains($text, 'CEDULA DE DETERMINACION')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Parsea la Cédula multirrenglón del SUA (soporta mensual y bimestral).
     */
    private function parseCedulaSua(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $chunkSize, callable $onChunk): void
    {
        $highestRow = $sheet->getHighestDataRow();
        $highestCol = $sheet->getHighestDataColumn();

        [$diasCol, $sdiCol] = $this->detectCedulaDataColumns($sheet, $highestRow, $highestCol);

        /** @var \Illuminate\Support\Collection<string, \App\Services\Compulsa\Dto\EmployeeRow> $consolidated */
        $consolidated = collect();
        $currentNss = null;
        $currentNombre = null;
        $currentRfcCurp = null;

        for ($r = 1; $r <= $highestRow; $r++) {
            $row = $sheet->rangeToArray("A{$r}:{$highestCol}{$r}", null, false, false)[0] ?? [];
            $firstCell = trim((string) ($row[0] ?? ''));
            $cleanFirst = $this->cleanNss($firstCell);

            if ($cleanFirst !== null) {
                // Renglón de cabecera de empleado: NSS en Col 0, Nombre en Col 5, RFC/CURP en Col 8 o 9
                $currentNss = $cleanFirst;
                $currentNombre = $this->cleanString($row[5] ?? null);
                $currentRfcCurp = $this->cleanString($row[8] ?? $row[9] ?? null);
            } elseif ($currentNss !== null) {
                // Renglón de datos numéricos
                $diasVal = $row[$diasCol] ?? null;
                $sdiVal = $row[$sdiCol] ?? null;

                $dias = $this->cleanInt($diasVal);
                $sdi = $this->cleanFloat($sdiVal);

                if ($dias !== null && $sdi !== null && $sdi > 0) {
                    // Separar RFC y CURP si vienen juntos o determinar formato
                    $rfc = null;
                    $curp = null;
                    if ($currentRfcCurp !== null) {
                        $cleanId = preg_replace('/[^A-Za-z0-9]/', '', $currentRfcCurp);
                        if (strlen($cleanId) === 18) {
                            $curp = $cleanId;
                        } elseif (strlen($cleanId) === 12 || strlen($cleanId) === 13) {
                            $rfc = $cleanId;
                        } else {
                            $curp = $cleanId;
                        }
                    }

                    // Extracción de Cuotas y Ramas según período (Mensual o Bimestral)
                    $isBimestral = ($diasCol === 2 && $sdiCol === 3);
                    $cuotasDetalle = $this->extractSuaCuotasDetalle($row, $isBimestral);
                    $amortizacion = (float) ($cuotasDetalle['amortizacion'] ?? 0.0);
                    $cuotaTotal = (float) ($cuotasDetalle['total'] ?? 0.0);

                    $employee = new EmployeeRow(
                        source: FileSource::Sua,
                        nss: $currentNss,
                        nombreCompleto: $currentNombre ?? 'Sin nombre',
                        diasLaborados: $dias,
                        sdi: $sdi,
                        rfc: $rfc,
                        curp: $curp,
                        raw: [],
                        cuotaTotal: $cuotaTotal > 0 ? $cuotaTotal : null,
                        cuotasDetalle: $cuotasDetalle,
                    );

                    if (! $consolidated->has($currentNss)) {
                        $consolidated->put($currentNss, $employee);
                    } else {
                        $existing = $consolidated->get($currentNss);
                        $mergedDias = ($existing->diasLaborados ?? 0) + $dias;
                        $latestSdi = $sdi > 0 ? $sdi : $existing->sdi;

                        $mergedCuotasDetalle = $this->mergeSuaCuotasDetalle(
                            $existing->cuotasDetalle ?? [],
                            $cuotasDetalle
                        );
                        $mergedTotal = (float) ($mergedCuotasDetalle['total'] ?? 0.0);

                        $consolidated->put($currentNss, new EmployeeRow(
                            source: FileSource::Sua,
                            nss: $existing->nss,
                            nombreCompleto: $existing->nombreCompleto,
                            diasLaborados: $mergedDias,
                            sdi: $latestSdi,
                            rfc: $existing->rfc ?? $rfc,
                            curp: $existing->curp ?? $curp,
                            raw: [],
                            cuotaTotal: $mergedTotal > 0 ? $mergedTotal : null,
                            cuotasDetalle: $mergedCuotasDetalle,
                        ));
                    }

                    $currentNss = null; // Reiniciar para el siguiente empleado
                }
            }
        }

        // Emitir en chunks
        $consolidated->values()->chunk($chunkSize)->each(function (\Illuminate\Support\Collection $chunk) use ($onChunk): void {
            $onChunk($chunk);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function extractSuaCuotasDetalle(array $row, bool $isBimestral): array
    {
        if ($isBimestral) {
            $retiro = (float) ($this->cleanFloat($row[7] ?? null) ?? 0.0);
            $cesPat = (float) ($this->cleanFloat($row[8] ?? null) ?? 0.0);
            $cesObr = (float) ($this->cleanFloat($row[9] ?? null) ?? 0.0);
            $infonavit = (float) ($this->cleanFloat($row[11] ?? null) ?? 0.0);
            $sumaInfonavit = (float) ($this->cleanFloat($row[15] ?? null) ?? 0.0);
            $amortizacion = $sumaInfonavit > $infonavit ? round($sumaInfonavit - $infonavit, 2) : 0.0;
            $creditoRaw = $this->cleanString($row[16] ?? null);
            $numeroCredito = ($creditoRaw !== null && $creditoRaw !== '' && $creditoRaw !== '-')
                ? preg_replace('/[^0-9]/', '', $creditoRaw)
                : null;
            if ($numeroCredito === '') {
                $numeroCredito = null;
            }

            $desglose = [
                'retiro' => round($retiro, 2),
                'cesantia_patronal' => round($cesPat, 2),
                'cesantia_obrero' => round($cesObr, 2),
                'infonavit' => round($infonavit, 2),
            ];
            if ($amortizacion > 0) {
                $desglose['amortizacion'] = round($amortizacion, 2);
            }
            if ($numeroCredito !== null) {
                $desglose['numero_credito'] = $numeroCredito;
            }

            $patronal = round($retiro + $cesPat + $infonavit, 2);
            $obrera = round($cesObr, 2);
            $subtotal = round($patronal + $obrera, 2);
            $total = round($subtotal + $amortizacion, 2);

            return [
                'total' => $total,
                'patronal' => $patronal,
                'obrera' => $obrera,
                'cuotas_subtotal' => $subtotal,
                'amortizacion' => $amortizacion,
                'numero_credito' => $numeroCredito,
                'desglose' => $desglose,
            ];
        }

        // Formato Mensual (EMA Cédula)
        $cuotaFija = (float) ($this->cleanFloat($row[8] ?? null) ?? 0.0);
        $excPat = (float) ($this->cleanFloat($row[9] ?? null) ?? 0.0);
        $excObr = (float) ($this->cleanFloat($row[10] ?? null) ?? 0.0);
        $pdPat = (float) ($this->cleanFloat($row[11] ?? null) ?? 0.0);
        $pdObr = (float) ($this->cleanFloat($row[12] ?? null) ?? 0.0);
        $gmpPat = (float) ($this->cleanFloat($row[13] ?? null) ?? 0.0);
        $gmpObr = (float) ($this->cleanFloat($row[14] ?? null) ?? 0.0);
        $rt = (float) ($this->cleanFloat($row[15] ?? null) ?? 0.0);
        $ivPat = (float) ($this->cleanFloat($row[16] ?? null) ?? 0.0);
        $ivObr = (float) ($this->cleanFloat($row[17] ?? null) ?? 0.0);
        $gpsPat = (float) ($this->cleanFloat($row[18] ?? null) ?? 0.0);

        $desglose = [
            'cuota_fija' => round($cuotaFija, 2),
            'excedente_patronal' => round($excPat, 2),
            'excedente_obrero' => round($excObr, 2),
            'prest_dinero_patronal' => round($pdPat, 2),
            'prest_dinero_obrero' => round($pdObr, 2),
            'gmp_patronal' => round($gmpPat, 2),
            'gmp_obrero' => round($gmpObr, 2),
            'riesgos_trabajo' => round($rt, 2),
            'iv_patronal' => round($ivPat, 2),
            'iv_obrero' => round($ivObr, 2),
            'guarderias_patronal' => round($gpsPat, 2),
        ];

        $patronalRaw = $this->cleanFloat($row[19] ?? null);
        $patronal = $patronalRaw !== null ? (float) $patronalRaw : ($cuotaFija + $excPat + $pdPat + $gmpPat + $rt + $ivPat + $gpsPat);

        $obreraRaw = $this->cleanFloat($row[20] ?? null);
        $obrera = $obreraRaw !== null ? (float) $obreraRaw : ($excObr + $pdObr + $gmpObr + $ivObr);

        $totalRaw = $this->cleanFloat($row[21] ?? null);
        $total = $totalRaw !== null ? (float) $totalRaw : ($patronal + $obrera);

        return [
            'total' => round($total, 2),
            'patronal' => round($patronal, 2),
            'obrera' => round($obrera, 2),
            'cuotas_subtotal' => round($total, 2),
            'desglose' => $desglose,
        ];
    }

    /**
     * @param  array<string, mixed>  $ex
     * @param  array<string, mixed>  $new
     * @return array<string, mixed>
     */
    private function mergeSuaCuotasDetalle(array $ex, array $new): array
    {
        if (empty($ex)) {
            return $new;
        }
        if (empty($new)) {
            return $ex;
        }

        $total = round((float) ($ex['total'] ?? 0) + (float) ($new['total'] ?? 0), 2);
        $patronal = round((float) ($ex['patronal'] ?? 0) + (float) ($new['patronal'] ?? 0), 2);
        $obrera = round((float) ($ex['obrera'] ?? 0) + (float) ($new['obrera'] ?? 0), 2);
        $subtotal = round((float) ($ex['cuotas_subtotal'] ?? 0) + (float) ($new['cuotas_subtotal'] ?? 0), 2);

        $amortEx = (float) ($ex['amortizacion'] ?? ($ex['desglose']['amortizacion'] ?? 0));
        $amortNew = (float) ($new['amortizacion'] ?? ($new['desglose']['amortizacion'] ?? 0));
        $mergedAmort = round($amortEx + $amortNew, 2);

        $credito = $new['numero_credito'] ?? ($ex['numero_credito'] ?? null);

        $exRamas = $ex['desglose'] ?? [];
        $newRamas = $new['desglose'] ?? [];
        $allKeys = array_unique(array_merge(array_keys($exRamas), array_keys($newRamas)));

        $mergedDesglose = [];
        foreach ($allKeys as $k) {
            if ($k === 'numero_credito') {
                $mergedDesglose[$k] = $credito;
                continue;
            }
            $v1 = (float) ($exRamas[$k] ?? 0);
            $v2 = (float) ($newRamas[$k] ?? 0);
            $mergedDesglose[$k] = round($v1 + $v2, 2);
        }

        return [
            'total' => $total,
            'patronal' => $patronal,
            'obrera' => $obrera,
            'cuotas_subtotal' => $subtotal,
            'amortizacion' => $mergedAmort > 0 ? $mergedAmort : null,
            'numero_credito' => $credito,
            'desglose' => $mergedDesglose,
        ];
    }

    /**
     * Detecta dinámicamente las columnas de 'Días' y 'SDI' en la Cédula del SUA.
     * En formato mensual: Días suele estar en Col 3 y SDI en Col 4.
     * En formato bimestral: Días suele estar en Col 2 y SDI en Col 3.
     *
     * @return array{0: int, 1: int}
     */
    private function detectCedulaDataColumns(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $highestRow, string $highestCol): array
    {
        $maxScan = min(35, $highestRow);
        $diasCol = null;
        $sdiCol = null;

        for ($r = 1; $r <= $maxScan; $r++) {
            $row = $sheet->rangeToArray("A{$r}:{$highestCol}{$r}", null, false, false)[0] ?? [];
            foreach ($row as $colIdx => $val) {
                $v = mb_strtolower(trim((string) $val));
                if ($v === 'dias' || $v === 'días') {
                    $diasCol = (int) $colIdx;
                }
                if ($v === 'sdi' || $v === 's.d.i.') {
                    $sdiCol = (int) $colIdx;
                }
            }
            if ($diasCol !== null && $sdiCol !== null) {
                return [$diasCol, $sdiCol];
            }
        }

        // Fallback por defecto si no encuentra cabecera explícita
        return [3, 4];
    }

    protected function columnAliases(): array
    {
        return [
            'nss' => [
                'nss',
                'numero de seguridad social',
                'no seguridad social',
                'no ss',
            ],
            'nombre' => [
                'nombre',
                'nombre completo',
                'nombre del trabajador',
                'apellido paterno apellido materno y nombre',
                'apellido paterno materno y nombre',
            ],
            'dias' => [
                'dias',
                'dias cotizados',
                'dias trabajados',
                'dias del periodo',
                'dias del bimestre',
                'dias del mes',
            ],
            'sdi' => [
                'sdi',
                'salario diario integrado',
                'salario base de cotizacion',
                'sbc',
            ],
            'rfc' => ['rfc'],
            'curp' => ['curp'],
            'amortizacion' => [
                'amortizacion',
                'amortizacion infonavit',
                'amort infonavit',
                'retencion infonavit',
            ],
            'numero_credito' => [
                'numero de credito',
                'no de credito',
                'no credito',
                'credito',
                'credito infonavit',
            ],
        ];
    }

    protected function mapRow(array $row, array $columns): ?EmployeeRow
    {
        $base = parent::mapRow($row, $columns);
        if ($base === null) {
            return null;
        }

        $amortizacion = $this->cleanFloat($this->pick($row, $columns, 'amortizacion'));
        $creditoRaw = $this->cleanString($this->pick($row, $columns, 'numero_credito'));
        $numeroCredito = null;
        if ($creditoRaw !== null && $creditoRaw !== '' && $creditoRaw !== '-') {
            $clean = preg_replace('/[^0-9]/', '', $creditoRaw);
            if ($clean !== '') {
                $numeroCredito = $clean;
            }
        }

        $cuotasDetalle = [];
        if ($amortizacion !== null || $numeroCredito !== null) {
            $cuotasDetalle = [
                'amortizacion' => $amortizacion !== null ? (float) $amortizacion : 0.0,
                'numero_credito' => $numeroCredito,
            ];
        }

        return new EmployeeRow(
            source: $base->source,
            nss: $base->nss,
            nombreCompleto: $base->nombreCompleto,
            diasLaborados: $base->diasLaborados,
            sdi: $base->sdi,
            rfc: $base->rfc,
            curp: $base->curp,
            raw: $base->raw,
            cuotaTotal: $amortizacion !== null ? round((float) $amortizacion, 2) : null,
            cuotasDetalle: $cuotasDetalle,
        );
    }
}
