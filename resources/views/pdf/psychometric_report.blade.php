<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Psicométrico - {{ $candidateName ?? 'Candidato' }}</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Reglas obligatorias para que los colores de fondo se impriman en el PDF */
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; background-color: white; }

        /* Evitar que las tarjetas se rompan a la mitad entre dos páginas */
        .avoid-break { page-break-inside: avoid; }

        /* Forzar saltos de página explícitos si se requiere */
        .page-break { page-break-before: always; }
    </style>
</head>
<body class="p-8 max-w-5xl mx-auto">

{{-- ========================================================= --}}
{{-- ENCABEZADO FORMAL DEL PDF                                 --}}
{{-- ========================================================= --}}
<div class="border-b-2 border-indigo-600 pb-4 mb-8 flex justify-between items-end avoid-break">
    <div>
        <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight">Reporte Psicométrico</h1>
        <p class="text-lg text-gray-600 mt-1">{{ $results['test_name'] ?? 'Evaluación' }}</p>
    </div>
    <div class="text-right">
        <p class="text-sm font-bold text-gray-800">Candidato: <span class="font-normal">{{ $candidateName ?? 'No especificado' }}</span></p>
        <p class="text-sm font-bold text-gray-800">Fecha: <span class="font-normal">{{ $date ?? date('d/m/Y') }}</span></p>
    </div>
</div>

