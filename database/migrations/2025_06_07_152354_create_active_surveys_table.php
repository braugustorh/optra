<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('active_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('norma_id')
                ->constrained('nom_035_processes')
                ->onDelete('cascade');
            $table->foreignId('evaluations_type_id')
                ->constrained('evaluations_types')
                ->onDelete('cascade');
            $table->boolean('some_users')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_surveys');
    }
};
