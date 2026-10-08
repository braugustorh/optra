<?php

declare(strict_types=1);

namespace App\Exports\Compulsa\Sheets;

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

class DetailSheet implements FromQuery, WithHeadings, WithMapping, WithTitle, WithStyles, ShouldAutoSize
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
            'NSS', 'Empleado', 'RFC', 'CURP',
            'Días IMSS', 'Días SUA', 'Días Nómina',
            'SDI IMSS', 'SDI SUA', 'SDI Nómina', 'SDI Topado',
            'Cuota IMSS', 'Cuota Patrón', 'Diferencia $',
            'Estatus', 'Supera 25 UMAs',
            'Presente IMSS', 'Presente SUA', 'Presente Nómina',
            'Alertas', 'Observaciones',
        ];
    }

    public function map($record): array
    {
        /** @var CalculationRecord $record */
        return [
            $record->nss,
            $record->nombre_completo,
            $record->rfc,
            $record->curp,
            $record->dias_imss,
            $record->dias_sua,
            $record->dias_nomina,
            $record->sdi_imss !== null ? (float) $record->sdi_imss : null,
            $record->sdi_sua !== null ? (float) $record->sdi_sua : null,
            $record->sdi_nomina !== null ? (float) $record->sdi_nomina : null,
            $record->sdi_topado !== null ? (float) $record->sdi_topado : null,
            (float) $record->cuota_imss,
            (float) $record->cuota_patron,
            (float) $record->diferencia_total,
            $record->estatus_conciliacion?->getLabel() ?? '',
            $record->supera_tope_uma ? 'Sí' : 'No',
            $record->presente_imss ? 'Sí' : 'No',
            $record->presente_sua ? 'Sí' : 'No',
            $record->presente_nomina ? 'Sí' : 'No',
            implode(', ', $record->alertas_json ?? []),
            $record->observaciones,
        ];
    }

    public function title(): string
    {
        return 'Detalle';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:U1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
        ]);

        return [];
    }
}