<div class="space-y-6">

    {{-- ========================================================= --}}
    {{-- PREPARACIÓN DE DATOS (PHP)                                --}}
    {{-- ========================================================= --}}
    @php
        // 1. Helper de Colores (Estilos inline para máxima compatibilidad)
        $getColorHex = function($range) {
            $range = strtolower($range ?? '');
            if (str_contains($range, 'excelente') || str_contains($range, 'muy superior')) return ['bg' => '#10b981', 'border' => '#059669', 'text' => '#064e3b', 'light' => '#d1fae5'];
            if (str_contains($range, 'superior') || str_contains($range, 'buena')) return ['bg' => '#34d399', 'border' => '#10b981', 'text' => '#065f46', 'light' => '#ecfdf5'];
            if (str_contains($range, 'término medio') || str_contains($range, 'promedio') || str_contains($range, 'tiende a buena')) return ['bg' => '#3b82f6', 'border' => '#2563eb', 'text' => '#1e3a8a', 'light' => '#dbeafe'];
            if (str_contains($range, 'inferior') || str_contains($range, 'bajo') || str_contains($range, 'mala')) return ['bg' => '#f59e0b', 'border' => '#d97706', 'text' => '#78350f', 'light' => '#fef3c7'];
            if (str_contains($range, 'deficiente') || str_contains($range, 'deficitaria')) return ['bg' => '#ef4444', 'border' => '#dc2626', 'text' => '#7f1d1d', 'light' => '#fee2e2'];
            return ['bg' => '#6b7280', 'border' => '#4b5563', 'text' => '#1f2937', 'light' => '#f3f4f6'];
        };

        // 2. Identificar el Tipo de Prueba
        $testName  = $results['test_name'] ?? '';
        $isCleaver = $testName === 'Cleaver (DISC)';
        $isKostick = str_contains($testName, 'Kostick');
        $isTerman  = str_contains($testName, 'Terman');

        // 3. Extracción de Datos
        if ($isCleaver) {
            $cleaverPercentiles = $results['percentiles'] ?? [];
            $cleaverInterpretations = $results['interpretations'] ?? [];
            $cleaverRaw = $results['raw_scores'] ?? [];
            $cleaverSummary = $results['summary'] ?? '';
            $discDomains = ['D' => 'Empuje', 'I' => 'Influencia', 'S' => 'Constancia', 'C' => 'Apego'];
        } elseif ($isKostick) {
            $kostickScores        = $results['scores'] ?? [];
            $kostickFactores      = $results['factoresAgrupados'] ?? [];
            $kostickInterpretacion= $results['kostickInterpretation'] ?? [];
            $kostickOrder         = ['G','L','I','T','V','S','R','D','C','E','Z','K','F','W','N','A','P','X','B','O'];
        } elseif ($isTerman) {
            $termanBruto     = $results['puntaje_bruto'] ?? 0;
            $termanCI        = $results['ci_score'] ?? '—';
            $termanClasCI    = $results['clasificacion_ci'] ?? 'Sin clasificación';
            $termanClasAp    = $results['clasificacion_capacidad'] ?? 'Sin clasificación';
            $termanSeries    = $results['series'] ?? [];
            $termanResumen   = $results['resumen'] ?? '';

            // Helper de color específico para clasificaciones Terman
            $getTermanColor = function(string $c): array {
                $cl = strtolower($c);
                if (str_contains($cl, 'sobresaliente'))    return ['bg' => '#10b981', 'border' => '#059669', 'text' => '#064e3b', 'light' => '#d1fae5'];
                if (str_contains($cl, 'superior'))         return ['bg' => '#34d399', 'border' => '#10b981', 'text' => '#065f46', 'light' => '#ecfdf5'];
                if (str_contains($cl, 'medio alto'))       return ['bg' => '#3b82f6', 'border' => '#2563eb', 'text' => '#1e3a8a', 'light' => '#dbeafe'];
                if (str_contains($cl, 'normal'))           return ['bg' => '#6366f1', 'border' => '#4f46e5', 'text' => '#312e81', 'light' => '#e0e7ff'];
                if (str_contains($cl, 'medio bajo'))       return ['bg' => '#f59e0b', 'border' => '#d97706', 'text' => '#78350f', 'light' => '#fef3c7'];
                if (str_contains($cl, 'inferior'))         return ['bg' => '#f97316', 'border' => '#ea580c', 'text' => '#7c2d12', 'light' => '#ffedd5'];
                if (str_contains($cl, 'deficiente'))       return ['bg' => '#ef4444', 'border' => '#dc2626', 'text' => '#7f1d1d', 'light' => '#fee2e2'];
                return ['bg' => '#6b7280', 'border' => '#4b5563', 'text' => '#1f2937', 'light' => '#f3f4f6'];
            };

            $termanCIColors   = $getTermanColor($termanClasCI);
            $termanApColors   = $getTermanColor($termanClasAp);
        } else {
            $globalRange = $results['range'] ?? 'N/A';
            $globalColors = $getColorHex($globalRange);
            $dimensions = $results['dimensions'] ?? ($results['scores'] ?? []);
            $subscales = $results['subscales'] ?? [];
            $relationsMap = [
                'Relaciones' => ['IM', 'CO', 'AP'],
                'Auto-realización' => ['AU', 'OR', 'PR'],
                'Estabilidad/Cambio' => ['CL', 'CN', 'IN', 'CF']
            ];
        }
    @endphp

    {{-- ========================================================= --}}
    {{-- RENDERIZADO CLEAVER (DISC)                                --}}
    {{-- ========================================================= --}}
    @if($isCleaver && !empty($cleaverPercentiles) && !empty($cleaverInterpretations))


        {{-- ALERTAS DE VALIDEZ Y APLANAMIENTO --}}
        @if(isset($cleaverIsValid) && !$cleaverIsValid)
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-4 rounded-md shadow-sm avoid-break">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                    </div>
                    <div class="ml-3"><p class="text-sm text-red-700 font-bold">Alerta de Validez: Las respuestas del candidato presentan inconsistencias. Los resultados pueden no ser exactos.</p></div>
                </div>
            </div>
        @endif

        @if(isset($cleaverInterpretations['alert']))
            <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6 rounded-md shadow-sm avoid-break">
                <p class="text-sm text-yellow-700 font-bold">⚠️ {{ $cleaverInterpretations['alert'] }}</p>
            </div>
        @endif

        {{-- CABECERA GENERAL --}}
        <div class="relative overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm avoid-break mb-8">
            <div class="absolute left-0 top-0 bottom-0 w-2" style="background-color: #4f46e5;"></div>
            <div class="p-5 pl-7 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 tracking-tight">Perfil DISC</h3>
                    <p class="text-sm text-gray-500 mt-1">Análisis de Motivación, Presión y Comportamiento Normal.</p>
                </div>
                <span class="text-sm font-bold text-indigo-700 bg-indigo-50 px-4 py-2 rounded-full border border-indigo-100">
                {{ $cleaverSummary }}
            </span>
            </div>
        </div>

        {{-- GRÁFICAS (Se mantiene tu misma lógica visual matemática) --}}
        <div class="grid grid-cols-3 gap-4 avoid-break">
            @php
                $renderDiscLineChart = function($title, $type, $data, $raw_data) {
                    $color = match($type) {
                        'M' => '#3b82f6', 'L' => '#ef4444', 'T' => '#10b981', default => '#6b7280'
                    };

                    $html = '<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex flex-col items-center h-full">';
                    $html .= '<h4 class="font-bold text-gray-800 mb-4 border-b w-full text-center pb-2 text-sm">' . $title . '</h4>';

                    $html .= '<div class="w-full max-w-[200px] aspect-square relative mx-auto">';
                    $html .= '<svg viewBox="0 -10 100 120" class="w-full h-full overflow-visible">';

                    foreach([0, 25, 50, 75, 100] as $gridY) {
                        $yPos = 100 - $gridY;
                        $html .= '<line x1="0" y1="'.$yPos.'" x2="100" y2="'.$yPos.'" stroke="#f3f4f6" stroke-width="0.5"/>';
                        $html .= '<text x="-5" y="'.$yPos.'" font-size="4" fill="#9ca3af" text-anchor="end" dominant-baseline="middle">'.$gridY.'</text>';
                    }
                    $html .= '<line x1="0" y1="50" x2="100" y2="50" stroke="#d1d5db" stroke-width="1" stroke-dasharray="2,2"/>';

                    $pts = [];
                    $xPositions = ['D' => 10, 'I' => 36, 'S' => 63, 'C' => 90];

                    foreach(['D', 'I', 'S', 'C'] as $let) {
                        $percent = max(0, min(100, $data[$let] ?? 0));
                        $y = 100 - $percent;
                        $x = $xPositions[$let];
                        $pts[] = "{$x},{$y}";
                    }
                    $pointsStr = implode(' ', $pts);

                    $html .= '<polyline points="'.$pointsStr.'" fill="none" stroke="'.$color.'" stroke-width="1.5" stroke-linejoin="round"/>';

                    foreach(['D', 'I', 'S', 'C'] as $let) {
                        $percent = max(0, min(100, $data[$let] ?? 0));
                        $y = 100 - $percent;
                        $x = $xPositions[$let];

                        $html .= '<circle cx="'.$x.'" cy="'.$y.'" r="2.5" fill="'.$color.'" stroke="#ffffff" stroke-width="0.5"/>';
                        $html .= '<text x="'.$x.'" y="'.($y - 4).'" font-size="5" fill="#374151" font-weight="bold" text-anchor="middle">'.$percent.'%</text>';
                        $html .= '<text x="'.$x.'" y="110" font-size="6" fill="#4b5563" font-weight="bold" text-anchor="middle">'.$let.'</text>';
                    }

                    $html .= '</svg></div></div>';
                    return $html;
                };
            @endphp

            {!! $renderDiscLineChart('Motivación (M)', 'M', $cleaverPercentiles['M'], $cleaverRaw['M']) !!}
            {!! $renderDiscLineChart('Bajo Presión (L)', 'L', $cleaverPercentiles['L'], $cleaverRaw['L']) !!}
            {!! $renderDiscLineChart('Normal (T)', 'T', $cleaverPercentiles['T'], $cleaverRaw['T']) !!}
        </div>

        {{-- LEYENDA --}}
        <div class="flex justify-center gap-6 text-xs text-gray-500 mt-4 bg-gray-50 py-2 rounded-lg border border-gray-100 avoid-break mb-8">
            <div class="flex items-center gap-1"><div class="w-3 h-3 rounded-sm bg-red-500"></div> <b>D</b>ominancia</div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 rounded-sm bg-amber-500"></div> <b>I</b>nfluencia</div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 rounded-sm bg-emerald-500"></div> <b>S</b>teadiness</div>
            <div class="flex items-center gap-1"><div class="w-3 h-3 rounded-sm bg-blue-500"></div> <b>C</b>ompliance</div>
        </div>

        {{-- INTERPRETACIÓN DINÁMICA POR DOMINIOS --}}
        <div>
            <h3 class="text-xl font-bold text-gray-800 mb-4 border-b border-gray-200 pb-2 avoid-break">Interpretación por Dominios</h3>
            <div class="space-y-6">

                {{-- Iteramos sobre las letras usando los datos inyectados por el backend --}}
                @foreach(['D', 'I', 'S', 'C'] as $letra)
                    @php
                        $info = $cleaverInterpretations[$letra];
                    @endphp

                    <div class="border border-gray-200 rounded-lg p-5 bg-gray-50 avoid-break shadow-sm">
                        <div class="mb-4 pb-3 border-b border-gray-200">
                            <h4 class="text-lg font-black text-gray-900">
                                <span class="bg-gray-800 text-white px-2 py-0.5 rounded mr-2">{{ $letra }}</span>
                                {{ $info['name'] }}
                            </h4>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-bold uppercase text-blue-600 bg-blue-100 px-2 py-0.5 rounded">Motivación (M)</span>
                                    <span class="text-sm font-bold text-gray-700">{{ $info['motivacion']['score'] }}%</span>
                                </div>
                                <div class="text-sm text-gray-700 bg-white p-3 rounded border border-gray-100 mt-2 h-full shadow-sm">
                                    <p><strong>{{ $info['motivacion']['title'] }}:</strong> {{ $info['motivacion']['text'] }}</p>
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs font-bold uppercase text-red-600 bg-red-100 px-2 py-0.5 rounded">Presión (L)</span>
                                    <span class="text-sm font-bold text-gray-700">{{ $info['presion']['score'] }}%</span>
                                </div>
                                <div class="text-sm text-gray-700 bg-white p-3 rounded border border-gray-100 mt-2 h-full shadow-sm">
                                    <p><strong>{{ $info['presion']['title'] }}:</strong> {{ $info['presion']['text'] }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="text-xs font-bold text-gray-500 uppercase">Comportamiento Diario (T):</span>
                                <span class="text-sm font-black text-emerald-600">{{ $info['diario']['score'] }}%</span>
                                <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded">{{ $info['diario']['title'] }}</span>
                            </div>
                            <div class="bg-white p-3 rounded border border-gray-100 shadow-sm text-sm text-gray-700">
                                <p class="mb-1"><strong>Rasgos de Personalidad:</strong> {{ $info['diario']['traits'] }}</p>
                                <p><strong>Comportamiento:</strong> {{ $info['diario']['behavior'] }}</p>
                            </div>
                        </div>

                    </div>
                @endforeach

            </div>
        </div>


        {{-- ========================================================= --}}
        {{-- RENDERIZADO KOSTICK                                       --}}
        {{-- ========================================================= --}}
    @elseif($isKostick && !empty($kostickScores))

        {{-- ── CABECERA ────────────────────────────────────────────── --}}
        <div class="relative overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm avoid-break mb-6">
            <div class="absolute left-0 top-0 bottom-0 w-2" style="background-color: #4f46e5;"></div>
            <div class="p-5 pl-7 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 tracking-tight">Perfil de Personalidad Kostick</h3>
                    <p class="text-sm text-gray-500 mt-1">20 dimensiones agrupadas en 7 factores de comportamiento y preferencias laborales.</p>
                </div>
                <div class="flex gap-2 text-[10px] font-bold">
                    <span class="px-2 py-1 rounded bg-indigo-100 text-indigo-800">Alto 7-9</span>
                    <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-800">Medio 4-6</span>
                    <span class="px-2 py-1 rounded bg-amber-100 text-amber-800">Bajo 1-3</span>
                </div>
            </div>
        </div>

        {{-- ── GRÁFICA RADAR + BARRAS RESUMEN ──────────────────────── --}}
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 mb-6">

            {{-- RADAR --}}
            <div class="md:col-span-5 bg-white border border-gray-200 rounded-xl p-5 shadow-sm flex flex-col items-center avoid-break">
                <h4 class="font-bold text-gray-700 mb-4 text-sm">Gráfica Radar — 20 Dimensiones</h4>
                @php
                    $cx = 150; $cy = 150; $rMax = 110;
                    $numPoints = count($kostickOrder);
                    $angleStep = M_PI * 2 / $numPoints;

                    $radarHtml = '<div class="w-full max-w-[300px] aspect-square">';
                    $radarHtml .= '<svg viewBox="0 0 300 300" class="w-full h-full overflow-visible">';

                    for ($i = 1; $i <= 9; $i++) {
                        $r = ($rMax / 9) * $i;
                        $stroke = ($i === 5) ? '#6366f1' : '#e5e7eb';
                        $sw = ($i === 5) ? '1.5' : '0.8';
                        $radarHtml .= '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'" fill="none" stroke="'.$stroke.'" stroke-width="'.$sw.'"/>';
                        if (in_array($i, [3, 6, 9])) {
                            $radarHtml .= '<text x="'.$cx.'" y="'.($cy - $r - 3).'" font-size="7" fill="#9ca3af" text-anchor="middle">'.$i.'</text>';
                        }
                    }

                    $polyPoints = [];
                    foreach ($kostickOrder as $index => $let) {
                        $angle = $index * $angleStep - (M_PI / 2);
                        $xEnd = $cx + $rMax * cos($angle);
                        $yEnd = $cy + $rMax * sin($angle);
                        $radarHtml .= '<line x1="'.$cx.'" y1="'.$cy.'" x2="'.$xEnd.'" y2="'.$yEnd.'" stroke="#f3f4f6" stroke-width="0.8"/>';
                        $labelR = $rMax + 17;
                        $lx = $cx + $labelR * cos($angle);
                        $ly = $cy + $labelR * sin($angle);
                        $align = $lx > $cx + 5 ? 'start' : ($lx < $cx - 5 ? 'end' : 'middle');
                        $radarHtml .= '<text x="'.$lx.'" y="'.($ly + 4).'" font-size="11" font-weight="bold" fill="#374151" text-anchor="'.$align.'">'.$let.'</text>';
                        $score = max(0, min(9, $kostickScores[$let] ?? 0));
                        $scoreR = ($rMax / 9) * $score;
                        $px = $cx + $scoreR * cos($angle);
                        $py = $cy + $scoreR * sin($angle);
                        $polyPoints[] = "{$px},{$py}";
                    }

                    $radarHtml .= '<polygon points="'.implode(' ', $polyPoints).'" fill="rgba(99,102,241,0.15)" stroke="#4f46e5" stroke-width="2" stroke-linejoin="round"/>';

                    foreach ($kostickOrder as $index => $let) {
                        $angle = $index * $angleStep - (M_PI / 2);
                        $score = max(0, min(9, $kostickScores[$let] ?? 0));
                        $scoreR = ($rMax / 9) * $score;
                        $px = $cx + $scoreR * cos($angle);
                        $py = $cy + $scoreR * sin($angle);
                        $radarHtml .= '<circle cx="'.$px.'" cy="'.$py.'" r="3" fill="#4f46e5" stroke="#fff" stroke-width="1.5"/>';
                    }

                    $radarHtml .= '</svg></div>';
                    echo $radarHtml;
                @endphp
            </div>

            {{-- BARRAS RESUMEN POR FACTOR ──────────────────────────── --}}
            <div class="md:col-span-7 bg-white border border-gray-200 rounded-xl p-5 shadow-sm avoid-break">
                <h4 class="font-bold text-gray-700 mb-4 text-sm">Puntuaciones por Factor</h4>
                <div class="space-y-4">
                    @foreach($kostickFactores as $factorNum => $factorData)
                        @php
                            $factorTitle = $factorData['Título'] ?? ($factorData['Titulo'] ?? "Factor {$factorNum}");
                            $factorDims  = $factorData['Dimension'] ?? [];
                        @endphp
                        <div class="avoid-break">
                            <p class="text-[10px] font-bold text-indigo-700 uppercase tracking-widest mb-1.5">
                                {{ $factorNum }}. {{ $factorTitle }}
                            </p>
                            <div class="space-y-1.5 pl-2">
                                @foreach($factorDims as $let)
                                    @php
                                        $sc = $kostickScores[$let] ?? 0;
                                        $pct = ($sc / 9) * 100;
                                        $barColor = $sc >= 7 ? '#6366f1' : ($sc >= 4 ? '#34d399' : '#fbbf24');
                                        $txtColor = $sc >= 7 ? '#4f46e5' : ($sc >= 4 ? '#059669' : '#d97706');
                                        $interpTitle = $kostickInterpretacion[$let][$sc]['detalle'] ?? $let;
                                        // Truncar a 45 chars para no desbordar
                                        $interpTitle = strlen($interpTitle) > 50 ? substr($interpTitle, 0, 50).'…' : $interpTitle;
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <span class="w-5 h-5 rounded flex items-center justify-center text-[9px] font-black text-white shrink-0" style="background-color: {{ $barColor }};">{{ $let }}</span>
                                        <div class="flex-1">
                                            <div class="flex justify-between mb-0.5">
                                                <span class="text-[9px] text-gray-500 truncate">{{ $interpTitle }}</span>
                                                <span class="text-[9px] font-black shrink-0 ml-1" style="color:{{ $txtColor }}">{{ $sc }}/9</span>
                                            </div>
                                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                                <div class="h-1.5 rounded-full" style="width:{{ $pct }}%; background-color:{{ $barColor }};"></div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ── INTERPRETACIÓN DETALLADA POR FACTORES ───────────────── --}}
        <div class="page-break"></div>

        <div class="mb-6 avoid-break">
            <h3 class="text-xl font-black text-gray-900 uppercase tracking-tight border-b-2 border-indigo-500 pb-2 mb-1">
                Interpretación Detallada por Factores
            </h3>
            <p class="text-xs text-gray-400">La glosa corresponde a la puntuación obtenida (1–9) en cada dimensión.</p>
        </div>

        @foreach($kostickFactores as $factorNum => $factorData)
            @php
                $factorTitle = $factorData['Título'] ?? ($factorData['Titulo'] ?? "Factor {$factorNum}");
                $factorDims  = $factorData['Dimension'] ?? [];
                // Colores por número de factor
                $fColors = [
                    1 => ['hdr' => '#4f46e5', 'light' => '#eef2ff', 'text' => '#3730a3'],
                    2 => ['hdr' => '#0891b2', 'light' => '#ecfeff', 'text' => '#164e63'],
                    3 => ['hdr' => '#059669', 'light' => '#ecfdf5', 'text' => '#064e3b'],
                    4 => ['hdr' => '#d97706', 'light' => '#fef3c7', 'text' => '#78350f'],
                    5 => ['hdr' => '#dc2626', 'light' => '#fef2f2', 'text' => '#7f1d1d'],
                    6 => ['hdr' => '#7c3aed', 'light' => '#f5f3ff', 'text' => '#4c1d95'],
                    7 => ['hdr' => '#0f766e', 'light' => '#f0fdfa', 'text' => '#134e4a'],
                ];
                $fc = $fColors[$factorNum] ?? ['hdr' => '#6b7280', 'light' => '#f9fafb', 'text' => '#1f2937'];
            @endphp

            <div class="rounded-xl border border-gray-200 overflow-hidden shadow-sm mb-5 avoid-break">
                {{-- Header del factor --}}
                <div class="px-5 py-3 flex items-center gap-3" style="background-color: {{ $fc['hdr'] }};">
                    <span class="w-7 h-7 rounded-full bg-white flex items-center justify-center text-xs font-black" style="color: {{ $fc['hdr'] }};">{{ $factorNum }}</span>
                    <h4 class="text-base font-black text-white uppercase tracking-wide">{{ $factorTitle }}</h4>
                    <div class="ml-auto flex gap-1.5">
                        @foreach($factorDims as $let)
                            <span class="w-6 h-6 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-xs font-black text-white">{{ $let }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Grid de dimensiones --}}
                <div style="background-color: {{ $fc['light'] }};" class="p-4">
                    <div class="{{ count($factorDims) >= 3 ? 'grid grid-cols-2 gap-4' : 'grid grid-cols-1 gap-4' }}">
                        @foreach($factorDims as $let)
                            @php
                                $sc = $kostickScores[$let] ?? 0;
                                $safeScore = max(1, min(9, $sc));
                                $interp = $kostickInterpretacion[$let][$safeScore] ?? null;
                                $pct = ($sc / 9) * 100;

                                // Color de la barra según score
                                if ($sc >= 7) {
                                    $barHex = '#6366f1'; $badgeBg = '#eef2ff'; $badgeTxt = '#3730a3';
                                    $scoreLabel = 'Alto';
                                } elseif ($sc >= 4) {
                                    $barHex = '#34d399'; $badgeBg = '#ecfdf5'; $badgeTxt = '#065f46';
                                    $scoreLabel = 'Promedio';
                                } else {
                                    $barHex = '#fbbf24'; $badgeBg = '#fef3c7'; $badgeTxt = '#78350f';
                                    $scoreLabel = 'Bajo';
                                }
                            @endphp

                            <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm avoid-break">

                                {{-- Fila superior: letra + score + label --}}
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-sm font-black text-white shrink-0" style="background-color: {{ $barHex }};">
                                        {{ $let }}
                                    </span>
                                    <div class="flex-1">
                                        @if($interp)
                                            <p class="text-[10px] font-bold uppercase tracking-wide leading-tight" style="color: {{ $fc['text'] }};">
                                                {{ $interp['detalle'] }}
                                            </p>
                                        @else
                                            <p class="text-[10px] font-bold text-gray-500 uppercase">Dimensión {{ $let }}</p>
                                        @endif
                                    </div>
                                    <span class="shrink-0 text-xs font-black px-2 py-0.5 rounded-full" style="background-color: {{ $badgeBg }}; color: {{ $badgeTxt }};">
                                        {{ $sc }}/9 · {{ $scoreLabel }}
                                    </span>
                                </div>

                                {{-- Barra de score --}}
                                <div class="w-full bg-gray-100 rounded-full h-2 mb-3">
                                    <div class="h-2 rounded-full" style="width:{{ $pct }}%; background-color:{{ $barHex }};"></div>
                                </div>

                                {{-- Glosa --}}
                                @if($interp && !empty($interp['glosa']))
                                    <p class="text-[10px] text-gray-600 leading-relaxed border-l-2 pl-2" style="border-color: {{ $barHex }};">
                                        {{ $interp['glosa'] }}
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

    @elseif($isTerman)
        {{-- ========================================================= --}}
        {{-- RENDERIZADO TERMAN-MERRIL (CI)                            --}}
        {{-- ========================================================= --}}

        {{-- ── CABECERA ─────────────────────────────────────────────── --}}
        <div class="relative overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm avoid-break mb-6">
            <div class="absolute left-0 top-0 bottom-0 w-2" style="background-color: {{ $termanCIColors['bg'] }};"></div>
            <div class="p-5 pl-7">
                <div class="flex justify-between items-start gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 tracking-tight">Test de Inteligencia Terman-Merril</h3>
                        <p class="text-sm text-gray-500 mt-1">Medición del Coeficiente Intelectual (CI) en 10 series de habilidades cognitivas.</p>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-[10px] uppercase tracking-wider font-bold text-gray-400 block mb-1">Clasificación CI</span>
                        <span class="px-4 py-1.5 rounded-full text-sm font-bold shadow-sm"
                              style="background-color: {{ $termanCIColors['light'] }}; color: {{ $termanCIColors['text'] }}; border: 1px solid {{ $termanCIColors['border'] }};">
                            {{ $termanClasCI }}
                        </span>
                    </div>
                </div>

                {{-- 4 indicadores clave --}}
                <div class="grid grid-cols-4 gap-3 mt-5">
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-3 text-center">
                        <span class="block text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-1">Puntaje Bruto</span>
                        <span class="text-3xl font-black text-gray-800">{{ $termanBruto }}</span>
                        <span class="block text-[10px] text-gray-400 mt-0.5">/ 207 máx.</span>
                    </div>
                    <div class="rounded-lg border p-3 text-center"
                         style="background-color: {{ $termanCIColors['light'] }}; border-color: {{ $termanCIColors['border'] }};">
                        <span class="block text-[10px] uppercase tracking-widest font-bold mb-1" style="color: {{ $termanCIColors['text'] }}">CI</span>
                        <span class="text-3xl font-black" style="color: {{ $termanCIColors['text'] }}">{{ $termanCI }}</span>
                        <span class="block text-[10px] mt-0.5" style="color: {{ $termanCIColors['text'] }}">Coeficiente Intelectual</span>
                    </div>
                    <div class="rounded-lg border p-3 text-center"
                         style="background-color: {{ $termanCIColors['light'] }}; border-color: {{ $termanCIColors['border'] }};">
                        <span class="block text-[10px] uppercase tracking-widest font-bold mb-1" style="color: {{ $termanCIColors['text'] }}">Nivel Intelectual</span>
                        <span class="text-base font-black leading-tight" style="color: {{ $termanCIColors['text'] }}">{{ $termanClasCI }}</span>
                    </div>
                    <div class="rounded-lg border p-3 text-center"
                         style="background-color: {{ $termanApColors['light'] }}; border-color: {{ $termanApColors['border'] }};">
                        <span class="block text-[10px] uppercase tracking-widest font-bold mb-1" style="color: {{ $termanApColors['text'] }}">Cap. Aprendizaje</span>
                        <span class="text-base font-black leading-tight" style="color: {{ $termanApColors['text'] }}">{{ $termanClasAp }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TABLA DE RESULTADOS POR SERIE ───────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden avoid-break mb-6">
            <div class="bg-gray-50 border-b border-gray-200 px-5 py-3">
                <h4 class="font-bold text-gray-800 text-sm">Resultados por Serie</h4>
                <p class="text-[10px] text-gray-400 mt-0.5">Cada serie evalúa una habilidad cognitiva específica.</p>
            </div>
            @php
                $maxPorSerie = [1=>16, 2=>22, 3=>30, 4=>18, 5=>24, 6=>20, 7=>20, 8=>17, 9=>18, 10=>22];
            @endphp
            <div class="divide-y divide-gray-100">
                @foreach($termanSeries as $num => $serie)
                    @php
                        $sc  = $getTermanColor($serie['clasificacion']);
                        $max = $maxPorSerie[$num] ?? 30;
                        $pct = $max > 0 ? min(100, round(($serie['puntaje'] / $max) * 100)) : 0;
                    @endphp
                    <div class="px-5 py-3 flex items-center gap-4 avoid-break">
                        <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-black text-white"
                             style="background-color: {{ $sc['bg'] }};">{{ $num }}</div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $serie['nombre'] }}</p>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 mt-1.5">
                                <div class="h-1.5 rounded-full" style="width: {{ $pct }}%; background-color: {{ $sc['bg'] }};"></div>
                            </div>
                        </div>
                        <div class="shrink-0 w-14 text-center">
                            <span class="text-xl font-black text-gray-800">{{ $serie['puntaje'] }}</span>
                            <span class="text-[9px] text-gray-400 block">/ {{ $max }}</span>
                        </div>
                        <div class="shrink-0 w-36 text-right">
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                                  style="background-color: {{ $sc['light'] }}; color: {{ $sc['text'] }}; border: 1px solid {{ $sc['border'] }};">
                                {{ $serie['clasificacion'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ── ESCALA DE REFERENCIA DE CI ──────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 avoid-break">
            <h4 class="font-bold text-gray-700 text-sm mb-3">Escala de Referencia — Clasificación del CI</h4>
            <div class="flex flex-wrap gap-2 text-[10px] font-bold">
                @foreach([
                    ['label'=>'Sobresaliente',      'range'=>'≥ 140', 'c'=>'#10b981','l'=>'#d1fae5','t'=>'#064e3b'],
                    ['label'=>'Superior',            'range'=>'120-139','c'=>'#34d399','l'=>'#ecfdf5','t'=>'#065f46'],
                    ['label'=>'Término Medio Alto',  'range'=>'110-119','c'=>'#3b82f6','l'=>'#dbeafe','t'=>'#1e3a8a'],
                    ['label'=>'Normal',              'range'=>'90-109', 'c'=>'#6366f1','l'=>'#e0e7ff','t'=>'#312e81'],
                    ['label'=>'Término Medio Bajo',  'range'=>'80-89', 'c'=>'#f59e0b','l'=>'#fef3c7','t'=>'#78350f'],
                    ['label'=>'Inferior',            'range'=>'70-79', 'c'=>'#f97316','l'=>'#ffedd5','t'=>'#7c2d12'],
                    ['label'=>'Deficiente',          'range'=>'< 70',  'c'=>'#ef4444','l'=>'#fee2e2','t'=>'#7f1d1d'],
                ] as $level)
                    <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full border"
                         style="background-color: {{ $level['l'] }}; border-color: {{ $level['c'] }}; color: {{ $level['t'] }};">
                        <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $level['c'] }};"></span>
                        {{ $level['label'] }} <span class="font-normal opacity-70">({{ $level['range'] }})</span>
                    </div>
                @endforeach
            </div>
        </div>

    @else
        {{-- ========================================================= --}}
        {{-- RENDERIZADO GENÉRICO (OTROS TESTS)                        --}}
        {{-- ========================================================= --}}

        <div class="relative overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm avoid-break mb-8">
            <div class="absolute left-0 top-0 bottom-0 w-2" style="background-color: {{ $globalColors['bg'] }};"></div>
            <div class="p-5 pl-7 flex justify-between items-center gap-4">
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900 tracking-tight">{{ $results['test_name'] ?? 'Evaluación' }}</h3>
                    <p class="text-sm text-gray-500 mt-1">{{ $results['summary'] ?? 'Informe de resultados.' }}</p>
                </div>
                <div class="flex flex-col items-end text-right">
                    <span class="text-[10px] uppercase tracking-wider font-bold text-gray-400">Rango Global</span>
                    <div class="flex items-center gap-2">
                        <span class="px-3 py-1 rounded-full text-sm font-bold shadow-sm" style="background-color: {{ $globalColors['light'] }}; color: {{ $globalColors['text'] }}; border: 1px solid {{ $globalColors['border'] }};">
                            {{ $globalRange ?? 'N/A' }}
                        </span>
                        @if(isset($results['percentile']) && $results['percentile'] > 0)
                            <div class="flex flex-col items-center justify-center w-10 h-10 rounded-full bg-gray-50 border border-gray-200">
                                <span class="text-[8px] text-gray-400 leading-none">Pctl</span>
                                <span class="text-xs font-bold text-gray-700 leading-none">{{ $results['percentile'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if(count($dimensions ?? []) > 0 && isset($results['chart_type']) && $results['chart_type'] != 'radar')
            <div class="grid grid-cols-1 gap-6">
                @foreach($dimensions as $dimKey => $dimData)
                    @php
                        $isRich = is_array($dimData);
                        $dimLabel = $isRich ? ($dimData['completeName'] ?? $dimKey) : $dimKey;
                        $dimVal = $isRich ? ($dimData['percentage'] ?? 0) : ($dimData['score'] ?? 0);
                        $dimRange = $isRich ? ($dimData['category'] ?? ($dimData['range'] ?? 'N/A')) : 'N/A';
                        $dimDescription = $isRich ? ($dimData['description'] ?? null) : null;
                        $dimColors = $getColorHex($dimRange);
                        $childSubscales = $relationsMap[$dimKey] ?? [];
                    @endphp

                    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-sm avoid-break">
                        <div class="p-4 border-b border-gray-100" style="background: linear-gradient(to right, #ffffff, {{ $dimColors['light'] }}33);">
                            <div class="flex justify-between items-start mb-2 gap-4">
                                <div>
                                    <h4 class="text-base font-bold text-gray-800">{{ $dimLabel }}</h4>
                                    @if($dimDescription)
                                        <p class="text-xs text-gray-500 mt-1 font-normal leading-snug">{{ $dimDescription }}</p>
                                    @endif
                                </div>
                                <span class="shrink-0 text-xs font-bold px-2 py-1 rounded border" style="color: {{ $dimColors['text'] }}; background-color: white; border-color: {{ $dimColors['border'] }};">{{ $dimRange }}</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 mt-2">
                                <div class="h-2 rounded-full" style="width: {{ $dimVal }}%; background-color: {{ $dimColors['bg'] }};"></div>
                            </div>
                        </div>

                        @if(count($childSubscales) > 0 && count($subscales ?? []) > 0)
                            <div class="px-4 py-3 bg-gray-50/50 border-b border-gray-100">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Detalle de Subescalas</p>
                                <div class="grid grid-cols-2 gap-3">
                                    @foreach($childSubscales as $subCode)
                                        @if(isset($subscales[$subCode]))
                                            @php
                                                $sub = $subscales[$subCode];
                                                $subColors = $getColorHex($sub['category']);
                                                $subPercent = min(($sub['raw_score'] ?? 0) * 11, 100);
                                            @endphp
                                            <div class="bg-white p-2.5 rounded border border-gray-200 flex items-start gap-3 shadow-sm avoid-break">
                                                <div class="flex flex-col items-center justify-center w-8 h-8 rounded-full shrink-0 font-bold text-xs mt-1" style="background-color: {{ $subColors['light'] }}; color: {{ $subColors['text'] }};">{{ $sub['raw_score'] }}</div>
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex justify-between items-baseline mb-0.5">
                                                        <span class="text-xs font-semibold text-gray-800 truncate block">{{ $sub['name'] }}</span>
                                                        <span class="text-[9px] uppercase font-bold" style="color: {{ $subColors['text'] }}">{{ $sub['category'] }}</span>
                                                    </div>
                                                    @if(isset($sub['description']))
                                                        <p class="text-[9px] text-gray-600 leading-tight mb-2">{{ $sub['description'] }}</p>
                                                    @endif
                                                    <div class="w-full bg-gray-100 h-1 rounded-full mt-auto">
                                                        <div class="h-1 rounded-full" style="width: {{ $subPercent }}%; background-color: {{ $subColors['bg'] }}"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(isset($dimData['interpretation']) || isset($dimData['recommendation']))
                            <div class="p-4 bg-white grid grid-cols-2 gap-4 text-sm avoid-break">
                                @if(isset($dimData['interpretation']))
                                    <div class="flex gap-3">
                                        <div class="shrink-0 mt-0.5 text-gray-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                                        <div>
                                            <span class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">Interpretación</span>
                                            <p class="text-gray-600 leading-snug">{{ $dimData['interpretation'] }}</p>
                                        </div>
                                    </div>
                                @endif
                                @if(isset($dimData['recommendation']))
                                    <div class="flex gap-3">
                                        <div class="shrink-0 mt-0.5 text-indigo-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg></div>
                                        <div>
                                            <span class="block text-xs font-bold text-indigo-400 uppercase tracking-wide mb-1">Recomendación</span>
                                            <div class="bg-indigo-50 border border-indigo-100 text-gray-700 p-2 rounded text-xs leading-snug">{{ $dimData['recommendation'] }}</div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>

{{-- ========================================================= --}}
{{-- PIE DE PÁGINA REQUERIDO                                   --}}
{{-- ========================================================= --}}
<div class="text-center mt-8 mb-4 text-xs font-semibold text-gray-400 uppercase tracking-widest border-t border-gray-200 pt-6 avoid-break">
    Documento generado automáticamente por el SEDYCO - {{ date('Y') }}
</div>

</body>
</html>
