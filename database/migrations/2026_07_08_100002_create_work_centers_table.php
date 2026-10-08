<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compulsa_work_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')
                ->nullable()
                ->constrained('sedes')
                ->nullOnDelete();
            $table->string('nombre_sede', 180)->index();
            $table->string('registro_patronal', 20)->unique();
            $table->string('rfc', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compulsa_work_centers');
    }
};
