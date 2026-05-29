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
        Schema::create('razon_social_sede', function (Blueprint $table) {
            $table->id();
            $table->foreignId('razon_social_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('razon_social_sede');
    }
};
