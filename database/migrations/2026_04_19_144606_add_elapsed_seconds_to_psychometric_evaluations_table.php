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
        Schema::table('psychometric_evaluations', function (Blueprint $table) {
            $table->unsignedInteger('elapsed_seconds')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('psychometric_evaluations', function (Blueprint $table) {
            $table->dropColumn('elapsed_seconds');
        });
    }
};
