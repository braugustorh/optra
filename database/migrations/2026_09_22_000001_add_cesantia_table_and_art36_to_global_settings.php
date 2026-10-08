<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compulsa_global_settings', function (Blueprint $table) {
            $table->json('tabla_cesantia_patronal')->nullable()->after('rate_cesantia_patronal');
            $table->boolean('exencion_cuota_obrera_sm')->default(true)->after('tabla_cesantia_patronal');
        });

        $tabla2025 = [
            ['rango' => '1.00 Salario Mínimo', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.00, 'porcentaje_patronal' => 3.150, 'aplica_sm' => true],
            ['rango' => 'De 1.01 SM a 1.50 UMA', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.50, 'porcentaje_patronal' => 3.575, 'aplica_sm' => false],
            ['rango' => 'De 1.51 a 2.00 UMA', 'limite_inferior_umas' => 1.51, 'limite_superior_umas' => 2.00, 'porcentaje_patronal' => 4.512, 'aplica_sm' => false],
            ['rango' => 'De 2.01 a 2.50 UMA', 'limite_inferior_umas' => 2.01, 'limite_superior_umas' => 2.50, 'porcentaje_patronal' => 5.109, 'aplica_sm' => false],
            ['rango' => 'De 2.51 a 3.00 UMA', 'limite_inferior_umas' => 2.51, 'limite_superior_umas' => 3.00, 'porcentaje_patronal' => 5.506, 'aplica_sm' => false],
            ['rango' => 'De 3.01 a 3.50 UMA', 'limite_inferior_umas' => 3.01, 'limite_superior_umas' => 3.50, 'porcentaje_patronal' => 5.790, 'aplica_sm' => false],
            ['rango' => 'De 3.51 a 4.00 UMA', 'limite_inferior_umas' => 3.51, 'limite_superior_umas' => 4.00, 'porcentaje_patronal' => 6.004, 'aplica_sm' => false],
            ['rango' => 'De 4.01 UMA en adelante', 'limite_inferior_umas' => 4.01, 'limite_superior_umas' => null, 'porcentaje_patronal' => 6.421, 'aplica_sm' => false],
        ];

        $tabla2026 = [
            ['rango' => '1.00 Salario Mínimo', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.00, 'porcentaje_patronal' => 3.150, 'aplica_sm' => true],
            ['rango' => 'De 1.01 SM a 1.50 UMA', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.50, 'porcentaje_patronal' => 3.676, 'aplica_sm' => false],
            ['rango' => 'De 1.51 a 2.00 UMA', 'limite_inferior_umas' => 1.51, 'limite_superior_umas' => 2.00, 'porcentaje_patronal' => 4.851, 'aplica_sm' => false],
            ['rango' => 'De 2.01 a 2.50 UMA', 'limite_inferior_umas' => 2.01, 'limite_superior_umas' => 2.50, 'porcentaje_patronal' => 5.556, 'aplica_sm' => false],
            ['rango' => 'De 2.51 a 3.00 UMA', 'limite_inferior_umas' => 2.51, 'limite_superior_umas' => 3.00, 'porcentaje_patronal' => 6.026, 'aplica_sm' => false],
            ['rango' => 'De 3.01 a 3.50 UMA', 'limite_inferior_umas' => 3.01, 'limite_superior_umas' => 3.50, 'porcentaje_patronal' => 6.361, 'aplica_sm' => false],
            ['rango' => 'De 3.51 a 4.00 UMA', 'limite_inferior_umas' => 3.51, 'limite_superior_umas' => 4.00, 'porcentaje_patronal' => 6.613, 'aplica_sm' => false],
            ['rango' => 'De 4.01 UMA en adelante', 'limite_inferior_umas' => 4.01, 'limite_superior_umas' => null, 'porcentaje_patronal' => 7.513, 'aplica_sm' => false],
        ];
        $tabla2027 = [
            ['rango' => '1.00 Salario Mínimo', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.00, 'porcentaje_patronal' => 3.150, 'aplica_sm' => true],
            ['rango' => 'De 1.01 SM a 1.50 UMA', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.50, 'porcentaje_patronal' => 3.791, 'aplica_sm' => false],
            ['rango' => 'De 1.51 a 2.00 UMA', 'limite_inferior_umas' => 1.51, 'limite_superior_umas' => 2.00, 'porcentaje_patronal' => 5.276, 'aplica_sm' => false],
            ['rango' => 'De 2.01 a 2.50 UMA', 'limite_inferior_umas' => 2.01, 'limite_superior_umas' => 2.50, 'porcentaje_patronal' => 6.166, 'aplica_sm' => false],
            ['rango' => 'De 2.51 a 3.00 UMA', 'limite_inferior_umas' => 2.51, 'limite_superior_umas' => 3.00, 'porcentaje_patronal' => 6.759, 'aplica_sm' => false],
            ['rango' => 'De 3.01 a 3.50 UMA', 'limite_inferior_umas' => 3.01, 'limite_superior_umas' => 3.50, 'porcentaje_patronal' => 7.183, 'aplica_sm' => false],
            ['rango' => 'De 3.51 a 4.00 UMA', 'limite_inferior_umas' => 3.51, 'limite_superior_umas' => 4.00, 'porcentaje_patronal' => 7.501, 'aplica_sm' => false],
            ['rango' => 'De 4.01 UMA en adelante', 'limite_inferior_umas' => 4.01, 'limite_superior_umas' => null, 'porcentaje_patronal' => 8.608, 'aplica_sm' => false],
        ];

        $tabla2028 = [
            ['rango' => '1.00 Salario Mínimo', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.00, 'porcentaje_patronal' => 3.150, 'aplica_sm' => true],
            ['rango' => 'De 1.01 SM a 1.50 UMA', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.50, 'porcentaje_patronal' => 3.906, 'aplica_sm' => false],
            ['rango' => 'De 1.51 a 2.00 UMA', 'limite_inferior_umas' => 1.51, 'limite_superior_umas' => 2.00, 'porcentaje_patronal' => 5.701, 'aplica_sm' => false],
            ['rango' => 'De 2.01 a 2.50 UMA', 'limite_inferior_umas' => 2.01, 'limite_superior_umas' => 2.50, 'porcentaje_patronal' => 6.776, 'aplica_sm' => false],
            ['rango' => 'De 2.51 a 3.00 UMA', 'limite_inferior_umas' => 2.51, 'limite_superior_umas' => 3.00, 'porcentaje_patronal' => 7.493, 'aplica_sm' => false],
            ['rango' => 'De 3.01 a 3.50 UMA', 'limite_inferior_umas' => 3.01, 'limite_superior_umas' => 3.50, 'porcentaje_patronal' => 8.004, 'aplica_sm' => false],
            ['rango' => 'De 3.51 a 4.00 UMA', 'limite_inferior_umas' => 3.51, 'limite_superior_umas' => 4.00, 'porcentaje_patronal' => 8.388, 'aplica_sm' => false],
            ['rango' => 'De 4.01 UMA en adelante', 'limite_inferior_umas' => 4.01, 'limite_superior_umas' => null, 'porcentaje_patronal' => 9.704, 'aplica_sm' => false],
        ];

        $tabla2029 = [
            ['rango' => '1.00 Salario Mínimo', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.00, 'porcentaje_patronal' => 3.150, 'aplica_sm' => true],
            ['rango' => 'De 1.01 SM a 1.50 UMA', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.50, 'porcentaje_patronal' => 4.021, 'aplica_sm' => false],
            ['rango' => 'De 1.51 a 2.00 UMA', 'limite_inferior_umas' => 1.51, 'limite_superior_umas' => 2.00, 'porcentaje_patronal' => 6.126, 'aplica_sm' => false],
            ['rango' => 'De 2.01 a 2.50 UMA', 'limite_inferior_umas' => 2.01, 'limite_superior_umas' => 2.50, 'porcentaje_patronal' => 7.385, 'aplica_sm' => false],
            ['rango' => 'De 2.51 a 3.00 UMA', 'limite_inferior_umas' => 2.51, 'limite_superior_umas' => 3.00, 'porcentaje_patronal' => 8.226, 'aplica_sm' => false],
            ['rango' => 'De 3.01 a 3.50 UMA', 'limite_inferior_umas' => 3.01, 'limite_superior_umas' => 3.50, 'porcentaje_patronal' => 8.826, 'aplica_sm' => false],
            ['rango' => 'De 3.51 a 4.00 UMA', 'limite_inferior_umas' => 3.51, 'limite_superior_umas' => 4.00, 'porcentaje_patronal' => 9.276, 'aplica_sm' => false],
            ['rango' => 'De 4.01 UMA en adelante', 'limite_inferior_umas' => 4.01, 'limite_superior_umas' => null, 'porcentaje_patronal' => 10.800, 'aplica_sm' => false],
        ];

        $tabla2030 = [
            ['rango' => '1.00 Salario Mínimo', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.00, 'porcentaje_patronal' => 3.150, 'aplica_sm' => true],
            ['rango' => 'De 1.01 SM a 1.50 UMA', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.50, 'porcentaje_patronal' => 4.136, 'aplica_sm' => false],
            ['rango' => 'De 1.51 a 2.00 UMA', 'limite_inferior_umas' => 1.51, 'limite_superior_umas' => 2.00, 'porcentaje_patronal' => 6.551, 'aplica_sm' => false],
            ['rango' => 'De 2.01 a 2.50 UMA', 'limite_inferior_umas' => 2.01, 'limite_superior_umas' => 2.50, 'porcentaje_patronal' => 7.995, 'aplica_sm' => false],
            ['rango' => 'De 2.51 a 3.00 UMA', 'limite_inferior_umas' => 2.51, 'limite_superior_umas' => 3.00, 'porcentaje_patronal' => 8.960, 'aplica_sm' => false],
            ['rango' => 'De 3.01 a 3.50 UMA', 'limite_inferior_umas' => 3.01, 'limite_superior_umas' => 3.50, 'porcentaje_patronal' => 9.648, 'aplica_sm' => false],
            ['rango' => 'De 3.51 a 4.00 UMA', 'limite_inferior_umas' => 3.51, 'limite_superior_umas' => 4.00, 'porcentaje_patronal' => 10.164, 'aplica_sm' => false],
            ['rango' => 'De 4.01 UMA en adelante', 'limite_inferior_umas' => 4.01, 'limite_superior_umas' => null, 'porcentaje_patronal' => 11.875, 'aplica_sm' => false],
        ];

        DB::table('compulsa_global_settings')
            ->where('ejercicio', 2025)
            ->update([
                'tabla_cesantia_patronal' => json_encode($tabla2025),
                'exencion_cuota_obrera_sm' => true,
            ]);

        DB::table('compulsa_global_settings')
            ->where('ejercicio', 2026)
            ->update([
                'tabla_cesantia_patronal' => json_encode($tabla2026),
                'exencion_cuota_obrera_sm' => true,
            ]);
        DB::table('compulsa_global_settings')
            ->where('ejercicio', 2027)
            ->update([
                'tabla_cesantia_patronal' => json_encode($tabla2027),
                'exencion_cuota_obrera_sm' => true,
            ]);

        DB::table('compulsa_global_settings')
            ->where('ejercicio', 2028)
            ->update([
                'tabla_cesantia_patronal' => json_encode($tabla2028),
                'exencion_cuota_obrera_sm' => true,
            ]);
        DB::table('compulsa_global_settings')
            ->where('ejercicio', 2029)
            ->update([
                'tabla_cesantia_patronal' => json_encode($tabla2029),
                'exencion_cuota_obrera_sm' => true,
            ]);

        DB::table('compulsa_global_settings')
            ->where('ejercicio', 2030)
            ->update([
                'tabla_cesantia_patronal' => json_encode($tabla2030),
                'exencion_cuota_obrera_sm' => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('compulsa_global_settings', function (Blueprint $table) {
            $table->dropColumn(['tabla_cesantia_patronal', 'exencion_cuota_obrera_sm']);
        });
    }
};
