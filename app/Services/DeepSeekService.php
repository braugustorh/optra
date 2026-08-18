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
    public function generateReport(array $candidateData, array $testResults, array $competencias = [], float $ajusteGlobal = 0.0, string $dictamen = '', float $ajusteRelativo = 0.0): array
    {
        $puesto = $candidateData['puesto'] ?? 'General';

        // Generar una semilla determinista a partir de los resultados + puesto.
        $deterministicSeed = abs(crc32(json_encode($testResults) . $puesto));

        // Pasamos el ajuste relativo (protagonista) y el ajuste global (seguridad) al constructor del prompt
        $prompt = $this->buildUserPrompt($candidateData, $testResults, $puesto, $competencias, $ajusteGlobal, $dictamen, $ajusteRelativo);

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
            \Illuminate\Support\Facades\Log::info('[DEEPSEEK-PAYLOAD]', [
                'candidateData' => $candidateData,
                'testResults'   => $testResults,
                'competencias'  => $competencias,
                'prompt'        => $prompt,
            ]);
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

REGLAS GLOBALES, CALIBRACIÓN CULTURAL Y DE INDUSTRIA:
1. Contexto México: La alta distancia jerárquica puede suprimir la Dominancia (D) en Cleaver. La Constancia (S) y Cumplimiento (C) suelen ser altas por evitación de incertidumbre.
2. Contexto Operativo (Administradora de Centrales (No choferes, no taquillas)): El talento evaluado opera en empresas de logística, mantenimiento, atención masiva en piso y gerencias de terminal. Aterriza tu lenguaje y planes de desarrollo a esta realidad.
3. Tono Constructivo (Growth Mindset): Evita lenguaje punitivo o destructivo.
4. REGLA ESTRICTA DE PLAN DE DESARROLLO: ÚNICAMENTE debes generar elementos en el "plan_desarrollo" para las competencias que cumplan estas DOS condiciones juntas en el JSON de entrada:
   a) Que "requerida" sea true.
   b) Que su "nivel" o "etiqueta" indique oportunidad ("Por Desarrollar", "Funcional", "En Desarrollo", "Latente", "weak", "moderate").
   ESTÁ ESTRICTAMENTE PROHIBIDO inventar competencias o sugerir planes para competencias "Consolidadas", "Fuertes" o "Complementarias". Si el candidato es perfecto y no tiene brechas, devuelve un arreglo vacío [].

