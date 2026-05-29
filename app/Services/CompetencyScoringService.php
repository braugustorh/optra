<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class CompetencyScoringService
{
    private array $nivelesPesos = [
        'DIRECTIVO' => [
            'Liderazgo' => ['cleaver.d' => 0.45, 'kostick.l' => 0.25, 'moss.supervision' => 0.30],
            'Comunicación' => ['cleaver.i' => 0.30, 'kostick.n' => 0.15, 'moss.relaciones' => 0.35, 'moss.sentido_comun' => 0.20],
            'Manejo de Conflictos' => ['moss.evaluacion' => 0.40, 'moss.decision' => 0.30, 'inverso.cleaver.s' => 0.15, 'kostick.p_adecuado' => 0.15],
            'Negociación' => ['cleaver.d' => 0.35, 'cleaver.i' => 0.20, 'moss.decision' => 0.25, 'moss.sentido_comun' => 0.20],
            'Organización' => ['cleaver.c' => 0.30, 'kostick.i' => 0.35, 'terman.ci' => 0.15, 'moss_wess.control' => 0.20],
            'Análisis de Problemas' => ['terman.ci' => 0.40, 'terman.abstraccion' => 0.30, 'moss.evaluacion' => 0.30],
            'Toma de Decisiones' => ['cleaver.d' => 0.40, 'moss.decision' => 0.30, 'terman.ci' => 0.20, 'inverso.cleaver.s' => 0.10],
            'Pensamiento Estratégico' => ['terman.ci' => 0.35, 'terman.abstraccion' => 0.35, 'inverso.cleaver.s' => 0.15, 'moss_wess.innovacion' => 0.15],
            'Resiliencia' => ['inverso.cleaver.s' => 0.40, 'moss_wess.presion' => 0.30, 'kostick.g' => 0.30],
            'Enfoque en Resultados' => ['kostick.a' => 0.35, 'kostick.g' => 0.25, 'cleaver.d' => 0.20, 'kostick.n' => 0.20],
            'Trabajo en Equipo' => ['cleaver.i' => 0.20, 'moss_wess.cohesion' => 0.30, 'kostick.s' => 0.20, 'moss_wess.apoyo' => 0.30],
            'Disposición de Servicio' => ['cleaver.i' => 0.20, 'moss_wess.apoyo' => 0.40, 'kostick.s' => 0.15, 'moss.relaciones' => 0.25],
        ],
        'MANDO MEDIO' => [
            'Liderazgo' => ['cleaver.d' => 0.40, 'kostick.l' => 0.30, 'moss.supervision' => 0.30],
            'Comunicación' => ['cleaver.i' => 0.40, 'kostick.n' => 0.20, 'moss.relaciones' => 0.40],
            'Manejo de Conflictos' => ['moss.evaluacion' => 0.50, 'moss.decision' => 0.30, 'inverso.cleaver.s' => 0.20],
            'Negociación' => ['cleaver.d' => 0.30, 'cleaver.i' => 0.30, 'moss.decision' => 0.40],
            'Organización' => ['cleaver.c' => 0.40, 'kostick.i' => 0.40, 'terman.ci' => 0.20],
            'Análisis de Problemas' => ['terman.ci' => 0.50, 'moss.evaluacion' => 0.50],
            'Toma de Decisiones' => ['cleaver.d' => 0.40, 'moss.decision' => 0.40, 'terman.ci' => 0.20],
            'Pensamiento Estratégico' => ['terman.ci' => 0.40, 'terman.abstraccion' => 0.30, 'inverso.cleaver.s' => 0.30],
            'Resiliencia' => ['inverso.cleaver.s' => 0.50, 'moss_wess.presion' => 0.50],
            'Enfoque en Resultados' => ['kostick.a' => 0.40, 'kostick.g' => 0.30, 'kostick.n' => 0.30],
            'Trabajo en Equipo' => ['cleaver.i' => 0.30, 'inverso.cleaver.s' => 0.20, 'kostick.s' => 0.20, 'moss_wess.cohesion' => 0.30],
            'Disposición de Servicio' => ['cleaver.i' => 0.25, 'inverso.cleaver.s' => 0.25, 'moss_wess.apoyo' => 0.50],
        ],
        'SUPERVISOR' => [
            'Liderazgo' => ['cleaver.d' => 0.35, 'moss.supervision' => 0.40, 'kostick.l' => 0.25],
            'Comunicación' => ['cleaver.i' => 0.40, 'moss.relaciones' => 0.35, 'kostick.s' => 0.25],
            'Manejo de Conflictos' => ['moss.evaluacion' => 0.50, 'moss.decision' => 0.30, 'inverso.cleaver.s' => 0.20],
            'Organización' => ['cleaver.c' => 0.50, 'kostick.i' => 0.30, 'terman.ci' => 0.20],
            'Análisis de Problemas' => ['terman.ci' => 0.55, 'moss.evaluacion' => 0.45],
            'Toma de Decisiones' => ['cleaver.d' => 0.40, 'terman.ci' => 0.30, 'moss.decision' => 0.30],
            'Resiliencia' => ['inverso.cleaver.s' => 0.60, 'moss_wess.presion' => 0.40],
            'Enfoque en Resultados' => ['kostick.a' => 0.40, 'kostick.g' => 0.30, 'cleaver.c' => 0.30],
            'Trabajo en Equipo' => ['cleaver.i' => 0.25, 'inverso.cleaver.s' => 0.25, 'kostick.s' => 0.25, 'moss_wess.cohesion' => 0.25],
            'Disposición de Servicio' => ['cleaver.i' => 0.30, 'inverso.cleaver.s' => 0.30, 'moss_wess.apoyo' => 0.40],
        ],
        'ADMINISTRATIVO' => [
            'Comunicación' => ['cleaver.i' => 0.60, 'inverso.cleaver.s' => 0.40],
            'Organización' => ['cleaver.c' => 0.55, 'terman.ci' => 0.25, 'kostick.i' => 0.20],
            'Análisis de Problemas' => ['terman.ci' => 0.70, 'moss.evaluacion' => 0.30],
            'Resiliencia' => ['inverso.cleaver.s' => 0.70, 'moss_wess.presion' => 0.30],
            'Enfoque en Resultados' => ['cleaver.c' => 0.50, 'terman.ci' => 0.30, 'kostick.a' => 0.20],
            'Trabajo en Equipo' => ['cleaver.i' => 0.40, 'inverso.cleaver.s' => 0.40, 'moss_wess.cohesion' => 0.20],
            'Disposición de Servicio' => ['cleaver.i' => 0.30, 'inverso.cleaver.s' => 0.30, 'moss_wess.apoyo' => 0.40],
        ],
    ];

    private array $iconos = [
        'Liderazgo' => '🏴', 'Comunicación' => '💬', 'Manejo de Conflictos' => '⚖️',
        'Negociación' => '🤝', 'Organización' => '📋', 'Análisis de Problemas' => '🔍',
        'Toma de Decisiones' => '✅', 'Pensamiento Estratégico' => '🧠', 'Resiliencia' => '🛡️',
        'Enfoque en Resultados' => '🎯', 'Trabajo en Equipo' => '👥', 'Disposición de Servicio' => '💝'
    ];

    public function calculate(string $nivel, array $testResults): array
    {
        $nivel = strtoupper(trim($nivel));
        if (!isset($this->nivelesPesos[$nivel])) {
            $nivel = 'MANDO MEDIO'; // fallback estandar
        }

        $competencias = $this->nivelesPesos[$nivel];
        $valores = $this->extraerValoresNormalizados($testResults);

        $resultado = [];

        foreach ($competencias as $nombre_competencia => $indicadores) {
            $scoreTotal = 0;
            $pesoTotalValido = 0;

            foreach ($indicadores as $indicador => $peso) {
                if (isset($valores[$indicador]) && $valores[$indicador] !== null) {
                    $scoreTotal += $valores[$indicador] * $peso;
                    $pesoTotalValido += $peso;
                }
            }

            if ($pesoTotalValido > 0) {
                // Redistribuimos el peso si faltan pruebas
                $scoreFinal = ($scoreTotal / $pesoTotalValido);
                $resultado[] = $this->formatearCompetencia($nombre_competencia, $scoreFinal);
            }
        }

        return $resultado;
    }

    private function extraerValoresNormalizados(array $res): array
    {
        $v = [];

        // Cleaver
        // Acepta "Cleaver", "Cleaver (DISC)", etc.
        $cleaverKey = $this->findKeyStr($res, 'Cleaver');
        if ($cleaverKey && isset($res[$cleaverKey]['scores'])) {
            $c = $res[$cleaverKey]['scores'];
            $v['cleaver.d'] = $c['D'] ?? null;
            $v['cleaver.i'] = $c['I'] ?? null;
            $v['cleaver.s'] = $c['S'] ?? null;
            $v['cleaver.c'] = $c['C'] ?? null;
            $v['inverso.cleaver.s'] = isset($c['S']) ? max(0, 100 - $c['S']) : null;
        }

        // Kostick
        $kostickKey = $this->findKeyStr($res, 'Kostick');
        if ($kostickKey && isset($res[$kostickKey]['scores'])) {
            $k = $res[$kostickKey]['scores'];
            foreach (['l','n','p','i','g','a','s'] as $l) {
                $val = $k[strtoupper($l)] ?? null;
                if ($val !== null) {
                    $v["kostick.{$l}"] = min(100, max(0, (($val - 1) / 8) * 100)); // 1-9 to 0-100
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
            // Se manejan posibles diferencias de acentos (utf8 encoding) al extraer del backend
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
            if (isset($t['ci_score'])) {
                $v['terman.ci'] = min(100, max(0, (($t['ci_score'] - 80) / 61) * 100)); // 80->0, 141->100
            }
            if (isset($t['series'][7]['puntaje'])) {
                $v['terman.abstraccion'] = min(100, max(0, ($t['series'][7]['puntaje'] / 20) * 100));
            }
            // A veces el indice es literal '7' en vez de int 7, pero php resuelve esto.
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

    private function formatearCompetencia(string $nombre, float $score): array
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
            'puntaje' => $score // No se muestra, pero util para calculos (ej. Ajuste % fallback)
        ];
    }
}

