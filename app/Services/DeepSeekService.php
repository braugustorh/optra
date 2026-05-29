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
    public function generateReport(array $candidateData, array $testResults, array $competencias = []): array
    {
        $puesto = $candidateData['puesto'] ?? 'General';

        // Generar una semilla determinista a partir de los resultados + puesto.
        $deterministicSeed = abs(crc32(json_encode($testResults) . $puesto));

        $prompt = $this->buildUserPrompt($candidateData, $testResults, $puesto, $competencias);

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
Tu única función es analizar resultados de pruebas psicométricas y generar un dictamen clínico en formato JSON estricto.

REGLAS GLOBALES Y CALIBRACIÓN CULTURAL:
1. Contexto México: La alta distancia jerárquica puede suprimir la Dominancia (D) en Cleaver. La Constancia (S) y Cumplimiento (C) suelen ser altas por evitación de incertidumbre.
2. Cero Alucinaciones: Limita tus conclusiones estrictamente a los números presentados y a las competencias ya pre-calculadas.

FORMATO DE SALIDA OBLIGATORIO (sin markdown, solo JSON puro):
{
    "pasos_de_razonamiento": {
        "1_contraste_cuantitativo_puro": "Comparación numérica exacta contra los baremos ideales.",
        "2_calibracion_cultural_mexico": "Ajuste interpretativo por factores culturales.",
        "3_resolucion_discrepancias_clinicas": "Detección de patrones y aplicación de reglas SEDYCO.",
        "4_evaluacion_viabilidad_y_brechas": "Conclusión lógica del Gap Analysis."
    },
    "reporte": {
        "resultado_global": {
            "apto": true,
            "dictamen": "Apto | Apto con Plan de Desarrollo | No Apto",
            "nivel_ajuste": "Alto|Medio|Bajo",
            "porcentaje_ajuste": 85
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
        "notas_adicionales": "string (solo si hay alertas críticas)"
    }
}
PROMPT;
    }

    protected function buildUserPrompt(array $candidateData, array $testResults, string $puesto, array $competencias = []): string
    {
        $jsonEntrada = [
            'candidato' => [
                'nombres' => $candidateData['name'] ?? '',
                'puesto' => $puesto,
                'fecha_evaluacion' => date('Y-m-d')
            ],
            'pruebas' => $testResults,
            'competencias_precalculadas' => $competencias
        ];

        $reglasSedyco = $this->getSedycoProfile($puesto);

        $payload = json_encode([
            'input_candidato'       => $jsonEntrada,
            'target_perfil_sedyco'  => $reglasSedyco
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "Realiza un Gap Analysis psicométrico estricto basado en la siguiente información del candidato (incluyendo las competencias ya pre-calculadas por el sistema) y el perfil requerido:\n\n" . $payload;
    }

    /**
     * Mapea las directrices del manual SEDYCO v1.0 a arrays estructurados según el nivel jerárquico.
     */
    private function getSedycoProfile(string $nivel): array
    {
        return match (strtoupper(trim($nivel))) {
            'DIRECTIVO' => [
                'perfil' => 'Arquitecto Organizacional',
                'Terman' => 'CI 115-130+ (Corte: 110 con compensación)',
                'Cleaver' => ['D' => '70-85%', 'I' => '50-65%', 'S' => '25-40%', 'C' => '45-60%'],
                'Kostick' => ['G' => '5-7', 'L' => '6-8', 'A' => '6-8', 'P' => '4-6', 'N' => '4-6', 'T' => '5-7'],
                'Moss' => ['Supervision' => '75-90%', 'Decision' => '70-85%', 'Evaluacion' => '75-90%', 'Relaciones' => '65-80%', 'Sentido_Comun' => '70-85%'],
                'Moss_Wess' => ['Clima_Global' => 'Buena/Buena']
            ],
            'MANDO MEDIO' => [
                'perfil' => 'Traductor Organizacional',
                'Terman' => 'CI 105-120 (Corte: 100)',
                'Cleaver' => ['D' => '50-65%', 'I' => '55-70%', 'S' => '45-60%', 'C' => '50-65%'],
                'Kostick' => ['G' => '4-6', 'L' => '5-7', 'N' => '5-7', 'A' => '5-7', 'S' => '5-7'],
                'Moss' => ['Supervision' => '65-80%', 'Decision' => '60-75%', 'Evaluacion' => '65-80%', 'Relaciones' => '65-80%', 'Sentido_Comun' => '65-75%'],
                'Moss_Wess' => ['Apoyo' => 'Promedio/Alto (CRÍTICO)', 'Clima_Global' => 'Tiende a Buena']
            ],
            // CORREGIDO: era 'SUPERVISION', el puesto real es 'Supervisor' → strtoupper = 'SUPERVISOR'
            'SUPERVISOR' => [
                'perfil' => 'Soporte Confiable',
                'Terman' => 'CI 95-110 (Corte: 90)',
                'Cleaver' => ['D' => '30-45%', 'I' => '40-55%', 'S' => '65-80%', 'C' => '65-80%']
            ],
            'ADMINISTRATIVO' => [
                'perfil' => 'Precisión Operativa',
                'Terman' => 'CI 90-105 (Corte: 88)',
                'Cleaver' => ['D' => '15-30%', 'I' => '20-35%', 'S' => '70-90%', 'C' => '75-95%']
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
