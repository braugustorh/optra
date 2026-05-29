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
        Schema::table('questions', function (Blueprint $table) {
            // 1. Dropear el índice actual que está estorbando.
            // Si era un índice único común, usa dropUnique. Si era normal, usa dropIndex.
            // Laravel por defecto nombra los índices únicos como: 'tabla_columna_unique'
            //$table->dropUnique('questions_question_unique');

            // NOTA: Si no se llama así o no es único, prueba con:
            $table->dropIndex('questions_question_index');
        });

        Schema::table('questions', function (Blueprint $table) {
            // 2. Ahora que no hay índice, cambiamos el tipo de columna a TEXT sin problemas
            $table->text('question')->change();
        });

        Schema::table('questions', function (Blueprint $table) {
            // 3. (Opcional) Si necesitas que siga teniendo un índice único,
            // tienes que forzarle una longitud de prefijo en crudo (ej. primeros 255 caracteres)
            DB::statement('ALTER TABLE questions ADD UNIQUE (question(255))');
        });
    }

    public function down(): void
    {
        // El camino de regreso por si haces rollback
        Schema::table('questions', function (Blueprint $table) {
            // Tendrías que quitar el índice de texto
            DB::statement('ALTER TABLE questions DROP INDEX question');
            // Regresar a string (varchar)
            $table->string('question', 255)->change();
            // Y volver a poner el unique clásico de Laravel
            $table->unique('question');
        });
    }
};

