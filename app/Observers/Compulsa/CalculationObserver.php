<?php

declare(strict_types=1);

namespace App\Observers\Compulsa;

use App\Models\Compulsa\Calculation;
use Illuminate\Support\Facades\Storage;

/**
 * Limpia el directorio de archivos asociado a una Compulsa cuando ésta se elimina.
 * Los CalculationRecord caen en cascada por FK; los archivos físicos no.
 */
class CalculationObserver
{
    public function deleting(Calculation $calculation): void
    {
        $disk = Storage::disk('local');
        $directory = "compulsa/{$calculation->id}";

        if ($disk->exists($directory)) {
            $disk->deleteDirectory($directory);
        }
    }
}
