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
        Schema::create('privacy_notices', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('version', 50)->nullable();
            $table->string('scope_type', 30)->default('razon_social'); // 'razon_social' | 'sede' | 'general'
            $table->string('provider_name', 255)->default('NetInnfode');
            $table->string('general_company_name', 255)->nullable();
            $table->text('general_address')->nullable();
            $table->longText('initial_notice_content');
            $table->longText('simplified_content');
            $table->longText('extended_content');
            $table->text('checkbox_label');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Índice compuesto para obtener el aviso activo de forma inmediata
            $table->index(['is_active', 'id'], 'idx_privacy_notices_active');
            $table->index('scope_type', 'idx_privacy_notices_scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('privacy_notices');
    }
};