FORMATO DE SALIDA OBLIGATORIO (sin markdown, solo JSON puro):
{
    "pasos_de_razonamiento": {
        "1_analisis_de_competencias_criticas": "Análisis de las áreas fuertes y de oportunidad en las competencias requeridas.",
        "2_justificacion_del_dictamen": "Explicación de por qué el perfil coincide con el dictamen de '{dictamen_php}'.",
        "3_identificacion_entorno_optimo": "Evaluación independiente de en qué dinámica operativa brillaría el candidato."
    },
    "reporte": {
        "resultado_global": {
            "nivel_ajuste": "Alto | Medio | Bajo | Insuficiente"
        },
        "resumen_ejecutivo": "string (máx 120 palabras. Céntrate en fortalezas operativas. OMITE dictámenes finales.)",
        "fortaleza_principal": "string (1 frase enfocada a la operación)",
        "brecha_principal": "string (1 frase propositiva sobre la principal competencia requerida baja)",
        "entorno_optimo_sugerido": "string (máx 50 palabras indicando áreas ideales en la central o corporativo)",
        "plan_desarrollo": [
            {
                "prioridad": "critical|important|normal",
                "titulo": "string (Usa el nombre exacto y etiqueta de la competencia real del JSON, ej: 'Organización — En Desarrollo')",
                "descripcion": "string (Acción recomendada táctica y aplicable, max 2 oraciones)",
                "periodo": "0 - 30 días | 30 - 60 días | 60 - 90 días"
            }
        ],
        "notas_adicionales": "string (solo si hay alertas operativas)"
    }
}
PROMPT;
    }

    protected function buildUserPrompt(array $candidateData, array $testResults, string $puesto, array $competencias = [], float $ajusteGlobal = 0.0, string $dictamen = '', float $ajusteRelativo = 0.0): string
    {
        $jsonEntrada = [
            'candidato' => [
                'nombres' => $candidateData['name'] ?? '',
                'puesto' => $puesto,
                'fecha_evaluacion' => date('Y-m-d'),
                // >>> INYECCIÓN CLAVE: El modelo usará esto como verdad absoluta <<<
                'ajuste_relativo_calculado' => $ajusteRelativo,
                'ajuste_global_seguridad' => $ajusteGlobal,
                'dictamen_asignado' => $dictamen,
                'alertas_conductuales_kostick' => $candidateData['alertas_kostick'] ?? [],
            ],
            'pruebas' => $testResults,
            'competencias_precalculadas' => $competencias
        ];

        $reglasSedyco = $this->getSedycoProfile($puesto);

        $payload = json_encode([
            'input_candidato'       => $jsonEntrada,
            'target_perfil_sedyco'  => $reglasSedyco
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return "Realiza el reporte clínico basado en la siguiente información. IMPORTANTE: El sistema ya ha determinado que el Ajuste Relativo (Cobertura del Perfil Ideal) es {$ajusteRelativo}% (métrica oficial que rige el dictamen), con un Ajuste Global de referencia de seguridad de {$ajusteGlobal}%, y el dictamen es '{$dictamen}'. Tu único trabajo es REDACTAR la justificación clínica y el plan de desarrollo congruentes con este dictamen:\n\n" . $payload;


    }

    /**
     * Mapea las directrices del manual SEDYCO v1.1 a arrays estructurados según el nivel jerárquico.
     */
    /**
     * Mapea las directrices del manual SEDYCO v1.1 a arrays estructurados según el nivel jerárquico.
     * CALIBRADO PARA SECTOR LOGÍSTICO / CENTRALES DE AUTOBUSES
     */
    private function getSedycoProfile(string $nivel): array
    {
        // Normalizamos espacios y guiones bajos para asegurar el match
        $nivelNormalizado = str_replace(' ', '_', strtoupper(trim($nivel)));

        return match ($nivelNormalizado) {
            'DIRECTIVO' => [
                'perfil' => 'Directivo (Dirección General / Área / Subdirección)',
                'Terman' => 'CI 105-125 (Fuerte en Juicio, Planeación y Negociación)',
                'Cleaver' => ['D' => '65-80%', 'I' => '60-75%', 'S' => '30-45%', 'C' => '50-65%'],
                'Kostick' => ['G' => '5-7', 'L' => '6-8', 'A' => '6-8', 'P' => '5-7', 'T' => '5-7'],
                'Moss' => ['Supervision' => '70-85%', 'Decision' => '75-90%', 'Evaluacion' => '70-85%', 'Relaciones' => '70-85%', 'Sentido_Comun' => '75-90%']
            ],
            'GERENCIA' => [
                'perfil' => 'Gerencia Corporativa / Coordinación Senior',
                'Terman' => 'CI 100-120 (Equilibrio analítico y ejecución táctica)',
                'Cleaver' => ['D' => '60-75%', 'I' => '55-70%', 'S' => '40-55%', 'C' => '55-70%'],
                'Kostick' => ['G' => '5-7', 'L' => '5-7', 'A' => '6-8', 'P' => '4-6', 'T' => '5-7'],
                'Moss' => ['Supervision' => '65-80%', 'Decision' => '70-85%', 'Evaluacion' => '65-80%', 'Relaciones' => '65-80%', 'Sentido_Comun' => '70-85%']
            ],
            'MANDO_MEDIO' => [
                'perfil' => 'Mando Medio (Gerencias B, Jefaturas)',
                'Terman' => 'CI 95-115 (Fuerte en Organización y Análisis operativo)',
                'Cleaver' => ['D' => '55-70%', 'I' => '50-65%', 'S' => '45-60%', 'C' => '60-75%'],
                'Kostick' => ['G' => '5-7', 'L' => '5-7', 'N' => '6-8', 'A' => '5-7', 'S' => '5-7', 'C' => '5-7'],
                'Moss' => ['Supervision' => '65-80%', 'Decision' => '60-75%', 'Evaluacion' => '65-80%', 'Relaciones' => '65-80%', 'Sentido_Comun' => '70-85%']
            ],
            'SUPERVISOR' => [
                'perfil' => 'Supervisor de Piso / Analista Senior',
                'Terman' => 'CI 90-105 (Funcional, pragmático)',
                'Cleaver' => ['D' => '50-65%', 'I' => '45-60%', 'S' => '60-75%', 'C' => '70-85%'],
            ],
            'ADMINISTRATIVO' => [
                'perfil' => 'Administrativo / Auxiliar / Operativo',
                'Terman' => 'CI 85-105 (Fuerte en Atención y Concentración)',
                'Cleaver' => ['D' => '20-35%', 'I' => '30-45%', 'S' => '70-85%', 'C' => '75-90%']
            ],
            default => [
                'nota' => 'No se encontró un perfil específico. Evaluar competencias operativas generales.'
            ]
        };
    }

    /**
     * Devuelve los valores ideales de Cleaver (DISC) como puntos medios.
     * CALIBRADO PARA SECTOR LOGÍSTICO / CENTRALES DE AUTOBUSES
     */
    public function getIdealCleaverForChart(string $nivel): array
    {
        $nivel = strtoupper(trim($nivel));

        $ideales = [
            'DIRECTIVO'      => ['D' => 75, 'I' => 65, 'S' => 38, 'C' => 58],
            'GERENCIA'       => ['D' => 68, 'I' => 62, 'S' => 48, 'C' => 62],
            'MANDO MEDIO'    => ['D' => 63, 'I' => 58, 'S' => 53, 'C' => 68],
            'SUPERVISOR'     => ['D' => 58, 'I' => 53, 'S' => 68, 'C' => 78],
            'ADMINISTRATIVO' => ['D' => 28, 'I' => 38, 'S' => 78, 'C' => 83],
        ];

        return $ideales[$nivel] ?? ['D' => 50, 'I' => 50, 'S' => 50, 'C' => 50];
    }
}
