<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Actions;

use App\Models\Compulsa\Calculation;
use App\Models\Compulsa\CalculationRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Motor de vinculación secundaria para detectar colaboradores con discrepancias
 * de NSS entre Nómina, SUA e IMSS (errores mecanográficos o doble número de seguridad social)
 * utilizando CURP, RFC y similitud de Nombre completo.
 */
class LinkDiscrepantRecordsAction
{
    /**
     * @return int Cantidad de pares vinculados/fusionados.
     */
    public function execute(Calculation $calculation): int
    {
        return DB::transaction(function () use ($calculation): int {
            /** @var Collection<int, CalculationRecord> $records */
            $records = $calculation->records()->get();

            // 1. Filtrar registros con fuentes incompletas (candidatos huérfanos)
            $suaOnly = $records->filter(fn (CalculationRecord $r) => $r->presente_sua && ! $r->presente_imss && ! $r->presente_nomina);
            $imssOrNominaWithoutSua = $records->filter(fn (CalculationRecord $r) => ! $r->presente_sua && ($r->presente_imss || $r->presente_nomina));

            $mergedCount = 0;

            // CASO A: Registro presente en SUA pero ausente en IMSS/Nómina -> Buscar match en IMSS/Nómina
            foreach ($suaOnly as $sourceSua) {
                $target = $this->findMatch($sourceSua, $imssOrNominaWithoutSua);
                if ($target === null) {
                    continue;
                }

                $this->mergeSuaIntoTarget($target, $sourceSua);
                $mergedCount++;

                // Remover de las listas de candidatos
                $imssOrNominaWithoutSua = $imssOrNominaWithoutSua->reject(fn ($r) => $r->id === $target->id);
            }

            // CASO B: Registro presente en Nómina pero ausente en IMSS/SUA -> Buscar match en IMSS/SUA
            $nominaOnly = $calculation->records()->get()->filter(fn (CalculationRecord $r) => $r->presente_nomina && ! $r->presente_imss && ! $r->presente_sua);
            $imssOrSuaWithoutNomina = $calculation->records()->get()->filter(fn (CalculationRecord $r) => ! $r->presente_nomina && ($r->presente_imss || $r->presente_sua));

            foreach ($nominaOnly as $sourceNomina) {
                $target = $this->findMatch($sourceNomina, $imssOrSuaWithoutNomina);
                if ($target === null) {
                    continue;
                }

                $this->mergeNominaIntoTarget($target, $sourceNomina);
                $mergedCount++;

                $imssOrSuaWithoutNomina = $imssOrSuaWithoutNomina->reject(fn ($r) => $r->id === $target->id);
            }

            if ($mergedCount > 0) {
                Log::info('Compulsa: Registros vinculados por NSS discrepante', [
                    'calculation_id' => $calculation->id,
                    'vinculados' => $mergedCount,
                ]);
            }

            return $mergedCount;
        });
    }

