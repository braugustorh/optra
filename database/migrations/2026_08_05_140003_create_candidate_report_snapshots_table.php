<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->uuid('batch_id');
            $table->string('puesto_original')->nullable();
            $table->string('puesto_evaluado'); // Puesto usado realmente para el cálculo (puede diferir del original)
            $table->decimal('ajuste_global', 5, 2)->nullable();
            $table->decimal('ajuste_relativo', 5, 2)->nullable();
            $table->string('dictamen')->nullable();
            $table->json('competencias_json')->nullable();
            $table->json('competencias_ideal_json')->nullable();
            $table->json('ai_report_json')->nullable();
            $table->json('cleaver_ideal_json')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('candidate_id');
            $table->index('batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_report_snapshots');
    }
};
