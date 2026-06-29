<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class CompetencyScoringService
{
    /**
     * Matriz de Configuración Estructural (SEDYCO Calibrado)
     * peso_global: Representa el % de impacto de esta competencia en el perfil global (La suma da 1.00).
     * factores: Las variables psicométricas que componen la competencia (La suma de sus pesos debe dar ~1.00).
     */
    /**
     * Matriz de Configuración Estructural (SEDYCO Calibrado)
     * peso_global: Representa el % de impacto de esta competencia en el perfil global (La suma da 1.00).
     * factores: Las variables psicométricas que componen la competencia (La suma de sus pesos debe dar ~1.00).
     */
    private array $nivelesConfig = [
        'DIRECTIVO' => [
            // Las 12 son requeridas. Suma de peso_global = 1.00
            'Liderazgo'               => ['requerida' => true, 'peso_global' => 0.15, 'factores' => ['cleaver.d' => 0.35, 'kostick.l' => 0.35, 'moss.supervision' => 0.30]],
            'Pensamiento Estratégico' => ['requerida' => true, 'peso_global' => 0.15, 'factores' => ['terman.ci' => 0.20, 'terman.abstraccion' => 0.40, 'terman.planeacion' => 0.20, 'inverso.cleaver.s' => 0.20]],
            'Toma de Decisiones'      => ['requerida' => true, 'peso_global' => 0.15, 'factores' => ['cleaver.d' => 0.40, 'moss.decision' => 0.40, 'terman.juicio' => 0.20]],
            'Enfoque en Resultados'   => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['kostick.a' => 0.40, 'cleaver.d' => 0.30, 'kostick.n' => 0.30]],
            'Negociación'             => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['cleaver.d' => 0.35, 'cleaver.i' => 0.30, 'moss.decision' => 0.35]],
            'Manejo de Conflictos'    => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['moss.evaluacion' => 0.40, 'moss.decision' => 0.40, 'kostick.e' => 0.20]],
            'Organización'            => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['kostick.c' => 0.40, 'kostick.e' => 0.30, 'terman.organizacion' => 0.30]],
            'Análisis de Problemas'   => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['terman.ci' => 0.10, 'terman.analisis' => 0.30, 'terman.abstraccion' => 0.30, 'moss.evaluacion' => 0.30]],
            'Comunicación'            => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['cleaver.i' => 0.40, 'kostick.x' => 0.30, 'moss.relaciones' => 0.30]],
            'Resiliencia'             => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['inverso.cleaver.s' => 0.40, 'kostick.e' => 0.40, 'cleaver.d' => 0.20]],
            'Trabajo en Equipo'       => ['requerida' => true, 'peso_global' => 0.03, 'factores' => ['cleaver.i' => 0.40, 'kostick.s' => 0.30, 'moss_wess.cohesion' => 0.30]],
            'Disposición de Servicio' => ['requerida' => true, 'peso_global' => 0.02, 'factores' => ['cleaver.i' => 0.40, 'kostick.s' => 0.30, 'moss.relaciones' => 0.30]],
        ],
        'MANDO_MEDIO' => [
            // Las 12 son requeridas. Suma de peso_global = 1.00
            'Organización'            => ['requerida' => true, 'peso_global' => 0.15, 'factores' => ['cleaver.c' => 0.35, 'kostick.c' => 0.35, 'terman.organizacion' => 0.30]],
            'Manejo de Conflictos'    => ['requerida' => true, 'peso_global' => 0.15, 'factores' => ['moss.evaluacion' => 0.40, 'moss.decision' => 0.30, 'kostick.e' => 0.30]],
            'Liderazgo'               => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['moss.supervision' => 0.40, 'cleaver.d' => 0.30, 'kostick.l' => 0.30]],
            'Toma de Decisiones'      => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['moss.decision' => 0.40, 'cleaver.d' => 0.30, 'terman.juicio' => 0.30]],
            'Análisis de Problemas'   => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['terman.ci' => 0.20, 'terman.analisis' => 0.30, 'terman.abstraccion' => 0.20, 'moss.evaluacion' => 0.30]],
            'Comunicación'            => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['cleaver.i' => 0.35, 'moss.relaciones' => 0.35, 'kostick.x' => 0.30]],
            'Trabajo en Equipo'       => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['moss_wess.cohesion' => 0.40, 'cleaver.i' => 0.30, 'kostick.s' => 0.30]],
            'Enfoque en Resultados'   => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['kostick.a' => 0.30, 'kostick.n' => 0.30, 'cleaver.d' => 0.20, 'kostick.g' => 0.20]],
            'Negociación'             => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['cleaver.i' => 0.40, 'cleaver.d' => 0.30, 'moss.sentido_comun' => 0.30]],
            'Pensamiento Estratégico' => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['terman.ci' => 0.20, 'terman.abstraccion' => 0.40, 'terman.planeacion' => 0.20, 'moss_wess.innovacion' => 0.20]],
            'Resiliencia'             => ['requerida' => true, 'peso_global' => 0.03, 'factores' => ['kostick.e' => 0.40, 'moss_wess.presion' => 0.30, 'inverso.cleaver.s' => 0.30]],
            'Disposición de Servicio' => ['requerida' => true, 'peso_global' => 0.02, 'factores' => ['moss.relaciones' => 0.50, 'kostick.s' => 0.30, 'cleaver.i' => 0.20]],
        ],
        'SUPERVISOR' => [
            // 8 Requeridas (suman 1.00) y 4 Adicionales (peso 0)
            'Liderazgo'               => ['requerida' => true, 'peso_global' => 0.20, 'factores' => ['cleaver.d' => 0.40, 'moss.supervision' => 0.40, 'kostick.l' => 0.20]],
            'Organización'            => ['requerida' => true, 'peso_global' => 0.20, 'factores' => ['cleaver.c' => 0.40, 'kostick.c' => 0.40, 'terman.organizacion' => 0.20]],
            'Trabajo en Equipo'       => ['requerida' => true, 'peso_global' => 0.15, 'factores' => ['cleaver.i' => 0.40, 'moss.relaciones' => 0.30, 'kostick.s' => 0.30]],
            'Enfoque en Resultados'   => ['requerida' => true, 'peso_global' => 0.15, 'factores' => ['kostick.n' => 0.40, 'cleaver.d' => 0.30, 'kostick.g' => 0.30]],
            'Comunicación'            => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['cleaver.i' => 0.40, 'moss.relaciones' => 0.40, 'kostick.x' => 0.20]],
            'Análisis de Problemas'   => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['terman.ci' => 0.20, 'terman.analisis' => 0.30, 'terman.abstraccion' => 0.20, 'moss.evaluacion' => 0.30]],
            'Disposición de Servicio' => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['moss.relaciones' => 0.50, 'kostick.s' => 0.30, 'cleaver.i' => 0.20]],
            'Resiliencia'             => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['kostick.e' => 0.40, 'inverso.cleaver.s' => 0.40, 'cleaver.d' => 0.20]],

            // Adicionales
            'Manejo de Conflictos'    => ['requerida' => false, 'peso_global' => 0, 'factores' => ['moss.evaluacion' => 0.40, 'moss.decision' => 0.30, 'kostick.e' => 0.30]],
            'Toma de Decisiones'      => ['requerida' => false, 'peso_global' => 0, 'factores' => ['moss.decision' => 0.40, 'terman.juicio' => 0.30, 'cleaver.d' => 0.30]],
            'Negociación'             => ['requerida' => false, 'peso_global' => 0, 'factores' => ['cleaver.i' => 0.50, 'moss.sentido_comun' => 0.30, 'cleaver.d' => 0.20]],
            'Pensamiento Estratégico' => ['requerida' => false, 'peso_global' => 0, 'factores' => ['terman.ci' => 0.30, 'terman.abstraccion' => 0.40, 'terman.planeacion' => 0.30]],
        ],
        'ADMINISTRATIVO' => [
            // 7 Requeridas (suman 1.00) y 5 Adicionales (peso 0)
            'Organización'            => ['requerida' => true, 'peso_global' => 0.25, 'factores' => ['cleaver.c' => 0.40, 'kostick.c' => 0.30, 'terman.organizacion' => 0.30]],
            'Disposición de Servicio' => ['requerida' => true, 'peso_global' => 0.20, 'factores' => ['cleaver.s' => 0.40, 'moss.relaciones' => 0.40, 'kostick.s' => 0.20]],
            'Trabajo en Equipo'       => ['requerida' => true, 'peso_global' => 0.20, 'factores' => ['cleaver.s' => 0.40, 'moss_wess.cohesion' => 0.30, 'cleaver.i' => 0.30]],
            'Enfoque en Resultados'   => ['requerida' => true, 'peso_global' => 0.15, 'factores' => ['kostick.a' => 0.40, 'cleaver.c' => 0.30, 'kostick.n' => 0.30]],
            'Análisis de Problemas'   => ['requerida' => true, 'peso_global' => 0.10, 'factores' => ['terman.ci' => 0.20, 'terman.analisis' => 0.30, 'terman.abstraccion' => 0.30, 'cleaver.c' => 0.20]],
            'Comunicación'            => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['cleaver.i' => 0.40, 'moss.relaciones' => 0.30, 'cleaver.s' => 0.30]],
            'Resiliencia'             => ['requerida' => true, 'peso_global' => 0.05, 'factores' => ['inverso.cleaver.s' => 0.50, 'kostick.e' => 0.30, 'cleaver.c' => 0.20]],

            // Adicionales
            'Toma de Decisiones'      => ['requerida' => false, 'peso_global' => 0, 'factores' => ['cleaver.c' => 0.40, 'moss.decision' => 0.30, 'terman.juicio' => 0.30]],
            'Manejo de Conflictos'    => ['requerida' => false, 'peso_global' => 0, 'factores' => ['inverso.cleaver.s' => 0.40, 'moss.evaluacion' => 0.40, 'kostick.e' => 0.20]],
            'Negociación'             => ['requerida' => false, 'peso_global' => 0, 'factores' => ['cleaver.i' => 0.40, 'moss.sentido_comun' => 0.40, 'cleaver.d' => 0.20]],
            'Liderazgo'               => ['requerida' => false, 'peso_global' => 0, 'factores' => ['cleaver.d' => 0.40, 'moss.supervision' => 0.40, 'kostick.l' => 0.20]],
            'Pensamiento Estratégico' => ['requerida' => false, 'peso_global' => 0, 'factores' => ['terman.ci' => 0.30, 'terman.abstraccion' => 0.40, 'terman.planeacion' => 0.30]],
        ],
    ];

    private array $iconos = [
        'Liderazgo' => '🏴', 'Comunicación' => '💬', 'Manejo de Conflictos' => '⚖️',
        'Negociación' => '🤝', 'Organización' => '📋', 'Análisis de Problemas' => '🔍',
        'Toma de Decisiones' => '✅', 'Pensamiento Estratégico' => '🧠', 'Resiliencia' => '🛡️',
        'Enfoque en Resultados' => '🎯', 'Trabajo en Equipo' => '👥', 'Disposición de Servicio' => '💝'
    ];

    /**
     * Calcula los puntajes de las competencias para un candidato dado sus testResults.
     */
    public function calculate(string $nivel, array $testResults): array
    {
        $nivel = str_replace(' ', '_', strtoupper(trim($nivel)));
        if (!isset($this->nivelesConfig[$nivel])) {
            $nivel = 'ADMINISTRATIVO'; // Fallback seguro
        }

        $competencias = $this->nivelesConfig[$nivel];
        $valores = $this->extraerValoresNormalizados($testResults);

        $resultado = [];

        foreach ($competencias as $nombre_competencia => $config) {
            $pesoGlobal = $config['peso_global'];
            $esRequerida = $config['requerida']; // <-- Capturamos si es requerida
            $indicadores = $config['factores'];

            $scoreTotal = 0;
            $pesoTotalValido = 0;

            foreach ($indicadores as $indicador => $pesoRelativo) {
                if (isset($valores[$indicador]) && $valores[$indicador] !== null) {
                    $scoreTotal += $valores[$indicador] * $pesoRelativo;
                    $pesoTotalValido += $pesoRelativo;
                }
            }

            if ($pesoTotalValido > 0) {
                $scoreFinal = ($scoreTotal / $pesoTotalValido);
                $comp = $this->formatearCompetencia($nombre_competencia, $scoreFinal, $esRequerida); // <-- Pasamos el flag
                $comp['peso_global'] = $pesoGlobal;
                $resultado[] = $comp;
            }
        }

        return $resultado;
    }

    /**
     * Calcula el porcentaje de ajuste clínico global aplicando penalizaciones
     * a las áreas críticas deficientes (Gap Analysis estructural).
     * * @param array $competenciasEvaluadas Array de competencias devuelto por calculate()
     * @return float Porcentaje exacto (0-100)
     */
    public function calcularAjusteGlobal(array $competenciasEvaluadas): float
    {
        $ajusteTotal = 0;
        $totalPesoRegistrado = 0;

        foreach ($competenciasEvaluadas as $comp) {
            // SOLO evaluamos las competencias requeridas para el porcentaje
            if ($comp['requerida'] === true) {
                $puntaje = $comp['puntaje'];
                $pesoGlobal = $comp['peso_global'] ?? 0;

                // Penalización SEDYCO
                if ($pesoGlobal >= 0.15 && $puntaje < 40) {
                    $puntaje *= 0.8;
                }

                $ajusteTotal += ($puntaje * $pesoGlobal);
                $totalPesoRegistrado += $pesoGlobal;
            }
        }

        if ($totalPesoRegistrado > 0 && $totalPesoRegistrado < 1.0) {
            $ajusteTotal = $ajusteTotal / $totalPesoRegistrado;
        }

        return round(min(100, max(0, $ajusteTotal)), 2);
    }

    private function extraerValoresNormalizados(array $res): array
    {
        $v = [];

        // Cleaver
        $cleaverKey = $this->findKeyStr($res, 'Cleaver');
        if ($cleaverKey && isset($res[$cleaverKey]['scores'])) {
            $c = $res[$cleaverKey]['scores'];
            $v['cleaver.d'] = $c['D'] ?? null;
            $v['cleaver.i'] = $c['I'] ?? null;
            $v['cleaver.s'] = $c['S'] ?? null;
            $v['cleaver.c'] = $c['C'] ?? null;
            $v['inverso.cleaver.s'] = isset($c['S']) ? max(0, 100 - $c['S']) : null;
            $v['inverso.cleaver.d'] = isset($c['D']) ? max(0, 100 - $c['D']) : null;
        }

        // Kostick
        $kostickKey = $this->findKeyStr($res, 'Kostick');
        if ($kostickKey && isset($res[$kostickKey]['scores'])) {
            $k = $res[$kostickKey]['scores'];
            foreach (['l','n','p','i','g','a','s','x','e','c','b'] as $l) {
                $val = $k[strtoupper($l)] ?? null;
                if ($val !== null) {
                    $v["kostick.{$l}"] = min(100, max(0, ($val / 9) * 100));
                }
            }
            if (isset($k['P'])) {
                $v['kostick.p_adecuado'] = max(0, 100 - abs($k['P'] - 5) * 25);
            }
        }

        // Moss
        $mossKey = $this->findKeyStr($res, 'Moss', 'Moss Wes'); // Excluimos Moss Wess
        if ($mossKey && isset($res[$mossKey]['scores'])) {
            $m = $res[$mossKey]['scores'];
            $v['moss.supervision'] = $m['Habilidad de Supervisión']['percentage'] ?? $m['Habilidad de Supervisin']['percentage'] ?? null;
            $v['moss.decision'] = $m['Capacidad de Decisión']['percentage'] ?? $m['Capacidad de Decisin']['percentage'] ?? null;
            $v['moss.evaluacion'] = $m['Capacidad de Evaluación']['percentage'] ?? $m['Capacidad de Evaluacin']['percentage'] ?? null;
            $v['moss.relaciones'] = $m['Habilidad de Relacionarse']['percentage'] ?? null;
            $v['moss.sentido_comun'] = $m['Sentido Común y Tacto']['percentage'] ?? $m['Sentido Comn y Tacto']['percentage'] ?? null;
        }

        // Terman
        $termanKey = $this->findKeyStr($res, 'Terman');
        if ($termanKey) {
            $t = $res[$termanKey];

            // CORRECCIÓN 1: Mapeo escalonado (piecewise) para el CI
            if (isset($t['ci_score'])) {
                $ci = (float)$t['ci_score'];
                if ($ci >= 120) {
                    // Superior o Sobresaliente (120+) -> 90% a 100%
                    $v['terman.ci'] = min(100, 90 + (($ci - 120) / 21) * 10);
                } elseif ($ci >= 110) {
                    // Término Medio Alto (110-119) -> 80% a 89%
                    $v['terman.ci'] = 80 + (($ci - 110) / 9) * 9;
                } elseif ($ci >= 90) {
                    // Normal (90-109) -> 70% a 79% (Nivel funcional aceptable)
                    $v['terman.ci'] = 70 + (($ci - 90) / 19) * 9;
                } elseif ($ci >= 80) {
                    // Término Medio Bajo (80-89) -> 50% a 69% (En riesgo)
                    $v['terman.ci'] = 50 + (($ci - 80) / 9) * 19;
                } else {
                    // Inferior o Deficiente (<80) -> 0% a 49%
                    $v['terman.ci'] = max(0, ($ci / 79) * 49);
                }
            }
            // 2. Función Helper parametrizada por Baremos Clínicos Reales
            // Recibe: Puntos obtenidos, Inicio del rango "Normal", Inicio del rango "Sobresaliente", Máximo posible
            $escalarClinico = function($puntaje, $inicioNormal, $inicioSobresaliente, $maximo) {
                if ($puntaje >= $inicioSobresaliente) {
                    // Rango Sobresaliente/TM Alto (80% a 100%)
                    $divisor = ($maximo - $inicioSobresaliente) ?: 1;
                    return min(100, 80 + (($puntaje - $inicioSobresaliente) / $divisor) * 20);
                } elseif ($puntaje >= $inicioNormal) {
                    // Rango Normal Funcional (70% a 79%)
                    $divisor = ($inicioSobresaliente - 1 - $inicioNormal) ?: 1;
                    return 70 + (($puntaje - $inicioNormal) / $divisor) * 9;
                } elseif ($puntaje >= ($inicioNormal - 3)) {
                    // Rango TM Bajo / Riesgo Medio (50% a 69%)
                    return 50 + (($puntaje - ($inicioNormal - 3)) / 2) * 19;
                } else {
                    // Deficiente (< 50%)
                    $divisor = ($inicioNormal - 4) ?: 1;
                    return max(0, ($puntaje / $divisor) * 49);
                }
            };

            // 3. Extracción de Series Estratégicas (Datos exactos extraídos de PsychometricScoringService)
            if (isset($t['series'])) {
                // Serie II (Juicio) - Normal inicia: 12, TM Alto/Sobresaliente inicia: 18, Max: 22
                if (isset($t['series'][2]['puntaje'])) $v['terman.juicio'] = $escalarClinico((float)$t['series'][2]['puntaje'], 12, 18, 22);

                // Serie VI (Análisis) - Normal inicia: 9, TM Alto/Sobresaliente inicia: 15, Max: 20
                if (isset($t['series'][6]['puntaje'])) $v['terman.analisis'] = $escalarClinico((float)$t['series'][6]['puntaje'], 9, 15, 20);

                // Serie VII (Abstracción) - Normal inicia: 9, TM Alto/Sobresaliente inicia: 16, Max: 20
                if (isset($t['series'][7]['puntaje'])) $v['terman.abstraccion'] = $escalarClinico((float)$t['series'][7]['puntaje'], 9, 16, 20);

                // Serie VIII (Planeación) - Normal inicia: 8, TM Alto/Sobresaliente inicia: 13, Max: 17
                if (isset($t['series'][8]['puntaje'])) $v['terman.planeacion'] = $escalarClinico((float)$t['series'][8]['puntaje'], 8, 13, 17);

                // Serie IX (Organización) - Normal inicia: 10, TM Alto/Sobresaliente inicia: 16, Max: 18
                if (isset($t['series'][9]['puntaje'])) $v['terman.organizacion'] = $escalarClinico((float)$t['series'][9]['puntaje'], 10, 16, 18);
            }
        }

        // Moss Wess
        $mossWessKey = $this->findKeyStr($res, 'Moss Wes');
        if ($mossWessKey && isset($res[$mossWessKey]['subscales'])) {
            $mw = $res[$mossWessKey]['subscales'];
            $v['moss_wess.control'] = isset($mw['CN']['raw_score']) ? ($mw['CN']['raw_score'] / 9) * 100 : null;
            $v['moss_wess.innovacion'] = isset($mw['IN']['raw_score']) ? ($mw['IN']['raw_score'] / 9) * 100 : null;
            $v['moss_wess.presion'] = isset($mw['PR']['raw_score']) ? ($mw['PR']['raw_score'] / 9) * 100 : null;
            $v['moss_wess.cohesion'] = isset($mw['CO']['raw_score']) ? ($mw['CO']['raw_score'] / 9) * 100 : null;
            $v['moss_wess.apoyo'] = isset($mw['AP']['raw_score']) ? ($mw['AP']['raw_score'] / 9) * 100 : null;
        }

        return $v;
    }

    private function findKeyStr(array $arr, string $search, string $exclude = null): ?string
    {
        foreach (array_keys($arr) as $key) {
            if (stripos($key, $search) !== false) {
                if ($exclude && stripos($key, $exclude) !== false) {
                    continue;
                }
                return $key;
            }
        }
        return null;
    }

    private function formatearCompetencia(string $nombre, float $score, bool $esRequerida): array
    {
        $score = round($score);
        $level = 'weak';
        $label = 'Débil';

        if ($score >= 70) {
            $level = 'strong';
            $label = 'Fuerte';
        } elseif ($score >= 50) {
            $level = 'moderate';
            $label = 'Moderado';
        }

        return [
            'nombre'  => $nombre,
            'icono'   => $this->iconos[$nombre] ?? '💡',
            'nivel'   => $level,
            'etiqueta'=> $label,
            'puntaje' => $score,
            'requerida'=> $esRequerida // <-- Lo guardamos en el arreglo final
        ];
    }
    /**
     * Devuelve los valores ideales de las 12 competencias para renderizar el radar chart.
     * Calibrado clínicamente bajo el Modelo SEDYCO v1.1 y Baremos Hofstede (México).
     */
    public function getIdealCompetenciesProfile(string $nivel): array
    {
        // Normalizamos el string para que "Mando Medio" de la BD haga match con "MANDO_MEDIO" del arreglo
        $nivel = str_replace(' ', '_', strtoupper(trim($nivel)));

        $ideales = [
            'DIRECTIVO' => [
                'Liderazgo' => 90, 'Pensamiento Estratégico' => 90, 'Toma de Decisiones' => 85,
                'Enfoque en Resultados' => 85, 'Negociación' => 80, 'Manejo de Conflictos' => 80,
                'Análisis de Problemas' => 85, 'Resiliencia' => 80, 'Comunicación' => 75,
                'Organización' => 70, 'Trabajo en Equipo' => 65, 'Disposición de Servicio' => 60
            ],
            'MANDO_MEDIO' => [
                'Liderazgo' => 80, 'Organización' => 80, 'Manejo de Conflictos' => 80,
                'Trabajo en Equipo' => 80, 'Comunicación' => 80, 'Toma de Decisiones' => 80,
                'Análisis de Problemas' => 80, 'Enfoque en Resultados' => 80, 'Negociación' => 75,
                'Pensamiento Estratégico' => 75, 'Resiliencia' => 75, 'Disposición de Servicio' => 75
            ],
            'SUPERVISOR' => [
                'Organización' => 85, 'Trabajo en Equipo' => 85, 'Enfoque en Resultados' => 80,
                'Disposición de Servicio' => 80, 'Comunicación' => 75, 'Análisis de Problemas' => 75,
                'Resiliencia' => 75, 'Manejo de Conflictos' => 70, 'Liderazgo' => 65,
                'Toma de Decisiones' => 65, 'Negociación' => 60, 'Pensamiento Estratégico' => 60
            ],
            'ADMINISTRATIVO' => [
                'Organización' => 90, 'Disposición de Servicio' => 90, 'Trabajo en Equipo' => 85,
                'Enfoque en Resultados' => 80, 'Análisis de Problemas' => 75, 'Resiliencia' => 75,
                'Comunicación' => 70, 'Toma de Decisiones' => 60, 'Manejo de Conflictos' => 60,
                'Pensamiento Estratégico' => 50, 'Negociación' => 50, 'Liderazgo' => 45
            ],
        ];

        // Retorna el ideal del puesto, o un "plano de 70" si el puesto no existe en la matriz
        return $ideales[$nivel] ?? array_fill_keys(array_keys($ideales['ADMINISTRATIVO']), 70);
    }
}
