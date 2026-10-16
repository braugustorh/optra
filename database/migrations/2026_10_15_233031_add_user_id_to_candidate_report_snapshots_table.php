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
        Schema::table('candidate_report_snapshots', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('candidate_id')->constrained('users')->nullOnDelete();
            $table->foreignId('candidate_id')->nullable()->change();
        });

        // Vincular snapshots existentes cuyos candidatos ya tengan usuario colaborador
        try {
            \Illuminate\Support\Facades\DB::statement("
                UPDATE candidate_report_snapshots crs
                INNER JOIN candidates c ON crs.candidate_id = c.id
                SET crs.user_id = c.user_id
                WHERE c.user_id IS NOT NULL AND crs.user_id IS NULL
            ");
        } catch (\Throwable $e) {
            // Silencioso si no hay registros
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_report_snapshots', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
