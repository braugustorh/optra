<?php

declare(strict_types=1);

namespace App\Exports\Compulsa;

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

/**
 * Exporta un resumen de la compulsa en una sola hoja.
 * Respeta la query filtrada de la tabla (filtros + búsqueda).
 */
class CompulsaSummaryExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithTitle, ShouldAutoSize
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
            ->orderBy('nombre_completo');
    }

    public function headings(): array
    {
        return [
            'NSS',
            'Empleado',
            'Días IMSS',
            'Días Nómina',
            'Δ Días',
            'SDI IMSS',
            'SDI Nómina',
            'Δ SDI',
            'Cuota IMSS',
            'Cuota Patrón',
            'Diferencia $',
            'Estatus',
            'Alertas',
            'Observaciones',
        ];
    }

    public function map($record): array
    {
        /** @var CalculationRecord $record */
        $diffDias = ($record->dias_nomina ?? $record->dias_sua) !== null && $record->dias_imss !== null
            ? ($record->dias_nomina ?? $record->dias_sua) - $record->dias_imss
            : null;

        $sdiPatron = $record->sdi_nomina ?? $record->sdi_sua;
        $diffSdi = $sdiPatron !== null && $record->sdi_imss !== null
            ? round((float) $sdiPatron - (float) $record->sdi_imss, 4)
            : null;

        return [
            $record->nss,
            $record->nombre_completo,
            $record->dias_imss,
            $record->dias_nomina,
            $diffDias,
            $record->sdi_imss !== null ? (float) $record->sdi_imss : null,
            $record->sdi_nomina !== null ? (float) $record->sdi_nomina : null,
            $diffSdi,
            (float) $record->cuota_imss,
            (float) $record->cuota_patron,
            (float) $record->diferencia_total,
            $record->estatus_conciliacion?->getLabel() ?? '',
            implode(', ', $record->alertas_json ?? []),
            $record->observaciones,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
        ]);

        return [];
    }

    public function title(): string
    {
        $tipo = $this->calculation->tipo_periodo?->value === 'bimestral' ? 'EBA' : 'EMA';

        return sprintf('%s %d-%02d', $tipo, $this->calculation->ejercicio, $this->calculation->mes_bimestre);
    }
}
