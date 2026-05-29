<?php

namespace App\Exports;

use App\Models\Differences;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DifferencesExport implements FromCollection, WithHeadings, WithStyles
{
    protected $selectedRecords;

    public function __construct($selectedRecords = null)
    {
        $this->selectedRecords = $selectedRecords;
    }

    public function collection()
    {
        if ($this->selectedRecords) {
            return Differences::query()->whereIn('nombre', $this->selectedRecords)->get();
        }

        return Differences::all();
    }

    public function headings(): array
    {
        return ['Nombre', 'Suma File 1', 'Suma File 2', 'Diferencia'];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];
        $rowNumber = 2; // Comienza después de los encabezados

        foreach ($this->collection() as $record) {
            $diferencia = (float) $record->diferencia;

            if ($diferencia > 0) {
                $styles["D{$rowNumber}"] = [
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FCA5A5'] // Rojo claro
                    ]
                ];
            } elseif ($diferencia < 0) {
                $styles["D{$rowNumber}"] = [
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '86EFAC'] // Verde claro
                    ]
                ];
            } else {
                $styles["D{$rowNumber}"] = [
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FACF8E'] // Gris claro
                    ]
                ];
            }

            $rowNumber++;
        }

        // Estilo para encabezados
        $styles[1] = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D1D5DB']
            ]
        ];

        return $styles;
    }
}
