<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Actions;

use App\Enums\CalculationStatus;
use App\Enums\FileSource;
use App\Models\Compulsa\Calculation;
use App\Services\Compulsa\Parsers\FileParserFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Orquesta la ingesta completa de una Calculation:
 *   1. Marca el cálculo como Processing.
 *   2. Parsea IMSS, SUA y Nómina en chunks.
 *   3. Persiste cada chunk vía StoreCalculationRowsAction (upsert por NSS).
 *   4. Devuelve estadísticas por fuente en meta.
 *
 * NOTA: No calcula diferencias/cuotas aún — eso lo hará Fase 4.
 * El estado final queda en Processing hasta que Fase 4 lo pase a Completed.
 */
class IngestCompulsaFilesAction
{
    public function __construct(
        private readonly FileParserFactory $factory,
        private readonly StoreCalculationRowsAction $store,
    ) {
    }

    /**
     * @return array<string, int>  totales por fuente
     */
    public function execute(Calculation $calculation, int $chunkSize = 2000): array
    {
        $calculation->update(['estado' => CalculationStatus::Processing->value]);

        try {
            $files = [
                FileSource::Imss->value => $calculation->archivo_imss_path,
                FileSource::Sua->value => $calculation->archivo_sua_path,
                FileSource::Nomina->value => $calculation->archivo_nomina_path,
            ];

            $totals = [];
            foreach ($files as $sourceValue => $path) {
                if (empty($path)) {
                    continue;
                }
                $source = FileSource::from($sourceValue);
                $totals[$sourceValue] = $this->ingestOne($calculation, $source, $path, $chunkSize);
            }

            $calculation->update([
                'meta' => array_merge($calculation->meta ?? [], [
                    'ingest_totals' => $totals,
                    'ingested_at' => now()->toIso8601String(),
                ]),
            ]);

            return $totals;
        } catch (Throwable $e) {
            Log::error('Compulsa ingest failed', [
                'calculation_id' => $calculation->id,
                'error' => $e->getMessage(),
            ]);

            $calculation->update([
                'estado' => CalculationStatus::Failed->value,
                'meta' => array_merge($calculation->meta ?? [], [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toIso8601String(),
                ]),
            ]);

            throw $e;
        }
    }

    private function ingestOne(Calculation $calculation, FileSource $source, string $relativePath, int $chunkSize): int
    {
        $absolute = $this->resolveAbsolutePath($relativePath);
        if (! is_file($absolute)) {
            throw new RuntimeException(sprintf(
                'Archivo %s no encontrado: %s',
                $source->value,
                $relativePath,
            ));
        }

        $periodType = $calculation->tipo_periodo instanceof \App\Enums\PeriodType
            ? $calculation->tipo_periodo
            : \App\Enums\PeriodType::tryFrom((string) $calculation->tipo_periodo);

        $parser = $this->factory->make($source, $periodType);
        $count = 0;

        $parser->parseInChunks($absolute, $chunkSize, function (Collection $rows) use ($calculation, &$count): void {
            $count += $this->store->execute($calculation, $rows);
        });

        return $count;
    }

    private function resolveAbsolutePath(string $relativePath): string
    {
        if (is_file($relativePath)) {
            return $relativePath;
        }

        return Storage::disk('local')->path($relativePath);
    }
}
