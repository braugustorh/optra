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
        Schema::create('privacy_notice_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('privacy_notice_id')->constrained('privacy_notices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('razon_social_id')->nullable()->constrained('razon_socials')->nullOnDelete();
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->string('resolved_company_name', 255)->nullable();
            $table->text('resolved_address')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accepted_at')->useCurrent();
            $table->timestamps();

            // Índice único compuesto B-Tree para verificación O(1) al iniciar sesión y evitar duplicados
            $table->unique(['privacy_notice_id', 'user_id'], 'uq_privacy_notice_user');

            // Índices adicionales para historial de usuario y filtros de auditoría
            $table->index(['user_id', 'accepted_at'], 'idx_acceptance_user_date');
            $table->index(['privacy_notice_id', 'razon_social_id', 'sede_id', 'accepted_at'], 'idx_acceptance_audit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privacy_notice_acceptances');
    }
};
