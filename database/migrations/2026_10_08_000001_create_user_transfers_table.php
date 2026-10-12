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
        Schema::create('user_transfers', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            $table->foreignId('from_sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->foreignId('to_sede_id')->constrained('sedes')->cascadeOnDelete();
            
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->nullable()->constrained('departments')->nullOnDelete();
            
            $table->foreignId('from_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->foreignId('to_position_id')->nullable()->constrained('positions')->nullOnDelete();
            
            $table->foreignId('from_razon_social_id')->nullable()->constrained('razon_socials')->nullOnDelete();
            $table->foreignId('to_razon_social_id')->nullable()->constrained('razon_socials')->nullOnDelete();
            
            $table->date('transfer_date');
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->string('reason', 255);
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_transfers');
    }
};
