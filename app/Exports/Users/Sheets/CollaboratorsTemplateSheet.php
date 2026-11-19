<?php

declare(strict_types=1);

namespace App\Exports\Users\Sheets;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Spatie\Permission\Models\Role;

class CollaboratorsTemplateSheet implements
    FromArray,
    WithHeadings,
    WithTitle,
    WithStyles,
    WithColumnFormatting,
    WithEvents,
    ShouldAutoSize
{
    public const HEADINGS = [
        'Nombre(s) *',
        'Primer Apellido *',
        'Segundo Apellido',
        'CURP *',
        'Sexo',
        'Fecha de Nacimiento (AAAA-MM-DD) *',
        'Nacionalidad',
        'País de Nacimiento',
        'Estado de Nacimiento',
        'Ciudad de Nacimiento',
        'Discapacidad',
        'Estado Civil',
        'Estado (Domicilio)',
        'Ciudad (Domicilio)',
        'Colonia',
        'Código Postal',
        'Dirección (Calle y Número)',
        'Teléfono Personal (10 dígitos)',
        'Contacto de Emergencia',
        'Parentesco Emergencia',
        'Teléfono Emergencia (10 dígitos)',
        'Escolaridad',
        'Área de Estudio',
        'Número de Empleado *',
        'Sede *',
        'Razón Social',
        'Departamento *',
        'Puesto *',
        'Tiempo en el Puesto',
        'Marcas Internas (SI/NO)',
        'Tipo de Contratación',
        'Tipo de Personal',
        'Rota Turnos (SI/NO)',
        'Turno de Trabajo',
        'Años de Experiencia',
        'RFC *',
        'NSS / IMSS (11 dígitos) *',
        'Fecha de Ingreso (AAAA-MM-DD)',
        'Correo Electrónico (Acceso) *',
        'Correo Alterno (Personal)',
        'Contraseña Temporal',
        'Rol',
    ];

    public function __construct(
        private readonly ?User $actor = null,
    ) {
    }

    public function array(): array
    {
        return [];
    }

    public function headings(): array
    {
        return self::HEADINGS;
    }

    public function title(): string
    {
        return 'Colaboradores';
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_TEXT,  // CURP
            'P' => NumberFormat::FORMAT_TEXT,  // Código Postal
            'R' => NumberFormat::FORMAT_TEXT,  // Teléfono Personal
            'U' => NumberFormat::FORMAT_TEXT,  // Teléfono Emergencia
            'X' => NumberFormat::FORMAT_TEXT,  // Número de Empleado
            'AJ' => NumberFormat::FORMAT_TEXT, // RFC
            'AK' => NumberFormat::FORMAT_TEXT, // NSS / IMSS
            'AO' => NumberFormat::FORMAT_TEXT, // Contraseña Temporal
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $totalColumns = count(self::HEADINGS);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->freezePane('A2');

        for ($colIndex = 1; $colIndex <= $totalColumns; $colIndex++) {
            $columnLetter = Coordinate::stringFromColumnIndex($colIndex);
            $coordinate = "{$columnLetter}1";
            $headingText = (string) $sheet->getCell($coordinate)->getValue();
            $isRequired = str_contains($headingText, '*');

            $sheet->getStyle($coordinate)->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $isRequired ? '0F766E' : '334155'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $dropdowns = [
                    'E' => ['Masculino', 'Femenino', 'Otro'],
                    'G' => ['Mexicana', 'Extranjera'],
                    'K' => ['Ninguna', 'Auditiva', 'Visual', 'Motriz', 'Intelectual', 'Múltiple', 'Otra'],
                    'L' => ['Soltero', 'Casado', 'Divorciado', 'Viudo', 'Union Libre'],
                    'V' => ['Sin Estudios', 'Primaria', 'Secundaria', 'Preparatoria', 'TSU', 'Licenciatura', 'Maestría', 'Doctorado', 'otro'],
                    'W' => ['Humanidades', 'Ciencias Sociales', 'Ciencias Exactas', 'Ingeniería', 'Ciencias de la Salud', 'Artes', 'Administración', 'Tecnología', 'Otra'],
                    'AC' => ['lt_6m', '6m_1y', '1_4y', '5_9y', '10_14y', '15_19y', '20_24y', '25_plus'],
                    'AD' => ['NO', 'SI'],
                    'AE' => ['Proyecto', 'Indeterminado', 'Temporal', 'Honorarios'],
                    'AF' => ['Confianza', 'Sindicalizado', 'Ninguno'],
                    'AG' => ['NO', 'SI'],
                    'AH' => ['Diurno', 'Nocturno', 'Mixto'],
                    'AP' => $this->allowedRoleOptions(),
                ];

                foreach ($dropdowns as $column => $options) {
                    if ($options !== []) {
                        $this->applyDropdown($sheet, $column, $options);
                    }
                }
            },
        ];
    }

    /**
     * @param  array<int, string>  $options
     */
    private function applyDropdown(Worksheet $sheet, string $column, array $options, int $startRow = 2, int $endRow = 300): void
    {
        $formula = '"' . implode(',', $options) . '"';

        // Excel rechaza fórmulas de lista inline superiores a 255 caracteres.
        if (mb_strlen($formula) > 255) {
            return;
        }

        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_WARNING);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(false);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Valor no listado');
        $validation->setError('Selecciona un valor válido de la lista desplegable.');
        $validation->setFormula1($formula);

        $sheet->setDataValidation("{$column}{$startRow}:{$column}{$endRow}", $validation);
    }

    /**
     * @return array<int, string>
     */
    private function allowedRoleOptions(): array
    {
        if ($this->actor?->hasAnyRole(['Administrador', 'Super Administrador'])) {
            $roles = Role::query()->orderBy('name')->pluck('name')->all();
            if ($roles !== [] && mb_strlen('"' . implode(',', $roles) . '"') <= 255) {
                return $roles;
            }

            return ['Colaborador', 'Supervisor', 'Operativo', 'RH', 'RH Corp', 'Gerente', 'Director', 'Compulsa', 'Apoyo Expedientes', 'Reclutador', 'Reclutador Jr', 'Visor', 'Administrador'];
        }

        if ($this->actor?->hasRole('RH Corp')) {
            return ['Colaborador', 'Supervisor', 'Operativo', 'RH', 'RH Corp', 'Compulsa', 'Apoyo Expedientes', 'Reclutador Jr', 'Reclutador'];
        }

        return ['Colaborador', 'Supervisor', 'Operativo'];
    }
}
