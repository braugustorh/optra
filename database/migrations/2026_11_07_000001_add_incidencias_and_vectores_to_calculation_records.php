<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compulsa_calculation_records', function (Blueprint $table) {
            if (! Schema::hasColumn('compulsa_calculation_records', 'dias_faltas_nomina')) {
                $table->unsignedSmallInteger('dias_faltas_nomina')->default(0)->after('dias_nomina');
            }
            if (! Schema::hasColumn('compulsa_calculation_records', 'dias_incapacidad_nomina')) {
                $table->unsignedSmallInteger('dias_incapacidad_nomina')->default(0)->after('dias_faltas_nomina');
            }
            if (! Schema::hasColumn('compulsa_calculation_records', 'dias_vacaciones_nomina')) {
                $table->unsignedSmallInteger('dias_vacaciones_nomina')->default(0)->after('dias_incapacidad_nomina');
            }
            if (! Schema::hasColumn('compulsa_calculation_records', 'dias_eym')) {
                $table->unsignedSmallInteger('dias_eym')->nullable()->after('dias_vacaciones_nomina');
            }
            if (! Schema::hasColumn('compulsa_calculation_records', 'dias_resto')) {
                $table->unsignedSmallInteger('dias_resto')->nullable()->after('dias_eym');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compulsa_calculation_records', function (Blueprint $table) {
            $cols = ['dias_faltas_nomina', 'dias_incapacidad_nomina', 'dias_vacaciones_nomina', 'dias_eym', 'dias_resto'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('compulsa_calculation_records', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
