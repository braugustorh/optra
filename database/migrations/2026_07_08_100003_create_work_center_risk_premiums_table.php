<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compulsa_work_center_risk_premiums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_center_id')
                ->constrained('compulsa_work_centers')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('ejercicio');
            $table->decimal('prima_riesgo', 10, 6);
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();

            $table->unique(['work_center_id', 'ejercicio'], 'wc_risk_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compulsa_work_center_risk_premiums');
    }
};
