<?php

declare(strict_types=1);

namespace App\Exports\Users\Sheets;

use App\Models\Sede;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrganizationalCatalogSheet implements
    FromArray,
    WithHeadings,
    WithTitle,
    WithStyles,
    ShouldAutoSize
{
    public function __construct(
        private readonly ?User $actor = null,
    ) {
    }

    public function headings(): array
    {
        return [
            'Sede',
            'Razón Social Asociada',
            'Departamento',
            'Puesto',
        ];
    }

    public function array(): array
    {
        $query = Sede::query()
            ->where('status', true)
            ->with([
                'razonSocials' => fn ($q) => $q->orderBy('name'),
                'department' => fn ($q) => $q->where('status', true)->orderBy('name'),
                'department.position' => fn ($q) => $q->where('status', true)->orderBy('name'),
            ])
            ->orderBy('name');

        if ($this->actor && $this->actor->hasRole('RH') && ! $this->actor->hasAnyRole(['RH Corp', 'Administrador', 'Super Administrador'])) {
            $query->where('id', $this->actor->sede_id);
        }

        $rows = [];
        foreach ($query->get() as $sede) {
            $razones = $sede->razonSocials->pluck('name')->implode(' | ');
            if ($razones === '') {
                $razones = 'Sin razón social asociada';
            }

            if ($sede->department->isEmpty()) {
                $rows[] = [$sede->name, $razones, '', ''];
                continue;
            }

            foreach ($sede->department as $department) {
                if ($department->position->isEmpty()) {
                    $rows[] = [$sede->name, $razones, $department->name, ''];
                    continue;
                }

                foreach ($department->position as $position) {
                    $rows[] = [
                        $sede->name,
                        $razones,
                        $department->name,
                        $position->name,
                    ];
                }
            }
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Catálogo Organizacional';
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
                'startColor' => ['rgb' => '1E3A8A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }
}
