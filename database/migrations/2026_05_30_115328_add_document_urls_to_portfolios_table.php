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
        Schema::table('portfolios', function (Blueprint $table) {
            $table->string('recomendacion_url')->nullable()->after('carta_no_antecedentes_url');
            $table->string('cert_medico_url')->nullable()->after('recomendacion_url');
            $table->string('nss_url')->nullable()->after('cert_medico_url');
            $table->string('alta_imss_url')->nullable()->after('nss_url');
            $table->string('modificacion_imss_url')->nullable()->after('alta_imss_url');
            $table->string('baja_imss_url')->nullable()->after('modificacion_imss_url');
            $table->string('retencion_url')->nullable()->after('baja_imss_url');
            $table->string('renuncia_url')->nullable()->after('retencion_url');
            $table->string('finiquito_url')->nullable()->after('renuncia_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolios', function (Blueprint $table) {
            $table->dropColumn([
                'recomendacion_url',
                'cert_medico_url',
                'nss_url',
                'alta_imss_url',
                'modificacion_imss_url',
                'baja_imss_url',
                'retencion_url',
                'renuncia_url',
                'finiquito_url'
            ]);
        });
    }
};
