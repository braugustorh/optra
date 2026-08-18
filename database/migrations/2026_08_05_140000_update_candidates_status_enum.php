<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Amplía el enum de estatus de candidatos al pipeline de selección:
     * en_proceso, contratado, banco_talento, archivado.
     * Mapea los valores previos: active/inactive -> en_proceso, hired -> contratado, rejected -> archivado.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        // 1. Ampliar temporalmente para permitir valores viejos y nuevos
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE candidates MODIFY status ENUM('active','inactive','hired','rejected','en_proceso','contratado','banco_talento','archivado') DEFAULT 'en_proceso'");
        } else {
            // PostgreSQL: usamos VARCHAR para permitir todos los valores temporalmente
            DB::statement("ALTER TABLE candidates ALTER COLUMN status TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE candidates ALTER COLUMN status SET DEFAULT 'en_proceso'");
            DB::statement("ALTER TABLE candidates DROP CONSTRAINT IF EXISTS candidates_status_check");
        }

        // 2. Migrar los datos existentes al nuevo vocabulario
        DB::table('candidates')->where('status', 'active')->update(['status' => 'en_proceso']);
        DB::table('candidates')->where('status', 'inactive')->update(['status' => 'en_proceso']);
        DB::table('candidates')->where('status', 'hired')->update(['status' => 'contratado']);
        DB::table('candidates')->where('status', 'rejected')->update(['status' => 'archivado']);

        // 3. Dejar únicamente con los valores nuevos
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE candidates MODIFY status ENUM('en_proceso','contratado','banco_talento','archivado') DEFAULT 'en_proceso'");
        } else {
            // PostgreSQL: restringimos con CHECK constraint
            DB::statement("ALTER TABLE candidates ADD CONSTRAINT candidates_status_check CHECK (status IN ('en_proceso','contratado','banco_talento','archivado'))");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE candidates MODIFY status ENUM('active','inactive','hired','rejected','en_proceso','contratado','banco_talento','archivado') DEFAULT 'active'");
        } else {
            DB::statement("ALTER TABLE candidates DROP CONSTRAINT IF EXISTS candidates_status_check");
            DB::statement("ALTER TABLE candidates ALTER COLUMN status TYPE VARCHAR(255)");
            DB::statement("ALTER TABLE candidates ALTER COLUMN status SET DEFAULT 'active'");
        }

        DB::table('candidates')->where('status', 'en_proceso')->update(['status' => 'active']);
        DB::table('candidates')->where('status', 'contratado')->update(['status' => 'hired']);
        DB::table('candidates')->where('status', 'banco_talento')->update(['status' => 'active']);
        DB::table('candidates')->where('status', 'archivado')->update(['status' => 'rejected']);

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE candidates MODIFY status ENUM('active','inactive','hired','rejected') DEFAULT 'active'");
        } else {
            DB::statement("ALTER TABLE candidates ADD CONSTRAINT candidates_status_check CHECK (status IN ('active','inactive','hired','rejected'))");
        }
    }
};
