<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compulsa_calculation_records', function (Blueprint $table) {
            $table->decimal('amortizacion_imss', 14, 2)->nullable()->default(0)->change();
            $table->decimal('amortizacion_sua', 14, 2)->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('compulsa_calculation_records', function (Blueprint $table) {
            $table->decimal('amortizacion_imss', 14, 2)->default(0)->change();
            $table->decimal('amortizacion_sua', 14, 2)->default(0)->change();
        });
    }
};
