<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compulsa_calculations') && Schema::hasColumn('compulsa_calculations', 'prima_riesgo_aplicada')) {
            Schema::table('compulsa_calculations', function (Blueprint $table) {
                $table->decimal('prima_riesgo_aplicada', 12, 7)->nullable()->change();
            });
        }

        if (Schema::hasTable('compulsa_risk_premiums') && Schema::hasColumn('compulsa_risk_premiums', 'prima_riesgo')) {
            Schema::table('compulsa_risk_premiums', function (Blueprint $table) {
                $table->decimal('prima_riesgo', 12, 7)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compulsa_calculations') && Schema::hasColumn('compulsa_calculations', 'prima_riesgo_aplicada')) {
            Schema::table('compulsa_calculations', function (Blueprint $table) {
                $table->decimal('prima_riesgo_aplicada', 10, 6)->nullable()->change();
            });
        }

        if (Schema::hasTable('compulsa_risk_premiums') && Schema::hasColumn('compulsa_risk_premiums', 'prima_riesgo')) {
            Schema::table('compulsa_risk_premiums', function (Blueprint $table) {
                $table->decimal('prima_riesgo', 10, 6)->change();
            });
        }
    }
};
