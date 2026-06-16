<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sede;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $mapping = [
            'DELTA' => [
                'Auditoría Interna', 'Liquidación y Boletos AXR', 'Mantenimiento a la Tecnología Abordo',
                'Contraloría', 'Gestoría de Análisis', 'Seguro Interno', 'Diesel',
                'Planeación y Operación de Servicios', 'Capital Humano'
            ],
            'RIO' => [
                'Dirección General', 'Administración', 'Almacén y Abastecimiento',
                'Mantenimiento General', 'Productividad'
            ],
            'METROPOLITANA' => [
                'Liquidación y Boletos Suburbano e Inter', 'Mantenimiento General'
            ],
            'MOREÑA' => ['Productividad AXR'],
            'ECHEVESTE' => ['Productividad AXR'],
            '21 DE MARZO' => ['Productividad AXR'],
            'BELLAVISTA' => ['Almacén y Abastecimiento', 'Productividad AXR'],
            'ESCOBEDO' => ['Productividad AXR'],
            'HILAMAS' => ['Productividad AXR'],
            'MERCED' => ['Operaciones'],
            'SANTA RITA' => ['Operaciones']
        ];

        foreach ($mapping as $sedeName => $depts) {
            $sede = Sede::where('name', $sedeName)->first();
            if ($sede) {
                foreach ($depts as $deptName) {
                    Department::updateOrCreate(
                        ['name' => $deptName, 'sede_id' => $sede->id],
                        ['status' => 1]
                    );
                }
            }
        }
    }
}
