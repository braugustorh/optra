<?php

declare(strict_types=1);

namespace App\Exports\Compulsa\Sheets;

use App\Enums\ReconciliationStatus;
use App\Models\Compulsa\Calculation;
use App\Models\Compulsa\CalculationRecord;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AlertsSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(
        private readonly Builder $filteredQuery,
        private readonly Calculation $calculation,
    ) {
    }

    public function query(): Builder
    {
        $ids = $this->filteredQuery->pluck('id')->all();

        return CalculationRecord::query()
            ->whereIn('id', $ids)
            ->where('estatus_conciliacion', '!=', ReconciliationStatus::Ok->value)
            ->orderBy('estatus_conciliacion')
            ->orderBy('nombre_completo');
    }

    public function headings(): array
    {
        return [
            'Estatus',
            'NSS',
            'Empleado',
            'Días IMSS',
            'Días Nómina',
            'SDI IMSS',
            'SDI Nómina',
            'Diferencia $',
            'Alertas',
            'Observaciones',
        ];
    }

    public function map($record): array
    {
        /** @var CalculationRecord $record */
        return [
            $record->estatus_conciliacion?->getLabel() ?? '',
            $record->nss,
            $record->nombre_completo,
            $record->dias_imss,
            $record->dias_nomina,
            $record->sdi_imss !== null ? (float) $record->sdi_imss : null,
            $record->sdi_nomina !== null ? (float) $record->sdi_nomina : null,
            (float) $record->diferencia_total,
            implode(', ', $record->alertas_json ?? []),
            $record->observaciones,
        ];
    }

    public function title(): string
    {
        return 'Alertas';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B91C1C']],
        ]);

        return [];
    }
}
