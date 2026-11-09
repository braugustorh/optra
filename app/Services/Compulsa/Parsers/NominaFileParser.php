<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Parsers;

use App\Enums\FileSource;
use App\Services\Compulsa\Dto\EmployeeRow;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

/**
 * Parser para el archivo de Nómina interna.
 * Soporta archivos con múltiples períodos/semanas por trabajador (consolidación multi-período),
 * jerarquía salarial estricta (prioridad SBC sobre SDI fijo) y desglose de incidencias
 * (días laborados, faltas, incapacidades, vacaciones) conforme a la LSS.
 */
class NominaFileParser extends AbstractExcelParser
{
    public function source(): FileSource
    {
        return FileSource::Nomina;
    }

    protected function requiredColumns(): array
    {
        return ['nss', 'nombre'];
    }

    protected function selectSheetIndex(Spreadsheet $spreadsheet): int|string
    {
        $sheetNames = $spreadsheet->getSheetNames();

        // 1. Si hay una hoja llamada explícitamente con 'INTEGRACION' o 'BASE', darle prioridad
        foreach ($sheetNames as $idx => $name) {
            if (stripos($name, 'INTEGRACION') !== false || stripos($name, 'BASE') !== false) {
                return $idx;
            }
        }

        // 2. Escanear las hojas para encontrar la que contenga encabezados de nómina
        foreach ($sheetNames as $idx => $name) {
            $sheet = $spreadsheet->getSheet($idx);
            $highestRow = min(15, $sheet->getHighestDataRow());
            $highestCol = $sheet->getHighestDataColumn();

            for ($r = 1; $r <= $highestRow; $r++) {
                $row = $sheet->rangeToArray("A{$r}:{$highestCol}{$r}", null, false, false)[0] ?? [];
                $normalized = array_map(fn ($v) => $this->normalizeHeader((string) ($v ?? '')), $row);

                if (in_array('nss', $normalized, true) && (
                    in_array('nombre completo', $normalized, true) ||
                    in_array('nombre', $normalized, true) ||
                    in_array('empleado', $normalized, true) ||
                    in_array('sbc', $normalized, true) ||
                    in_array('sdi', $normalized, true)
                )) {
                    return $idx;
                }
            }
        }

        return 0;
    }

    protected function columnAliases(): array
    {
        return [
            'nss' => [
                'nss',
                'numero de seguridad social',
                'no seguridad social',
                'no de seguridad social',
                'num seguridad social',
            ],
            'nombre' => [
                'nombre completo',
                'nombre',
                'nombre del empleado',
                'nombre del trabajador',
                'empleado',
            ],
            // Jerarquía Salarial: Prioridad absoluta SBC > SDI Variable > SDI Fijo > SD
            'sbc' => [
                'sbc',
                'sbc formulado',
                'salario base de cotizacion',
                'salario base cotizacion',
                'sbc integrado',
            ],
            'sdi_variable' => [
                'sdi variable',
                'sbc variable',
                'variable',
                'sdi var',
                'sbc var',
                'parte variable',
            ],
            'sdi' => [
                'sdi',
                'sdi calculado',
                'salario diario integrado',
                'sdi fijo',
            ],
            'sd' => [
                'sd',
                'sueldo diario',
                'salario diario',
            ],
            'dias' => [
                'dias laborados',
                'dias trabajados',
                'dias cotizados',
                'dias pagados',
                'dias',
            ],
            'faltas' => [
                'dias de faltas',
                'dias faltas',
                'faltas',
                'ausentismos',
                'dias ausentismo',
                'falta',
                'dias de falta',
            ],
            'incapacidades' => [
                'dias de incapacidad',
                'dias incapacidad',
                'incapacidades',
                'incapacidad',
                'dias de incapacidades',
            ],
            'vacaciones' => [
                'dias de vacaciones',
                'dias vacaciones',
                'vacaciones',
                'vacacion',
                'dias vacacion',
            ],
            'fecha_ingreso' => [
                'fecha_ingreso',
                'fecha ingreso',
                'fecha alta',
                'fecha de ingreso',
            ],
            'fecha_reingreso' => [
                'fecha_reingreso',
                'fecha reingreso',
                'fecha de reingreso',
            ],
            'fecha_baja' => [
                'fecha_baja',
                'fecha baja',
                'fecha de baja',
            ],
            'rfc' => ['rfc'],
            'curp' => ['curp'],
        ];
    }

