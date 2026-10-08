<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compulsa_global_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('ejercicio')->unique();

            // Valores base
            $table->decimal('salario_minimo', 12, 4);
            $table->decimal('valor_uma', 12, 4);
            $table->decimal('valor_umi', 12, 4)->nullable();
            $table->decimal('tope_uma_veces', 8, 2)->default(25.00);

            // Cuotas EMA (mensual) — porcentajes expresados como 20.400 = 20.4 %
            $table->decimal('rate_cuota_fija_patronal', 8, 4)->default(20.400);
            $table->decimal('rate_excedente_patronal', 8, 4)->default(1.100);
            $table->decimal('rate_excedente_obrero', 8, 4)->default(0.400);
            $table->decimal('rate_prest_dinero_patronal', 8, 4)->default(0.700);
            $table->decimal('rate_prest_dinero_obrero', 8, 4)->default(0.250);
            $table->decimal('rate_gmp_patronal', 8, 4)->default(1.050);
            $table->decimal('rate_gmp_obrero', 8, 4)->default(0.375);
            $table->decimal('rate_iv_patronal', 8, 4)->default(1.750);
            $table->decimal('rate_iv_obrero', 8, 4)->default(0.625);
            $table->decimal('rate_guarderias_patronal', 8, 4)->default(1.000);

            // Cuotas EBA (bimestral)
            $table->decimal('rate_retiro_patronal', 8, 4)->default(2.000);
            $table->decimal('rate_cesantia_patronal', 8, 4)->default(3.150);
            $table->decimal('rate_cesantia_obrero', 8, 4)->default(1.125);
            $table->decimal('rate_infonavit_patronal', 8, 4)->default(5.000);

            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compulsa_global_settings');
    }
};
