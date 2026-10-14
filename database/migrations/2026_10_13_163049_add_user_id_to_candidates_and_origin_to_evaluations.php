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
        Schema::table('candidates', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });

        Schema::table('psychometric_evaluations', function (Blueprint $table) {
            $table->foreignId('candidate_origin_id')->nullable()->after('evaluable_id')->constrained('candidates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('psychometric_evaluations', function (Blueprint $table) {
            $table->dropForeign(['candidate_origin_id']);
            $table->dropColumn('candidate_origin_id');
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
