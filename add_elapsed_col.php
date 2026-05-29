<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

if (!Schema::hasColumn('psychometric_evaluations', 'elapsed_seconds')) {
    Schema::table('psychometric_evaluations', function (Blueprint $table) {
        $table->unsignedInteger('elapsed_seconds')->default(0)->after('completed_at');
    });
    echo "Columna 'elapsed_seconds' agregada correctamente.\n";
} else {
    echo "La columna 'elapsed_seconds' ya existe.\n";
}

