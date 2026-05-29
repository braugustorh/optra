<?php

namespace App\Services;

use App\Models\PsychometricEvaluation;
use App\Models\EvaluationsTypes;
use App\Services\CompetencyScoringService;

/**
 * GeneralReportService
 *
 * Concentra los resultados de todas las evaluaciones completadas de un batch.
 * Uso: $service->calculateBatch($batchId) → array consolidado listo para PDF/presentación.
 *
 * Para reporte con IA:
 *   $service->generateAiReport($batchId, $deepSeekService) → array con análisis DeepSeek
 */
class GeneralReportService
{
    protected PsychometricScoringService $scoring;

    public function __construct()
    {
        $this->scoring = new PsychometricScoringService();
    }

    /**
     * Calcula y consolida resultados de todas las evaluaciones COMPLETADAS de un batch.
     */
    public function calculateBatch(string $batchId): array
    {
        $evaluations = PsychometricEvaluation::with(['evaluable', 'evaluationType'])
            ->where('batch_id', $batchId)
            ->where('status', 'completed')
            ->get();

        if ($evaluations->isEmpty()) {
            return [];
        }

        $first = $evaluations->first();

        $consolidated = [
            'evaluable'               => $first->evaluable,
            'puesto'                  => $first->puesto,
            'batch_id'                => $batchId,
            'assigned_at'             => $first->assigned_at,
            'total_elapsed_seconds'   => 0,
            'total_elapsed_formatted' => '0m 00s',
            'tests'                   => [],
        ];

        foreach ($evaluations as $evaluation) {
            $testName = $evaluation->evaluationType->name
                ?? "Prueba #{$evaluation->evaluations_type_id}";

            try {
                $results = $this->scoring->calculate($evaluation);
            } catch (\Throwable $e) {
                $results = [
                    'error'     => $e->getMessage(),
                    'test_name' => $testName,
                ];
            }

            $elapsed = max(0, (int) ($evaluation->elapsed_seconds ?? 0));

            $consolidated['tests'][$testName] = [
                'evaluation_id'       => $evaluation->id,
                'test_name'           => $testName,
                'evaluations_type_id' => $evaluation->evaluations_type_id,
                'elapsed_seconds'     => $elapsed,
                'elapsed_formatted'   => self::formatSeconds($elapsed),
                'completed_at'        => $evaluation->completed_at,
                'results'             => $results,
            ];

            $consolidated['total_elapsed_seconds'] += $elapsed;
        }

        $consolidated['total_elapsed_formatted'] = self::formatSeconds(
            $consolidated['total_elapsed_seconds']
        );

        return $consolidated;
    }

    /**
     * Puente entre GeneralReportService y DeepSeekService.
     *
     * Pasos:
     *   1. Llama a calculateBatch() para los resultados psicométricos.
     *   2. Transforma el array al formato que espera DeepSeekService.
     *   3. Llama a DeepSeekService::generateReport() y retorna la respuesta IA.
     *
     * Estructura retornada:
     *   consolidated => resultado de calculateBatch()
     *   ai_report    => respuesta de DeepSeek (array decodificado)
     *   ai_raw       => string JSON crudo para debug
     */
    public function generateAiReport(string $batchId, DeepSeekService $deepSeek): array
    {
        // 1. Calcular resultados psicométricos
        $consolidated = $this->calculateBatch($batchId);

        if (empty($consolidated)) {
            return ['error' => 'No se encontraron evaluaciones completadas en este batch.'];
        }

        $evaluable = $consolidated['evaluable'];

        // 2. Preparar $candidateData para DeepSeek
        $candidateData = [
            'name'   => $evaluable->name ?? 'Sin nombre',
            'puesto' => $consolidated['puesto'] ?? 'General',
        ];

        // 3. Transformar tests al formato que espera DeepSeekService::generateReport()
        //    Solo pasamos los 'results' de cada prueba, no los metadatos internos.
        $testResults = collect($consolidated['tests'])
            ->mapWithKeys(fn ($test, $name) => [$name => $test['results']])
            ->toArray();

        // 4. Calcular competencias
        $competencias = app(CompetencyScoringService::class)->calculate(
            $consolidated['puesto'] ?? 'General',
            $testResults
        );
        // 4b. Obtener perfil ideal Cleaver para el radar chart
        $cleaverIdeal = $deepSeek->getIdealCleaverForChart(
            $consolidated['puesto'] ?? 'General'
        );


        // 5. Llamar a DeepSeek
        $aiResponse = $deepSeek->generateReport($candidateData, $testResults, $competencias);

        // Detectar si la IA devolvió un error
        $aiError = null;
        if (isset($aiResponse['__ai_error']) && $aiResponse['__ai_error'] === true) {
            $aiError    = $aiResponse;
            $aiResponse = null;
        } elseif (empty($aiResponse)) {
            // Si la respuesta está vacía pero no hay __ai_error, es un error no detectado
            $aiError = [
                '__ai_error' => true,
                'message' => 'DeepSeek devolvió una respuesta vacía o no válida',
                'code' => 'empty_response',
            ];
            $aiResponse = null;
        }

        return [
            'consolidated' => $consolidated,
            'competencias' => $competencias,
            'cleaver_ideal' => $cleaverIdeal,
            'ai_report'    => $aiResponse,
            'ai_error'     => $aiError,
            'ai_raw'       => is_string($aiResponse) ? $aiResponse : json_encode($aiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Formatea segundos a string legible: "1h 23m 04s" o "12m 05s"
     */
    public static function formatSeconds(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;

        return $h > 0
            ? sprintf('%dh %02dm %02ds', $h, $m, $s)
            : sprintf('%dm %02ds', $m, $s);
    }
}

