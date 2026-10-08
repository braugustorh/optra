<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compulsa_calculation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculation_id')
                ->constrained('compulsa_calculations')
                ->cascadeOnDelete();

            $table->string('nss', 15)->index();
            $table->string('nombre_completo', 200);
            $table->string('rfc', 20)->nullable();
            $table->string('curp', 20)->nullable();

            $table->unsignedSmallInteger('dias_imss')->nullable();
            $table->unsignedSmallInteger('dias_sua')->nullable();
            $table->unsignedSmallInteger('dias_nomina')->nullable();

            $table->decimal('sdi_imss', 12, 4)->nullable();
            $table->decimal('sdi_sua', 12, 4)->nullable();
            $table->decimal('sdi_nomina', 12, 4)->nullable();
            $table->decimal('sdi_topado', 12, 4)->nullable();

            $table->decimal('cuota_imss', 14, 2)->default(0);
            $table->decimal('cuota_patron', 14, 2)->default(0);
            $table->decimal('diferencia_total', 14, 2)->default(0);

            $table->string('estatus_conciliacion', 30)->default('pendiente');
            $table->boolean('presente_imss')->default(false);
            $table->boolean('presente_sua')->default(false);
            $table->boolean('presente_nomina')->default(false);
            $table->boolean('supera_tope_uma')->default(false);

            $table->json('alertas_json')->nullable();
            $table->json('desglose_cuotas')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['calculation_id', 'estatus_conciliacion'], 'cr_calc_status_idx');
            $table->unique(['calculation_id', 'nss'], 'cr_calc_nss_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compulsa_calculation_records');
    }
};
