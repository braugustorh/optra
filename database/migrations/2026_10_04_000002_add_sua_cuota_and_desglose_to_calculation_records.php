<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compulsa_calculation_records', function (Blueprint $table) {
            if (! Schema::hasColumn('compulsa_calculation_records', 'cuota_sua')) {
                $table->decimal('cuota_sua', 14, 2)->default(0)->after('cuota_imss');
            }
            if (! Schema::hasColumn('compulsa_calculation_records', 'desglose_sua')) {
                $table->json('desglose_sua')->nullable()->after('desglose_cuotas');
            }
        });
    }

    public function down(): void
    {
        Schema::table('compulsa_calculation_records', function (Blueprint $table) {
            if (Schema::hasColumn('compulsa_calculation_records', 'cuota_sua')) {
                $table->dropColumn('cuota_sua');
            }
            if (Schema::hasColumn('compulsa_calculation_records', 'desglose_sua')) {
                $table->dropColumn('desglose_sua');
            }
        });
    }
};
