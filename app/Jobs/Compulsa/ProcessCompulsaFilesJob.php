<?php

declare(strict_types=1);

namespace App\Jobs\Compulsa;

use App\Models\Compulsa\Calculation;
use App\Services\Compulsa\Actions\IngestCompulsaFilesAction;
use App\Services\Compulsa\Actions\ReconcileCalculationAction;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Orquestador de la Compulsa: ingesta de archivos (Fase 2) seguido de conciliación (Fase 4).
 * Diseñado para funcionar tanto síncrono (dispatchSync) como en cola (dispatch).
 */
class ProcessCompulsaFilesJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries = 1;

    public function __construct(
        public int $calculationId,
        public int $chunkSize = 2000,
    ) {
    }

    public function handle(
        IngestCompulsaFilesAction $ingest,
        ReconcileCalculationAction $reconcile,
    ): void {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $calculation = Calculation::findOrFail($this->calculationId);
        $ingest->execute($calculation, $this->chunkSize);
        $reconcile->execute($calculation->refresh());
    }
}