    protected function resolveColumns(array $headers): array
    {
        $columns = parent::resolveColumns($headers);

        // Validar que al menos exista una columna salarial válida
        if (($columns['sbc'] ?? null) === null &&
            ($columns['sdi'] ?? null) === null &&
            ($columns['sd'] ?? null) === null) {
            throw new RuntimeException(sprintf(
                'No se localizó ninguna columna salarial (SBC, SDI o SD) en el archivo de nómina. Encabezados detectados: [%s]',
                implode(' | ', array_filter(array_map('strval', $headers))),
            ));
        }

        return $columns;
    }

    protected function mapRow(array $row, array $columns): ?EmployeeRow
    {
        $nssRaw = $columns['nss'] !== null ? ($row[$columns['nss']] ?? null) : null;
        $nss = $this->cleanNss($nssRaw);

        if ($nss === null) {
            return null;
        }

        // Extracción salarial con jerarquía estricta SBC sobre SDI fijo
        $sbc = $this->cleanFloat($this->pick($row, $columns, 'sbc'));
        $sdiFijo = $this->cleanFloat($this->pick($row, $columns, 'sdi'));
        $sdiVar = $this->cleanFloat($this->pick($row, $columns, 'sdi_variable'));
        $sd = $this->cleanFloat($this->pick($row, $columns, 'sd'));

        if ($sbc !== null && $sbc > 0) {
            $sdiFinal = round($sbc, 2);
        } elseif ($sdiFijo !== null && $sdiFijo > 0 && $sdiVar !== null && $sdiVar > 0) {
            $sdiFinal = round($sdiFijo + $sdiVar, 2);
        } elseif ($sdiFijo !== null && $sdiFijo > 0) {
            $sdiFinal = round($sdiFijo, 2);
        } else {
            $sdiFinal = $sd !== null ? round($sd, 2) : null;
        }

        // Extracción de incidencias
        $diasLab = $this->cleanInt($this->pick($row, $columns, 'dias')) ?? 0;
        $faltas = $this->cleanInt($this->pick($row, $columns, 'faltas')) ?? 0;
        $incapacidades = $this->cleanInt($this->pick($row, $columns, 'incapacidades')) ?? 0;
        $vacaciones = $this->cleanInt($this->pick($row, $columns, 'vacaciones')) ?? 0;

        $fechaIngreso = $this->cleanDate($this->pick($row, $columns, 'fecha_ingreso'));
        $fechaReingreso = $this->cleanDate($this->pick($row, $columns, 'fecha_reingreso'));
        $fechaBaja = $this->cleanDate($this->pick($row, $columns, 'fecha_baja'));
        $fechaEfectiva = $fechaReingreso ?? $fechaIngreso;

        return new EmployeeRow(
            source: $this->source(),
            nss: $nss,
            nombreCompleto: $this->cleanString($this->pick($row, $columns, 'nombre')),
            diasLaborados: $diasLab,
            sdi: $sdiFinal,
            rfc: $this->cleanString($this->pick($row, $columns, 'rfc')),
            curp: $this->cleanString($this->pick($row, $columns, 'curp')),
            raw: [],
            cuotaTotal: null,
            cuotasDetalle: [],
            diasFaltas: $faltas,
            diasIncapacidad: $incapacidades,
            diasVacaciones: $vacaciones,
            sdiFijo: $sdiFijo,
            sdiVariable: $sdiVar,
            fechaIngreso: $fechaEfectiva,
            fechaBaja: $fechaBaja,
        );
    }

