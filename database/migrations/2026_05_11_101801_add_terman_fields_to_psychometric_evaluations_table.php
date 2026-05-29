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
            $table->unsignedBigInteger('current_series_id')->nullable();
            $table->timestamp('current_series_started_at')->nullable();
            $table->boolean('is_invalidated')->default(false);
            $table->string('invalidated_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('psychometric_evaluations', function (Blueprint $table) {
            $table->dropColumn([
                'current_series_id',
                'current_series_started_at',
                'is_invalidated',
                'invalidated_reason'
            ]);
        });
    }
};
