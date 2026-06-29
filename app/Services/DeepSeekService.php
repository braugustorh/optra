<?php

namespace App\Services;

use DeepSeek\DeepSeekClient;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected DeepSeekClient $deepseek;

    public function __construct(DeepSeekClient $deepseek)
    {
        $this->deepseek = $deepseek;
    }

    /**
     * Genera un dictamen psicométrico determinista basado en los resultados del candidato.
     *
     * @param array $candidateData  Datos del candidato (debe incluir 'puesto')
     * @param array $testResults    Resultados de las pruebas aplicadas
     * @return array                Reporte estructurado o array con clave '__ai_error'
     */
    public function generateReport(array $candidateData, array $testResults, array $competencias = [], float $ajusteGlobal = 0.0, string $dictamen = ''): array
    {
        $puesto = $candidateData['puesto'] ?? 'General';

        // Generar una semilla determinista a partir de los resultados + puesto.
        $deterministicSeed = abs(crc32(json_encode($testResults) . $puesto));

        // Pasamos el ajuste global al constructor del prompt
        $prompt = $this->buildUserPrompt($candidateData, $testResults, $puesto, $competencias, $ajusteGlobal,$dictamen);

        try {
            $queryBuilder = $this->deepseek
                ->query($this->getSystemPrompt(), 'system')
                ->query($prompt)
                ->withModel("deepseek-chat")   // Regresamos a deepseek-chat por compatibilidad SDK
                ->setTemperature(0.0);             // Greedy sampling: sin aleatoriedad

            // Inyectar parámetros de determinismo y formato JSON
            if (method_exists($queryBuilder, 'addParameter')) {
                $queryBuilder
                    ->addParameter('seed', $deterministicSeed)
                    ->addParameter('response_format', ['type' => 'json_object']);
                // top_p no es necesario con temperature=0, se omite.
            }
            file_put_contents(
                storage_path('logs/deepseek_payload_debug.json'),
                json_encode([
                    'candidateData' => $candidateData,
                    'testResults'   => $testResults,
                    'competencias'  => $competencias,
                    'prompt'        => $prompt,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
            $response = $queryBuilder->run();

            $decoded = json_decode($response, true);

            // Manejo de errores de la API (saldo insuficiente, rate limit, etc.)
            if (isset($decoded['error'])) {
                $errorMsg = $decoded['error']['message'] ?? 'Error desconocido de la API';
                $errorCode = $decoded['error']['code'] ?? $decoded['error']['type'] ?? '';
                Log::warning("DeepSeek API error [{$errorCode}]: {$errorMsg}");

                return [
                    '__ai_error' => true,
                    'message'    => $errorMsg,
                    'code'       => $errorCode,
                ];
            }

            $content = $decoded['choices'][0]['message']['content'] ?? null;

            if ($content) {
                // Eliminar posibles bloques de markdown (por si el modelo ignora response_format)
                $content = preg_replace('/^```json\s*(.*?)\s*```$/s', '$1', trim($content));

                $reportData = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $reportData; // Éxito
                }

                Log::warning('DeepSeek: contenido no es JSON válido', [
                    'content' => substr($content, 0, 500),
                    'error'   => json_last_error_msg()
                ]);

                return [
                    '__ai_error' => true,
                    'message'    => 'DeepSeek devolvió una respuesta que no es JSON válido',
                    'code'       => 'invalid_json_content',
                ];
            }

            return $decoded ?? [];

        } catch (\Exception $e) {
            Log::error('Error al generar reporte psicológico: ' . $e->getMessage());

            return [
                '__ai_error' => true,
                'message'    => $e->getMessage(),
                'code'       => 'exception',
            ];
        }
    }

    /**
     * System prompt estático que contiene TODAS las reglas, teoría y formato de salida.
     * Al ser inmutable, el modelo lo procesa con alta consistencia.
     */
    protected function getSystemPrompt(): string
    {
        return <<<PROMPT
Eres un psicólogo organizacional experto basado estrictamente en el "Modelo de Assessment Psicométrico Estratificado SEDYCO v1.1".
Tu única función es generar un dictamen clínico en formato JSON estricto, basándote en los resultados provistos.

INFORMACIÓN DEL SISTEMA (HECHOS INMUTABLES):
1. El porcentaje de ajuste global calculado por el sistema es: {ajuste_global_calculado}%
2. El dictamen final asignado por el sistema es: "{dictamen_php}"
Tu trabajo NO es calcular el dictamen, sino REDACTAR la justificación clínica congruente con este resultado.

REGLAS GLOBALES Y CALIBRACIÓN CULTURAL:
1. Contexto México: La alta distancia jerárquica puede suprimir la Dominancia (D) en Cleaver. La Constancia (S) y Cumplimiento (C) suelen ser altas por evitación de incertidumbre.
2. PRUEBAS OPCIONALES (MUY IMPORTANTE): Las pruebas Kostick, Moss y Moss Wess son opcionales para niveles Supervisores y Administrativos. Si en el JSON de entrada NO aparecen estas pruebas, ignóralas por completo. Basa tu análisis clínico y brechas estrictamente en las pruebas provistas y en las 'competencias_precalculadas'. NO menciones en el reporte que "faltan pruebas" ni penalices al candidato.

FORMATO DE SALIDA OBLIGATORIO (sin markdown, solo JSON puro):
{
    "pasos_de_razonamiento": {
        "1_analisis_de_competencias_criticas": "Análisis de las brechas en las competencias con mayor peso global.",
        "2_justificacion_del_dictamen": "Explicación de por qué el perfil coincide con el dictamen de {dictamen_php}."
    },
    "reporte": {
        "resultado_global": {
            "nivel_ajuste": "Alto | Medio | Bajo | Insuficiente"
        },
        "resumen_ejecutivo": "string (máx 100 palabras detallando ajuste cognitivo y conductual)",
        "fortaleza_principal": "string (1 frase, ej: Alta capacidad organizacional y enfoque)",
        "brecha_principal": "string (1 frase, ej: Disposición de servicio requiere desarrollo)",
        "plan_desarrollo": [
            {
                "prioridad": "critical|important|normal",
                "titulo": "string (ej: Disposición de Servicio — Nivel Débil)",
                "descripcion": "string (Acción recomendada, max 2 oraciones)",
                "periodo": "0 - 30 días | 30 - 60 días | 60 - 90 días"
            }
        ],
        "notas_adicionales": "string (solo si hay alertas clínicas)"
    }
}
PROMPT;
    }

    protected function buildUserPrompt(array $candidateData, array $testResults, string $puesto, array $competencias = [], float $ajusteGlobal = 0.0, string $dictamen = ''): string
    {
        $jsonEntrada = [
            'candidato' => [
                'nombres' => $candidateData['name'] ?? '',
                'puesto' => $puesto,
                'fecha_evaluacion' => date('Y-m-d'),
                // >>> INYECCIÓN CLAVE: El modelo usará esto como verdad absoluta <<<
                'ajuste_global_calculado' => $ajusteGlobal,
                'dictamen_asignado'=>$dictamen,
            ],
            'pruebas' => $testResults,
            'competencias_precalculadas' => $competencias
        ];

        $reglasSedyco = $this->getSedycoProfile($puesto);

        $payload = json_encode([
            'input_candidato'       => $jsonEntrada,
            'target_perfil_sedyco'  => $reglasSedyco
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "Realiza el reporte clínico basado en la siguiente información. IMPORTANTE: El sistema ya ha determinado que el ajuste es {$ajusteGlobal}% y el dictamen es '{$dictamen}'. Tu único trabajo es REDACTAR la justificación clínica y el plan de desarrollo congruentes con este dictamen:\n\n" . $payload;


    }

    /**
     * Mapea las directrices del manual SEDYCO v1.1 a arrays estructurados según el nivel jerárquico.
     */
    private function getSedycoProfile(string $nivel): array
    {
        // Normalizamos espacios y guiones bajos para asegurar el match
        $nivelNormalizado = str_replace(' ', '_', strtoupper(trim($nivel)));

        return match ($nivelNormalizado) {
            'DIRECTIVO' => [
                'perfil' => 'Directivo',
                'Terman' => 'CI 115-130+ (Corte: 110 con compensación)',
                'Cleaver' => ['D' => '70-85%', 'I' => '50-65%', 'S' => '25-40%', 'C' => '45-60%'],
                'Kostick' => ['G' => '5-7', 'L' => '6-8', 'A' => '6-8', 'P' => '4-6', 'N' => '4-6', 'T' => '5-7'],
                'Moss' => ['Supervision' => '75-90%', 'Decision' => '70-85%', 'Evaluacion' => '75-90%', 'Relaciones' => '65-80%', 'Sentido_Comun' => '70-85%'],
                // Evaluamos las Dimensiones principales y la innovación
                'Moss_Wess' => ['Relaciones' => 'Promedio/Buena', 'Auto-realización' => 'Promedio/Buena', 'INNOVACION' => 'Promedio/Buena']
            ],
            'MANDO_MEDIO' => [
                'perfil' => 'Mando Medio',
                'Terman' => 'CI 105-120 (Corte: 100)',
                'Cleaver' => ['D' => '50-65%', 'I' => '55-70%', 'S' => '45-60%', 'C' => '50-65%'],
                'Kostick' => ['G' => '4-6', 'L' => '5-7', 'N' => '5-7', 'A' => '5-7', 'S' => '5-7', 'C' => '4-6'],
                'Moss' => ['Supervision' => '65-80%', 'Decision' => '60-75%', 'Evaluacion' => '65-80%', 'Relaciones' => '65-80%', 'Sentido_Comun' => '65-75%'],
                // Mapeado a la dimensión "Relaciones" y a la subescala "APOYO"
                'Moss_Wess' => ['APOYO' => 'Promedio/Buena (CRÍTICO)', 'Relaciones' => 'Promedio/Buena']
            ],
            'SUPERVISOR' => [
                'perfil' => 'Supervisor',
                'Terman' => 'CI 95-110 (Corte: 90)',
                'Cleaver' => ['D' => '30-45%', 'I' => '40-55%', 'S' => '65-80%', 'C' => '65-80%'],
                'Kostick' => ['L' => '4-6', 'C' => '5-7', 'S' => '5-7', 'E' => '4-6', 'N' => '4-6'],
                'Moss' => ['Supervision' => '50-70%', 'Decision' => '50-65%', 'Evaluacion' => '50-70%', 'Relaciones' => '60-80%', 'Sentido_Comun' => '60-75%'],
                // Mapeado directo a las subescalas del JSON
                'Moss_Wess' => ['COHESION' => 'Promedio/Buena', 'CONTROL' => 'Promedio']
            ],
            'ADMINISTRATIVO' => [
                'perfil' => 'Administrativo',
                'Terman' => 'CI 90-105 (Corte: 88)',
                'Cleaver' => ['D' => '15-30%', 'I' => '20-35%', 'S' => '70-90%', 'C' => '75-95%'],
                'Kostick' => ['C' => '6-8', 'W' => '6-8', 'S' => '4-6', 'A' => '3-5', 'P' => '2-4 (Bajo control sobre otros)'],
                'Moss' => ['Supervision' => 'No Crítico (30-50%)', 'Decision' => '40-60%', 'Evaluacion' => '40-60%', 'Relaciones' => '50-70%', 'Sentido_Comun' => '60-80%'],
                // Mapeado directo a las subescalas del JSON
                'Moss_Wess' => ['ORGANIZACIÓN' => 'Promedio/Buena', 'CLARIDAD' => 'Promedio/Buena']
            ],
            default => [
                'nota' => 'No se encontró un perfil estratificado específico para este puesto en el manual SEDYCO. Evaluar competencias generales.'
            ]
        };
    }
    /**
     * Devuelve los valores ideales de Cleaver (DISC) como puntos medios de los rangos SEDYCO (0-100).
     * Usado para renderizar el dataset "Ideal SEDYCO" en la gráfica de radar del reporte.
     *
     * Midpoints: Directivo D=(70+85)/2=78, Mando Medio D=(50+65)/2=58, etc.
     */
    public function getIdealCleaverForChart(string $nivel): array
    {
        $nivel = strtoupper(trim($nivel));

        $ideales = [
            'DIRECTIVO'      => ['D' => 78, 'I' => 58, 'S' => 33, 'C' => 53],
            'MANDO MEDIO'    => ['D' => 58, 'I' => 63, 'S' => 53, 'C' => 58],
            'SUPERVISOR'     => ['D' => 38, 'I' => 48, 'S' => 73, 'C' => 73],
            'ADMINISTRATIVO' => ['D' => 23, 'I' => 28, 'S' => 80, 'C' => 85],
        ];

        return $ideales[$nivel] ?? ['D' => 50, 'I' => 50, 'S' => 50, 'C' => 50];
    }

}
