<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compulsa_calculation_records', function (Blueprint $table) {
            $table->decimal('amortizacion_imss', 14, 2)->default(0)->after('cuota_imss');
            $table->decimal('amortizacion_sua', 14, 2)->default(0)->after('amortizacion_imss');
            $table->string('numero_credito', 25)->nullable()->after('amortizacion_sua');
        });
    }

    public function down(): void
    {
        Schema::table('compulsa_calculation_records', function (Blueprint $table) {
            $table->dropColumn(['amortizacion_imss', 'amortizacion_sua', 'numero_credito']);
        });
    }
};
