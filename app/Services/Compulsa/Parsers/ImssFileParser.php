<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Parsers;

use App\Enums\FileSource;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Parser para archivos EMA (mensual) y EBA (bimestral) emitidos por el IMSS.
 * Soporta tanto el archivo original multishoja (.xls / .xlsx) como hojas extraídas.
 */
class ImssFileParser extends AbstractExcelParser
{
    public function source(): FileSource
    {
        return FileSource::Imss;
    }

    protected ?\App\Enums\PeriodType $periodType = null;

    public function setPeriodType(?\App\Enums\PeriodType $type): self
    {
        $this->periodType = $type;
        return $this;
    }

    protected function selectSheetIndex(Spreadsheet $spreadsheet): int|string
    {
        $sheetNames = $spreadsheet->getSheetNames();

        if ($this->periodType === \App\Enums\PeriodType::Bimestral) {
            // 1. Si el cálculo es Bimestral, buscar prioritariamente 'Movimientos EBA'
            foreach ($sheetNames as $idx => $name) {
                if (stripos($name, 'Movimientos EBA') !== false) {
                    return $idx;
                }
            }
            foreach ($sheetNames as $idx => $name) {
                if (stripos($name, 'EBA') !== false) {
                    return $idx;
                }
            }
        } else {
            // 2. Si el cálculo es Mensual (o por defecto), buscar prioritariamente 'Movimientos EMA'
            foreach ($sheetNames as $idx => $name) {
                if (stripos($name, 'Movimientos EMA') !== false) {
                    return $idx;
                }
            }
            foreach ($sheetNames as $idx => $name) {
                if (stripos($name, 'EMA') !== false) {
                    return $idx;
                }
            }
        }

        // 3. Fallback: cualquier hoja que contenga 'Movimientos'
        foreach ($sheetNames as $idx => $name) {
            if (stripos($name, 'Movimientos') !== false) {
                return $idx;
            }
        }

        // 4. Fallback final: Primera hoja
        return 0;
    }

    protected function columnAliases(): array
    {
        return [
            'nss' => [
                'nss',
                'no seguridad social',
                'numero de seguridad social',
                'no de seguridad social',
                'num seguridad social',
                'no ss',
            ],
            'nombre' => [
                'nombre',
                'nombre completo',
                'nombre del trabajador',
                'nombre del asegurado',
                'apellido paterno apellido materno y nombre',
            ],
            'dias' => [
                'dias',
                'dias cotizados',
                'dias trabajados',
                'dias del bimestre',
                'dias del mes',
                'dias cotizacion',
            ],
            'sdi' => [
                'salario diario',
                'sdi',
                'salario base de cotizacion',
                'salario diario integrado',
                'sbc',
                'salario base',
            ],
            'rfc' => ['rfc'],
            'curp' => ['curp'],
            // Ramas EMA (Mensual)
            'cuota_fija' => ['cuota fija'],
            'excedente_patronal' => ['excedente patronal', 'exc patronal', 'excedente pat'],
            'excedente_obrero' => ['excedente obrero', 'exc obrero', 'excedente obr'],
            'prest_dinero_patronal' => ['prestaciones en dinero patronal', 'prest dinero patronal', 'prestaciones en dinero pat', 'prest dinero pat'],
            'prest_dinero_obrero' => ['prestaciones en dinero obrero', 'prest dinero obrero', 'prestaciones en dinero obr', 'prest dinero obr'],
            'gmp_patronal' => ['gastos medicos y pensionados patronal', 'gastos medicos y pensionados pat', 'gmp patronal', 'gastos med y pens patronal'],
            'gmp_obrero' => ['gastos medicos y pensionados obrero', 'gastos medicos y pensionados obr', 'gmp obrero', 'gastos med y pens obrero'],
            'riesgos_trabajo' => ['riesgos de trabajo', 'rt'],
            'iv_patronal' => ['invalidez y vida patronal', 'iv patronal', 'invalidez y vida pat'],
            'iv_obrero' => ['invalidez y vida obrero', 'iv obrero', 'invalidez y vida obr'],
            'guarderias_patronal' => ['guarderias y prestaciones sociales', 'guarderias', 'guarderias y prest sociales'],
            // Ramas EBA (Bimestral)
            'retiro' => ['retiro'],
            'cesantia_patronal' => ['cesantia en edad avanzada y vejez patronal', 'cesantia patronal', 'cesantia en edad avanzada y vejez pat'],
            'cesantia_obrero' => ['cesantia en edad avanzada y vejez obrero', 'cesantia obrero', 'cesantia en edad avanzada y vejez obr'],
            'infonavit_patronal' => ['aportacion patronal', 'infonavit patronal', 'aportacion patronal sin credito'],
            'amortizacion' => ['amortizacion'],
            'numero_credito' => ['numero de credito', 'no de credito', 'no credito', 'credito'],
            'subtotal_rcv' => ['subtotal rcv'],
            'subtotal_infonavit' => ['subtotal infonavit'],
            'total' => ['total'],
        ];
    }

