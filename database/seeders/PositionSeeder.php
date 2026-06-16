<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Position;
use App\Models\Department;
use App\Models\Sede;

class PositionSeeder extends Seeder
{
    public function run()
    {
        // --- AUXILIAR PARA OBTENER DEPARTAMENTOS ---
        $getDept = function($sede, $dept) {
            $s = Sede::where('name', $sede)->first();
            if (!$s) return null;
            return Department::where('name', $dept)->where('sede_id', $s->id)->first();
        };

        // --- NIVEL 1: ALTA DIRECCIÓN (RIO) ---
        $deptDG = $getDept('RIO', 'Dirección General');
        if (!$deptDG) return;

        $consejero = Position::updateOrCreate(
            ['name' => 'Consejero Delegado', 'department_id' => $deptDG->id],
            ['order' => 1, 'supervisor_id' => null, 'evaluation_grades' => '360', 'status' => 1]
        );

        // --- NIVEL 2: DIRECCIONES (RIO) ---
        $dirAdm = Position::updateOrCreate(['name' => 'Directora Administrativa', 'department_id' => $deptDG->id], ['order' => 2, 'supervisor_id' => $consejero->id, 'evaluation_grades' => '360', 'status' => 1]);
        $dirUN = Position::updateOrCreate(['name' => 'Director de Unidades de Negocio', 'department_id' => $deptDG->id], ['order' => 2, 'supervisor_id' => $consejero->id, 'evaluation_grades' => '360', 'status' => 1]);

        Position::updateOrCreate(['name' => 'Asistente Administrativo de Dirección Administrativa', 'department_id' => $deptDG->id], ['order' => 3, 'supervisor_id' => $dirAdm->id, 'evaluation_grades' => '360', 'status' => 1]);
        Position::updateOrCreate(['name' => 'Asistente Administrativo de Unidades de Negocio', 'department_id' => $deptDG->id], ['order' => 3, 'supervisor_id' => $dirUN->id, 'evaluation_grades' => '360', 'status' => 1]);

        // --- NIVEL 3: GERENCIAS ---
        $gAdm = Position::updateOrCreate(['name' => 'Gerente Administrativo', 'department_id' => $getDept('RIO', 'Administración')->id], ['order' => 3, 'supervisor_id' => $dirAdm->id, 'evaluation_grades' => '360', 'status' => 1]);
        $gAlm = Position::updateOrCreate(['name' => 'Gerente de Almacén y Abastecimiento', 'department_id' => $getDept('RIO', 'Almacén y Abastecimiento')->id], ['order' => 3, 'supervisor_id' => $dirUN->id, 'evaluation_grades' => '360', 'status' => 1]);
        $gMantGen = Position::updateOrCreate(['name' => 'Gerente General de Mantenimiento', 'department_id' => $getDept('RIO', 'Mantenimiento General')->id], ['order' => 3, 'supervisor_id' => $dirUN->id, 'evaluation_grades' => '360', 'status' => 1]);
        $gProd = Position::updateOrCreate(['name' => 'Gerente de Productividad', 'department_id' => $getDept('RIO', 'Productividad')->id], ['order' => 3, 'supervisor_id' => $dirUN->id, 'evaluation_grades' => '360', 'status' => 1]);

        $gCont = Position::updateOrCreate(['name' => 'Gerente de Contraloría', 'department_id' => $getDept('DELTA', 'Contraloría')->id], ['order' => 3, 'supervisor_id' => $dirAdm->id, 'evaluation_grades' => '360', 'status' => 1]);
        $gTI = Position::updateOrCreate(['name' => 'Gerente de TI Desarrollo y Datos', 'department_id' => $getDept('DELTA', 'Gestoría de Análisis')->id], ['order' => 3, 'supervisor_id' => $dirAdm->id, 'evaluation_grades' => '360', 'status' => 1]);
        $gSeg = Position::updateOrCreate(['name' => 'Gerente de Seguro Interno', 'department_id' => $getDept('DELTA', 'Seguro Interno')->id], ['order' => 3, 'supervisor_id' => $dirAdm->id, 'evaluation_grades' => '360', 'status' => 1]);
        $gDiesel = Position::updateOrCreate(['name' => 'Gerente de Diesel', 'department_id' => $getDept('DELTA', 'Diesel')->id], ['order' => 3, 'supervisor_id' => $dirUN->id, 'evaluation_grades' => '360', 'status' => 1]);
        $gPlan = Position::updateOrCreate(['name' => 'Gerente de Planeación y Operación de Servicios', 'department_id' => $getDept('DELTA', 'Planeación y Operación de Servicios')->id], ['order' => 3, 'supervisor_id' => $dirUN->id, 'evaluation_grades' => '360', 'status' => 1]);
        $gCH = Position::updateOrCreate(['name' => 'Gerente de Capital Humano', 'department_id' => $getDept('DELTA', 'Capital Humano')->id], ['order' => 3, 'supervisor_id' => $dirAdm->id, 'evaluation_grades' => '360', 'status' => 1]);

        // --- NIVEL 4: COORDINACIONES Y SUPERVISIONES ---
        $cAud = Position::updateOrCreate(['name' => 'Coordinador de Auditoría Interna', 'department_id' => $getDept('DELTA', 'Auditoría Interna')->id], ['order' => 3, 'supervisor_id' => $consejero->id, 'evaluation_grades' => '360', 'status' => 1]);
        Position::updateOrCreate(['name' => 'Auditor Interno', 'department_id' => $getDept('DELTA', 'Auditoría Interna')->id], ['order' => 4, 'supervisor_id' => $cAud->id, 'evaluation_grades' => '360', 'status' => 1]);

        $cLiqAXR = Position::updateOrCreate(['name' => 'Coordinador de Liquidación y Boletos AXR', 'department_id' => $getDept('DELTA', 'Liquidación y Boletos AXR')->id], ['order' => 4, 'supervisor_id' => $dirUN->id, 'evaluation_grades' => '360', 'status' => 1]);
        Position::updateOrCreate(['name' => 'Cajero', 'department_id' => $getDept('DELTA', 'Liquidación y Boletos AXR')->id], ['order' => 5, 'supervisor_id' => $cLiqAXR->id, 'evaluation_grades' => '360', 'status' => 1]);

        $cLiqSub = Position::updateOrCreate(['name' => 'Coordinador de Liquidación y Boletos Sub E Inter', 'department_id' => $getDept('METROPOLITANA', 'Liquidación y Boletos Suburbano e Inter')->id], ['order' => 4, 'supervisor_id' => $dirUN->id, 'evaluation_grades' => '360', 'status' => 1]);
        Position::updateOrCreate(['name' => 'Cajero', 'department_id' => $getDept('METROPOLITANA', 'Liquidación y Boletos Suburbano e Inter')->id], ['order' => 5, 'supervisor_id' => $cLiqSub->id, 'evaluation_grades' => '360', 'status' => 1]);

        // --- PRODUCTIVIDAD Y OPERADORES ---
        $sedes = Sede::all();
        foreach ($sedes as $sede) {
            $dProdAXR = Department::where('name', 'Productividad AXR')->where('sede_id', $sede->id)->first();
            if ($dProdAXR) {
                $sup = Position::updateOrCreate(
                    ['name' => "Supervisor de Productividad AXR {$sede->name}", 'department_id' => $dProdAXR->id],
                    ['order' => 4, 'supervisor_id' => $gProd->id, 'evaluation_grades' => '360', 'status' => 1]
                );
                Position::updateOrCreate(['name' => 'OPERADOR AXR', 'department_id' => $dProdAXR->id], ['order' => 5, 'supervisor_id' => $sup->id, 'evaluation_grades' => '360', 'status' => 1]);

                if (in_array($sede->name, ['ECHEVESTE', 'RIO'])) {
                    Position::updateOrCreate(['name' => 'OPERADOR SIT', 'department_id' => $dProdAXR->id], ['order' => 5, 'supervisor_id' => $sup->id, 'evaluation_grades' => '360', 'status' => 1]);
                }
            }
        }

        // Caso Especial Metropolitana (Productividad SUB)
        $deptMantMetro = $getDept('METROPOLITANA', 'Mantenimiento General');
        if ($deptMantMetro) {
            $supSub = Position::updateOrCreate(['name' => 'Supervisor de Productividad Sub E Inter', 'department_id' => $deptMantMetro->id], ['order' => 4, 'supervisor_id' => $gProd->id, 'evaluation_grades' => '360', 'status' => 1]);
            Position::updateOrCreate(['name' => 'OPERADOR SUB', 'department_id' => $deptMantMetro->id], ['order' => 5, 'supervisor_id' => $supSub->id, 'evaluation_grades' => '360', 'status' => 1]);
        }

        // --- MANTENIMIENTO GENERAL RIO ---
        $deptMantRio = $getDept('RIO', 'Mantenimiento General');
        if ($deptMantRio) {
            $cMantRio = Position::updateOrCreate(['name' => 'Coordinador de Mantenimiento RIO', 'department_id' => $deptMantRio->id], ['order' => 4, 'supervisor_id' => $gMantGen->id, 'evaluation_grades' => '360', 'status' => 1]);
            Position::updateOrCreate(['name' => 'OPERADOR GRUA', 'department_id' => $deptMantRio->id], ['order' => 5, 'supervisor_id' => $cMantRio->id, 'evaluation_grades' => '360', 'status' => 1]);
            Position::updateOrCreate(['name' => 'Mecánico A', 'department_id' => $deptMantRio->id], ['order' => 5, 'supervisor_id' => $cMantRio->id, 'evaluation_grades' => '360', 'status' => 1]);
        }
    }
}