    /**
     * @param Collection<int, CalculationRecord> $candidates
     */
    private function findMatch(CalculationRecord $source, Collection $candidates): ?CalculationRecord
    {
        $cleanCurpSource = $this->cleanIdentifier($source->curp);
        $cleanRfcSource = $this->cleanIdentifier($source->rfc);
        $normalizedNameSource = $this->normalizeName($source->nombre_completo);

        // 1. Criterio de Certeza 1: Coincidencia exacta de CURP (18 caracteres)
        if ($cleanCurpSource !== null && strlen($cleanCurpSource) >= 15) {
            $match = $candidates->first(function (CalculationRecord $c) use ($cleanCurpSource) {
                $cCurp = $this->cleanIdentifier($c->curp);
                return $cCurp !== null && $cCurp === $cleanCurpSource;
            });
            if ($match !== null) {
                return $match;
            }
        }

        // 2. Criterio de Certeza 2: Coincidencia exacta de RFC (10 a 13 caracteres)
        if ($cleanRfcSource !== null && strlen($cleanRfcSource) >= 10) {
            $match = $candidates->first(function (CalculationRecord $c) use ($cleanRfcSource) {
                $cRfc = $this->cleanIdentifier($c->rfc);
                return $cRfc !== null && $cRfc === $cleanRfcSource;
            });
            if ($match !== null) {
                return $match;
            }
        }

        // 3. Criterio de Certeza 3: Similitud de Nombre >= 95%
        if ($normalizedNameSource !== '') {
            foreach ($candidates as $candidate) {
                $normalizedCandName = $this->normalizeName($candidate->nombre_completo);
                if ($normalizedCandName === '') {
                    continue;
                }

                if ($normalizedNameSource === $normalizedCandName) {
                    return $candidate;
                }

                similar_text($normalizedNameSource, $normalizedCandName, $percent);
                if ($percent >= 95.0) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function mergeSuaIntoTarget(CalculationRecord $target, CalculationRecord $sourceSua): void
    {
        $alertas = $target->alertas_json ?? [];
        if (! in_array('nss_discrepante_sua', $alertas, true)) {
            $alertas[] = 'nss_discrepante_sua';
        }

        $desglose = $target->desglose_cuotas ?? [];
        $desglose['nss_discrepante_sua'] = [
            'nss_oficial' => $target->nss,
            'nss_sua_detectado' => $sourceSua->nss,
            'nombre_sua' => $sourceSua->nombre_completo,
            'curp_sua' => $sourceSua->curp,
        ];

        $target->update([
            'dias_sua' => $sourceSua->dias_sua,
            'sdi_sua' => $sourceSua->sdi_sua,
            'cuota_sua' => $sourceSua->cuota_sua,
            'desglose_sua' => $sourceSua->desglose_sua,
            'amortizacion_sua' => $sourceSua->amortizacion_sua,
            'numero_credito' => $target->numero_credito ?? $sourceSua->numero_credito,
            'presente_sua' => true,
            'alertas_json' => array_values(array_unique($alertas)),
            'desglose_cuotas' => $desglose,
        ]);

        // Eliminar el registro duplicado del SUA
        $sourceSua->delete();
    }

    private function mergeNominaIntoTarget(CalculationRecord $target, CalculationRecord $sourceNomina): void
    {
        $alertas = $target->alertas_json ?? [];
        if (! in_array('nss_discrepante_nomina', $alertas, true)) {
            $alertas[] = 'nss_discrepante_nomina';
        }

        $desglose = $target->desglose_cuotas ?? [];
        $desglose['nss_discrepante_nomina'] = [
            'nss_oficial' => $target->nss,
            'nss_nomina_detectado' => $sourceNomina->nss,
            'nombre_nomina' => $sourceNomina->nombre_completo,
            'curp_nomina' => $sourceNomina->curp,
        ];

        $target->update([
            'dias_nomina' => $sourceNomina->dias_nomina,
            'dias_faltas_nomina' => $sourceNomina->dias_faltas_nomina,
            'dias_incapacidad_nomina' => $sourceNomina->dias_incapacidad_nomina,
            'dias_vacaciones_nomina' => $sourceNomina->dias_vacaciones_nomina,
            'sdi_nomina' => $sourceNomina->sdi_nomina,
            'presente_nomina' => true,
            'alertas_json' => array_values(array_unique($alertas)),
            'desglose_cuotas' => $desglose,
        ]);

        $sourceNomina->delete();
    }

    private function cleanIdentifier(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = preg_replace('/[^A-Za-z0-9]/', '', mb_strtoupper(trim($value)));
        return ($clean !== '' && $clean !== null) ? $clean : null;
    }

    private function normalizeName(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $v = mb_strtoupper(trim($value));
        $v = strtr($v, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);
        $v = preg_replace('/[^A-Z0-9\s]/', '', $v);
        return trim(preg_replace('/\s+/', ' ', $v));
    }
}