    public function parseInChunks(string $absolutePath, int $chunkSize, callable $onChunk): void
    {
        /** @var \Illuminate\Support\Collection<string, \App\Services\Compulsa\Dto\EmployeeRow> $consolidated */
        $consolidated = collect();

        // Extraer y consolidar movimientos por NSS para evitar duplicados y acumular días y cuotas
        parent::parseInChunks($absolutePath, $chunkSize, function (\Illuminate\Support\Collection $chunk) use (&$consolidated): void {
            foreach ($chunk as $row) {
                /** @var \App\Services\Compulsa\Dto\EmployeeRow $row */
                $nss = $row->nss;
                if (! $consolidated->has($nss)) {
                    $consolidated->put($nss, $row);
                } else {
                    /** @var \App\Services\Compulsa\Dto\EmployeeRow $existing */
                    $existing = $consolidated->get($nss);
                    $consolidated->put($nss, $this->mergeEmployeeRows($existing, $row));
                }
            }
        });

        // Emitir los registros consolidados en chunks
        $consolidated->values()->chunk($chunkSize)->each(function (\Illuminate\Support\Collection $chunk) use ($onChunk): void {
            $onChunk($chunk);
        });
    }

    protected function mapRow(array $row, array $columns): ?\App\Services\Compulsa\Dto\EmployeeRow
    {
        $base = parent::mapRow($row, $columns);
        if ($base === null) {
            return null;
        }

        $isEma = ($columns['cuota_fija'] ?? null) !== null;
        $isEba = ($columns['retiro'] ?? null) !== null;
        $cuotasDetalle = [];
        $cuotaTotal = null;

        $shouldParseAsEba = $this->periodType === \App\Enums\PeriodType::Bimestral
            ? $isEba
            : (! $isEma && $isEba);

        if ($shouldParseAsEba) {
            $desglose = [
                'retiro' => (float) ($this->cleanFloat($this->pick($row, $columns, 'retiro')) ?? 0),
                'cesantia_patronal' => (float) ($this->cleanFloat($this->pick($row, $columns, 'cesantia_patronal')) ?? 0),
                'cesantia_obrero' => (float) ($this->cleanFloat($this->pick($row, $columns, 'cesantia_obrero')) ?? 0),
                'infonavit' => (float) ($this->cleanFloat($this->pick($row, $columns, 'infonavit_patronal')) ?? 0),
            ];

            $amortizacion = $this->cleanFloat($this->pick($row, $columns, 'amortizacion'));
            if ($amortizacion !== null && $amortizacion > 0) {
                $desglose['amortizacion'] = (float) $amortizacion;
            }

            $creditoNum = $this->cleanString($this->pick($row, $columns, 'numero_credito'));
            if ($creditoNum !== null && $creditoNum !== '-' && $creditoNum !== '') {
                $desglose['numero_credito'] = $creditoNum;
            }

            $patronal = $desglose['retiro'] + $desglose['cesantia_patronal'] + $desglose['infonavit'];
            $obrera = $desglose['cesantia_obrero'];

            $totalCol = $this->cleanFloat($this->pick($row, $columns, 'total'));
            $cuotaTotal = $totalCol !== null ? (float) $totalCol : ($patronal + $obrera);


            $cuotasDetalle = [
                'total' => round($cuotaTotal, 2),
                'patronal' => round($patronal, 2),
                'obrera' => round($obrera, 2),
                'desglose' => array_map(fn ($v) => round((float) $v, 2), $desglose),
            ];
        } elseif ($isEma) {
            $desglose = [
                'cuota_fija' => (float) ($this->cleanFloat($this->pick($row, $columns, 'cuota_fija')) ?? 0),
                'excedente_patronal' => (float) ($this->cleanFloat($this->pick($row, $columns, 'excedente_patronal')) ?? 0),
                'excedente_obrero' => (float) ($this->cleanFloat($this->pick($row, $columns, 'excedente_obrero')) ?? 0),
                'prest_dinero_patronal' => (float) ($this->cleanFloat($this->pick($row, $columns, 'prest_dinero_patronal')) ?? 0),
                'prest_dinero_obrero' => (float) ($this->cleanFloat($this->pick($row, $columns, 'prest_dinero_obrero')) ?? 0),
                'gmp_patronal' => (float) ($this->cleanFloat($this->pick($row, $columns, 'gmp_patronal')) ?? 0),
                'gmp_obrero' => (float) ($this->cleanFloat($this->pick($row, $columns, 'gmp_obrero')) ?? 0),
                'riesgos_trabajo' => (float) ($this->cleanFloat($this->pick($row, $columns, 'riesgos_trabajo')) ?? 0),
                'iv_patronal' => (float) ($this->cleanFloat($this->pick($row, $columns, 'iv_patronal')) ?? 0),
                'iv_obrero' => (float) ($this->cleanFloat($this->pick($row, $columns, 'iv_obrero')) ?? 0),
                'guarderias_patronal' => (float) ($this->cleanFloat($this->pick($row, $columns, 'guarderias_patronal')) ?? 0),
            ];

            $patronal = $desglose['cuota_fija'] + $desglose['excedente_patronal'] + $desglose['prest_dinero_patronal'] +
                        $desglose['gmp_patronal'] + $desglose['riesgos_trabajo'] + $desglose['iv_patronal'] + $desglose['guarderias_patronal'];
            $obrera = $desglose['excedente_obrero'] + $desglose['prest_dinero_obrero'] + $desglose['gmp_obrero'] + $desglose['iv_obrero'];

            $totalCol = $this->cleanFloat($this->pick($row, $columns, 'total'));
            $cuotaTotal = $totalCol !== null ? (float) $totalCol : ($patronal + $obrera);

            $cuotasDetalle = [
                'total' => round($cuotaTotal, 2),
                'patronal' => round($patronal, 2),
                'obrera' => round($obrera, 2),
                'desglose' => array_map(fn ($v) => round((float) $v, 2), $desglose),
            ];
        }

        return new \App\Services\Compulsa\Dto\EmployeeRow(
            source: $base->source,
            nss: $base->nss,
            nombreCompleto: $base->nombreCompleto,
            diasLaborados: $base->diasLaborados,
            sdi: $base->sdi,
            rfc: $base->rfc,
            curp: $base->curp,
            raw: $base->raw,
            cuotaTotal: $cuotaTotal !== null ? round($cuotaTotal, 2) : null,
            cuotasDetalle: $cuotasDetalle,
        );
    }

