<?php

declare(strict_types=1);

namespace App\Exports\Users;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserImportFailuresExport implements
    FromArray,
    WithHeadings,
    WithTitle,
    WithStyles,
    ShouldAutoSize
{
    /**
     * @param  array<int, array{row: int, identifier: string, errors: array<int, string>}>  $failures
     */
    public function __construct(
        private readonly array $failures,
    ) {
    }

    public function headings(): array
    {
        return [
            'Fila en Plantilla',
            'Colaborador / Identificador',
            'Núm. Errores',
            'Motivo(s) del Rechazo',
        ];
    }

    public function array(): array
    {
        return array_map(static fn (array $failure): array => [
            'Fila ' . $failure['row'],
            $failure['identifier'],
            count($failure['errors']),
            implode(' | ', $failure['errors']),
        ], $this->failures);
    }

    public function title(): string
    {
        return 'Errores de Importación';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->freezePane('A2');

        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '991B1B'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        $highestRow = $sheet->getHighestRow();
        if ($highestRow > 1) {
            $sheet->getStyle("D2:D{$highestRow}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("A2:C{$highestRow}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
        }

        return [];
    }
}
