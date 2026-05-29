<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('one_to_one_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Colaborador evaluado
            $table->foreignId('supervisor_id')->constrained('users')->onDelete('cascade'); // Supervisor que realiza la evaluación
            $table->date('evaluation_date'); // Fecha de la evaluación
            $table->boolean('initial')->nullable()->default(false);
            $table->boolean('follow_up')->nullable()->default(false);
            $table->boolean('consolidated')->nullable()->default(false);
            $table->boolean('final')->nullable()->default(false);
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress'); // Estado de la evaluación
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('one_to_one_evaluations');
    }
};
