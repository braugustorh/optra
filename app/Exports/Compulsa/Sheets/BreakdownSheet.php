<?php

declare(strict_types=1);

namespace App\Exports\Compulsa\Sheets;

use App\Exports\Compulsa\Support\BranchLabels;
use App\Models\Compulsa\Calculation;
use App\Models\Compulsa\CalculationRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Formato "long" (una fila por empleado × rama).
 * Facilita pivotear en Excel.
 */
class BreakdownSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private readonly Builder $filteredQuery,
        private readonly Calculation $calculation,
    ) {
    }

    public function collection(): Collection
    {
        $ids = $this->filteredQuery->pluck('id')->all();
        $rows = collect();

        CalculationRecord::query()
            ->whereIn('id', $ids)
            ->orderBy('nombre_completo')
            ->chunk(500, function ($chunk) use ($rows): void {
                foreach ($chunk as $record) {
                    $desglose = $record->desglose_cuotas ?? [];
                    $imssDesglose = $desglose['imss']['desglose'] ?? [];
                    $patronDesglose = $desglose['patron']['desglose'] ?? [];
                    $allKeys = array_keys(array_merge($imssDesglose, $patronDesglose));

                    if ($allKeys === []) {
                        continue;
                    }

                    foreach ($allKeys as $key) {
                        $imssVal = (float) ($imssDesglose[$key] ?? 0);
                        $patronVal = (float) ($patronDesglose[$key] ?? 0);
                        $rows->push([
                            $record->nss,
                            $record->nombre_completo,
                            BranchLabels::for($key),
                            round($imssVal, 2),
                            round($patronVal, 2),
                            round($patronVal - $imssVal, 2),
                        ]);
                    }
                }
            });

        return $rows;
    }

    public function headings(): array
    {
        return ['NSS', 'Empleado', 'Concepto', 'IMSS $', 'Patrón $', 'Diferencia $'];
    }

    public function title(): string
    {
        return 'Desglose por rama';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4338CA']],
        ]);

        return [];
    }
}
