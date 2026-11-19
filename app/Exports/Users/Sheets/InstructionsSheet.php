<?php

declare(strict_types=1);

namespace App\Exports\Users\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InstructionsSheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize
{
    public function array(): array
    {
        return [
            ['Guía de Llenado — Plantilla de Importación Masiva de Colaboradores'],
            [],
            ['1. Reglas Generales'],
            ['Encabezados en color verde (con *)', 'Son campos obligatorios para dar de alta al colaborador.'],
            ['Encabezados en color gris', 'Son campos opcionales; pueden dejarse en blanco si no se cuenta con el dato.'],
            ['No modifique los encabezados', 'Conserve la fila 1 de la hoja "Colaboradores" exactamente igual para que el sistema reconozca las columnas.'],
            ['Catálogo Organizacional', 'Consulte la hoja "Catálogo Organizacional" para copiar los nombres exactos de Sede, Razón Social, Departamento y Puesto.'],
            [],
            ['2. Formatos y Reglas por Campo'],
            ['CURP *', '18 caracteres alfanuméricos únicos. Se convertirá automáticamente a mayúsculas.'],
            ['RFC *', '12 o 13 caracteres alfanuméricos únicos. Se convertirá automáticamente a mayúsculas.'],
            ['NSS / IMSS *', 'Exactamente 11 dígitos numéricos sin guiones ni espacios (se conservan ceros a la izquierda).'],
            ['Fechas (Nacimiento / Ingreso)', 'Formato AAAA-MM-DD (ej. 1995-08-24) o DD/MM/AAAA (ej. 24/08/1995) o fecha nativa de Excel.'],
            ['Teléfonos (Personal / Emergencia)', 'Exactamente 10 dígitos numéricos sin espacios ni guiones.'],
            ['Código Postal', '5 dígitos numéricos (ej. 37000).'],
            ['Lugar de Nacimiento vs Domicilio', 'Escriba el nombre del Estado y Municipio/Ciudad (ej. Guanajuato, León); el sistema los homologa automáticamente con el catálogo SEPOMEX.'],
            ['Correo Electrónico (Acceso) *', 'Correo único de acceso al sistema (ej. juan.perez@optra o correo corporativo).'],
            ['Contraseña Temporal', 'Si se deja vacío, el sistema asignará automáticamente "#Optra{Número de Empleado}." y solicitará cambio obligatorio en el primer inicio de sesión.'],
            ['Rol', 'Si se deja vacío, se asignará automáticamente el rol "Colaborador". Solo usuarios autorizados pueden asignar otros roles.'],
            [],
            ['3. Equivalencias para "Tiempo en el Puesto"'],
            ['lt_6m', 'Menos de 6 meses'],
            ['6m_1y', 'Entre 6 meses y 1 año'],
            ['1_4y', 'Entre 1 a 4 años'],
            ['5_9y', 'Entre 5 a 9 años'],
            ['10_14y', 'Entre 10 a 14 años'],
            ['15_19y', 'Entre 15 a 19 años'],
            ['20_24y', 'Entre 20 a 24 años'],
            ['25_plus', '25 años o más'],
            [],
            ['4. Ejemplo de Registro Válido (Referencia)'],
            ['Nombre(s)', 'JUAN CARLOS'],
            ['Primer Apellido', 'PEREZ'],
            ['Segundo Apellido', 'LOPEZ'],
            ['CURP', 'PELJ950824HGTLRN01'],
            ['Sexo', 'Masculino'],
            ['Fecha de Nacimiento', '1995-08-24'],
            ['Nacionalidad', 'Mexicana'],
            ['País de Nacimiento', 'México'],
            ['Estado de Nacimiento', 'Guanajuato'],
            ['Ciudad de Nacimiento', 'León'],
            ['Número de Empleado', '60999'],
            ['Sede / Departamento / Puesto', 'Tomar nombres exactos de la hoja "Catálogo Organizacional"'],
            ['RFC', 'PELJ950824ABC'],
            ['NSS / IMSS', '12999501234'],
            ['Fecha de Ingreso', '2026-01-15'],
            ['Correo Electrónico', 'juan.perez@optra'],
            ['Contraseña Temporal', '(Dejar vacío para generar #Optra60999.)'],
            ['Rol', 'Colaborador'],
        ];
    }

    public function title(): string
    {
        return 'Guía de Llenado';
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F766E']],
        ]);

        foreach ([3, 9, 21, 31] as $sectionRow) {
            $sheet->mergeCells("A{$sectionRow}:B{$sectionRow}");
            $sheet->getStyle("A{$sectionRow}")->applyFromArray([
                'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '0F172A']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            ]);
        }

        $sheet->getStyle('A4:A48')->getFont()->setBold(true);

        return [];
    }
}
