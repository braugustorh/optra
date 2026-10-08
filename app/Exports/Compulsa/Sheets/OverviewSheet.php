<?php

declare(strict_types=1);

namespace App\Exports\Compulsa\Sheets;

use App\Models\Compulsa\Calculation;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OverviewSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    public function __construct(private readonly Calculation $calculation)
    {
    }

    public function array(): array
    {
        $c = $this->calculation;

        return [
            ['Compulsa IMSS · Resumen'],
            [],
            ['Ejercicio', $c->ejercicio],
            ['Período', $c->periodLabel()],
            ['Tipo', $c->tipo_periodo?->getLabel()],
            ['Razón Social', $c->razonSocial?->name],
            ['Registro patronal', $c->razonSocial?->registro_patronal],
            ['Estado', $c->estado?->getLabel()],
            ['Procesado en', optional($c->procesado_en ?? $c->updated_at)->format('d/m/Y H:i')],
            [],
            ['— Totales del cálculo —'],
            ['Total empleados', (int) $c->total_empleados],
            ['Con inconsistencias', (int) $c->empleados_con_diferencias],
            ['Total cuota IMSS', (float) $c->total_cuota_imss],
            ['Total cuota Patrón', (float) $c->total_cuota_patron],
            ['Total de diferencias', (float) $c->total_diferencia],
            [],
            ['— Parámetros aplicados —'],
            ['Prima de riesgo', (float) $c->prima_riesgo_aplicada],
            ['UMA aplicada', (float) $c->valor_uma_aplicado],
            ['Salario mínimo', (float) $c->salario_minimo_aplicado],
            ['Tope UMAs', (float) $c->tope_uma_veces_aplicado],
            ['UMI aplicada', $c->meta['valor_umi_aplicado'] ?? null],
        ];
    }

    public function title(): string
    {
        return 'Resumen';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
        ]);
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A11')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']],
        ]);
        $sheet->getStyle('A18')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']],
        ]);
        $sheet->getStyle('A3:A23')->getFont()->setBold(true);

        return [];
    }
}