    public function parseInChunks(string $absolutePath, int $chunkSize, callable $onChunk): void
    {
        /** @var Collection<string, EmployeeRow> $consolidated */
        $consolidated = collect();

        // 1. Leer todas las filas y consolidar multi-período por NSS en memoria
        parent::parseInChunks($absolutePath, $chunkSize, function (Collection $chunk) use (&$consolidated): void {
            foreach ($chunk as $row) {
                /** @var EmployeeRow $row */
                $nss = $row->nss;
                if (! $consolidated->has($nss)) {
                    $consolidated->put($nss, $row);
                } else {
                    /** @var EmployeeRow $existing */
                    $existing = $consolidated->get($nss);
                    $consolidated->put($nss, $this->mergeNominaRows($existing, $row));
                }
            }
        });

        // 2. Emitir los colaboradores consolidados en bloques hacia el store action
        $consolidated->values()->chunk($chunkSize)->each(function (Collection $chunk) use ($onChunk): void {
            $onChunk($chunk);
        });
    }

    private function mergeNominaRows(EmployeeRow $existing, EmployeeRow $new): EmployeeRow
    {
        // Sumar incidencias multi-período
        $mergedDiasLaborados = ($existing->diasLaborados ?? 0) + ($new->diasLaborados ?? 0);
        $mergedFaltas = ($existing->diasFaltas ?? 0) + ($new->diasFaltas ?? 0);
        $mergedIncapacidades = ($existing->diasIncapacidad ?? 0) + ($new->diasIncapacidad ?? 0);
        $mergedVacaciones = ($existing->diasVacaciones ?? 0) + ($new->diasVacaciones ?? 0);

        // Tarifa salarial: Tomar el SBC reportado vigente (último no nulo ni cero). PROHIBIDO sumar salarios.
        $latestSdi = ($new->sdi !== null && (float) $new->sdi > 0) ? $new->sdi : $existing->sdi;
        $latestSdiFijo = ($new->sdiFijo !== null && (float) $new->sdiFijo > 0) ? $new->sdiFijo : $existing->sdiFijo;
        $latestSdiVar = ($new->sdiVariable !== null && (float) $new->sdiVariable >= 0) ? $new->sdiVariable : $existing->sdiVariable;

        // Fechas de movimiento: si el nuevo período tiene fecha real válida (descartando 1899-12-31 o default)
        $latestFechaIngreso = $this->pickValidDate($new->fechaIngreso, $existing->fechaIngreso);
        $latestFechaBaja = $this->pickValidDate($new->fechaBaja, $existing->fechaBaja);

        return new EmployeeRow(
            source: $this->source(),
            nss: $existing->nss,
            nombreCompleto: $new->nombreCompleto ?? $existing->nombreCompleto,
            diasLaborados: $mergedDiasLaborados,
            sdi: $latestSdi,
            rfc: $new->rfc ?? $existing->rfc,
            curp: $new->curp ?? $existing->curp,
            raw: [],
            cuotaTotal: null,
            cuotasDetalle: [],
            diasFaltas: $mergedFaltas,
            diasIncapacidad: $mergedIncapacidades,
            diasVacaciones: $mergedVacaciones,
            sdiFijo: $latestSdiFijo,
            sdiVariable: $latestSdiVar,
            fechaIngreso: $latestFechaIngreso,
            fechaBaja: $latestFechaBaja,
        );
    }

    private function cleanDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $s = trim((string) $value);
        if ($s === '' || str_starts_with($s, '1899') || str_starts_with($s, '1900') || $s === '-') {
            return null;
        }

        // Si es fecha serial de Excel numérica (ej. 46279)
        if (is_numeric($s)) {
            $ts = ((int) $s - 25569) * 86400;
            if ($ts > 0) {
                return gmdate('Y-m-d', $ts);
            }
        }

        // Formatos tipo YYYY-MM-DD o DD/MM/YYYY
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $s, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})/', $s, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            return "{$m[3]}-{$month}-{$day}";
        }

        return $s;
    }

    private function pickValidDate(?string $newDate, ?string $existingDate): ?string
    {
        if ($newDate !== null && $newDate !== '' && ! str_starts_with($newDate, '1899')) {
            return $newDate;
        }

        return ($existingDate !== null && $existingDate !== '' && ! str_starts_with($existingDate, '1899'))
            ? $existingDate
            : null;
    }
}

