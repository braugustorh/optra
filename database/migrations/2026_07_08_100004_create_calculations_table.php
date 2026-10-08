<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compulsa_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_center_id')
                ->constrained('compulsa_work_centers')
                ->restrictOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('tipo_periodo', 15);
            $table->unsignedTinyInteger('mes_bimestre');
            $table->unsignedSmallInteger('ejercicio');

            $table->string('estado', 20)->default('draft');

            $table->decimal('prima_riesgo_aplicada', 10, 6)->nullable();
            $table->decimal('valor_uma_aplicado', 12, 4)->nullable();
            $table->decimal('salario_minimo_aplicado', 12, 4)->nullable();
            $table->decimal('tope_uma_veces_aplicado', 8, 2)->nullable();

            $table->unsignedInteger('total_empleados')->default(0);
            $table->unsignedInteger('empleados_con_diferencias')->default(0);
            $table->decimal('total_cuota_imss', 14, 2)->default(0);
            $table->decimal('total_cuota_patron', 14, 2)->default(0);
            $table->decimal('total_diferencia', 14, 2)->default(0);

            $table->string('archivo_imss_path')->nullable();
            $table->string('archivo_sua_path')->nullable();
            $table->string('archivo_nomina_path')->nullable();

            $table->json('meta')->nullable();
            $table->timestamp('procesado_en')->nullable();
            $table->timestamps();

            $table->index(['work_center_id', 'ejercicio', 'mes_bimestre', 'tipo_periodo'], 'calc_period_idx');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compulsa_calculations');
    }
};
