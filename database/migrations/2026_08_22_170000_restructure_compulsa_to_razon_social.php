<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Agregar registro_patronal a razon_socials si no existe
        if (Schema::hasTable('razon_socials') && ! Schema::hasColumn('razon_socials', 'registro_patronal')) {
            Schema::table('razon_socials', function (Blueprint $table) {
                $table->string('registro_patronal', 50)->nullable()->after('rfc');
            });
        }

        // 2. Crear tabla de primas de riesgo vinculada a razon_socials
        if (! Schema::hasTable('compulsa_risk_premiums')) {
            Schema::create('compulsa_risk_premiums', function (Blueprint $table) {
                $table->id();
                $table->foreignId('razon_social_id')
                    ->constrained('razon_socials')
                    ->cascadeOnDelete();
                $table->unsignedSmallInteger('ejercicio');
                $table->decimal('prima_riesgo', 10, 6);
                $table->date('vigencia_desde')->nullable();
                $table->date('vigencia_hasta')->nullable();
                $table->timestamps();

                $table->unique(['razon_social_id', 'ejercicio'], 'rs_risk_year_unique');
            });
        }

        // 3. Modificar compulsa_calculations para usar razon_social_id
        if (Schema::hasTable('compulsa_calculations')) {
            Schema::table('compulsa_calculations', function (Blueprint $table) {
                if (Schema::hasColumn('compulsa_calculations', 'work_center_id')) {
                    // Quitar foreign key e índice antiguo si existen
                    try {
                        $table->dropForeign(['work_center_id']);
                    } catch (\Throwable $e) {
                    }
                    try {
                        $table->dropIndex('calc_period_idx');
                    } catch (\Throwable $e) {
                    }
                    $table->dropColumn('work_center_id');
                }

                if (! Schema::hasColumn('compulsa_calculations', 'razon_social_id')) {
                    $table->foreignId('razon_social_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('razon_socials')
                        ->nullOnDelete();

                    $table->index(['razon_social_id', 'ejercicio', 'mes_bimestre', 'tipo_periodo'], 'calc_rs_period_idx');
                }
            });
        }

        // 4. Eliminar tablas antiguas de work_centers
        Schema::dropIfExists('compulsa_work_center_risk_premiums');
        Schema::dropIfExists('compulsa_work_centers');
    }

    public function down(): void
    {
        if (Schema::hasTable('razon_socials') && Schema::hasColumn('razon_socials', 'registro_patronal')) {
            Schema::table('razon_socials', function (Blueprint $table) {
                $table->dropColumn('registro_patronal');
            });
        }

        Schema::dropIfExists('compulsa_risk_premiums');

        if (Schema::hasTable('compulsa_calculations') && Schema::hasColumn('compulsa_calculations', 'razon_social_id')) {
            Schema::table('compulsa_calculations', function (Blueprint $table) {
                $table->dropForeign(['razon_social_id']);
                $table->dropIndex('calc_rs_period_idx');
                $table->dropColumn('razon_social_id');
            });
        }
    }
};
