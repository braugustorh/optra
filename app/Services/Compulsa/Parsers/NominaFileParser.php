<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Parsers;

use App\Enums\FileSource;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Parser para el archivo de Nómina interna.
 * Soporta archivos con múltiples hojas, fórmulas de integración y alias ampliados.
 */
class NominaFileParser extends AbstractExcelParser
{
    public function source(): FileSource
    {
        return FileSource::Nomina;
    }

    protected function requiredColumns(): array
    {
        return ['nss', 'nombre', 'sdi'];
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
                    in_array('sdi', $normalized, true) ||
                    in_array('sbc', $normalized, true)
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
            'sdi' => [
                'sdi',
                'sbc',
                'sbc formulado',
                'sdi calculado',
                'salario diario integrado',
                'salario base de cotizacion',
                'sd',
            ],
            'dias' => [
                'dias',
                'dias laborados',
                'dias trabajados',
                'dias cotizados',
            ],
            'rfc' => ['rfc'],
            'curp' => ['curp'],
        ];
    }
}