    private function mergeEmployeeRows(\App\Services\Compulsa\Dto\EmployeeRow $existing, \App\Services\Compulsa\Dto\EmployeeRow $new): \App\Services\Compulsa\Dto\EmployeeRow
    {
        $mergedDias = ($existing->diasLaborados ?? 0) + ($new->diasLaborados ?? 0);
        $latestSdi = ($new->sdi !== null && (float) $new->sdi > 0) ? $new->sdi : $existing->sdi;

        $mergedCuotaTotal = null;
        if ($existing->cuotaTotal !== null || $new->cuotaTotal !== null) {
            $mergedCuotaTotal = round((float) ($existing->cuotaTotal ?? 0) + (float) ($new->cuotaTotal ?? 0), 2);
        }

        $mergedCuotasDetalle = [];
        if (! empty($existing->cuotasDetalle) || ! empty($new->cuotasDetalle)) {
            $exDet = $existing->cuotasDetalle;
            $newDet = $new->cuotasDetalle;

            $total = round((float) ($exDet['total'] ?? 0) + (float) ($newDet['total'] ?? 0), 2);
            $patronal = round((float) ($exDet['patronal'] ?? 0) + (float) ($newDet['patronal'] ?? 0), 2);
            $obrera = round((float) ($exDet['obrera'] ?? 0) + (float) ($newDet['obrera'] ?? 0), 2);

            $exRamas = $exDet['desglose'] ?? [];
            $newRamas = $newDet['desglose'] ?? [];
            $allBranchKeys = array_unique(array_merge(array_keys($exRamas), array_keys($newRamas)));

            $mergedDesglose = [];
            foreach ($allBranchKeys as $k) {
                if ($k === 'numero_credito') {
                    $mergedDesglose[$k] = $newRamas[$k] ?? $exRamas[$k] ?? null;
                    continue;
                }
                $mergedDesglose[$k] = round((float) ($exRamas[$k] ?? 0) + (float) ($newRamas[$k] ?? 0), 2);
            }

            $mergedCuotasDetalle = [
                'total' => $total,
                'patronal' => $patronal,
                'obrera' => $obrera,
                'desglose' => $mergedDesglose,
            ];
        }

        return new \App\Services\Compulsa\Dto\EmployeeRow(
            source: $existing->source,
            nss: $existing->nss,
            nombreCompleto: $new->nombreCompleto ?? $existing->nombreCompleto,
            diasLaborados: $mergedDias,
            sdi: $latestSdi,
            rfc: $new->rfc ?? $existing->rfc,
            curp: $new->curp ?? $existing->curp,
            raw: [],
            cuotaTotal: $mergedCuotaTotal,
            cuotasDetalle: $mergedCuotasDetalle,
        );
    }
}
