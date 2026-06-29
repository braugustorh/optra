@props(['reportData', 'candidateData', 'psychometricResults', 'competencias', 'cleaverIdeal' => ['D' => 50, 'I' => 50, 'S' => 50, 'C' => 50], 'meta', 'reportKey', 'ajusteGlobalPhp', 'dictamenPhp', 'competenciasIdeal'])
@php
    $reporteBase = $reportData['reporte'] ?? $reportData;

    // >>> CAMBIO CLAVE: Usamos PHP como fuente de verdad absoluta <<<
    $dictamen = $dictamenPhp;
    $porcentajeAjuste = $ajusteGlobalPhp;

    // Variables de estilo dinámico
    $badgeStyle = 'badge-success';
    $badgeIcon = '<polyline points="20 6 9 17 4 12"></polyline>';
    if (str_contains(strtolower($dictamen), 'no apto')) {
        $badgeStyle = 'bg-red-50 text-red-700 border-red-200';
        $badgeIcon = '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>';
    } elseif (str_contains(strtolower($dictamen), 'con plan')) {
        $badgeStyle = 'bg-yellow-50 text-yellow-700 border-yellow-200';
    }

    $cleaverData = collect($psychometricResults)->first(fn($r) => stripos($r['prueba'], 'Cleaver') !== false);
    $cleaverScores = $cleaverData['resultados']['scores'] ?? ['D' => 0, 'I' => 0, 'S' => 0, 'C' => 0];
@endphp

<div class="container max-w-6xl mx-auto px-6 py-10" style="padding-bottom: 60px;">
    <!-- Importación de fuente Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --bg: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --text-secondary: #475569;
            --green: #0d9488; --green-bg: #f0fdfa; --green-border: #99f6e4; --green-text: #115e59;
            --yellow: #d97706; --yellow-bg: #fefce8; --yellow-border: #fef08a; --yellow-text: #854d0e;
            --red: #e11d48; --red-bg: #fff1f2; --red-border: #fecdd3; --red-text: #9f1239;
            --blue: #4f46e5; --blue-bg: #f5f3ff; --blue-border: #ddd6fe; --blue-text: #4338ca;
            --radius-lg: 16px; --radius-md: 12px; --radius-sm: 8px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .report-font { font-family: 'Inter', sans-serif; color: var(--text); line-height: 1.6; }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); margin-bottom: 24px; overflow: hidden; }
        .card-header { padding: 20px 28px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; background-color: #fafafa; }
        .card-header h2 { font-size: 1.0625rem; font-weight: 600; display: flex; align-items: center; gap: 10px; margin:0;}
        .card-body { padding: 28px; }
        /* Anillo animado */
        .gauge-ring { position: relative; width: 160px; height: 160px; }
        .gauge-ring svg { transform: rotate(-90deg); width: 160px; height: 160px; }
        .gauge-ring circle { fill: none; stroke-width: 12; }
        .gauge-ring circle.bg { stroke: #f1f5f9; }
        .gauge-ring circle.progress { stroke: var(--blue); stroke-linecap: round; stroke-dasharray: 439.82; transition: stroke-dashoffset 1.8s cubic-bezier(0.4, 0, 0.2, 1); }
        .gauge-center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        /* Semáforo Competencias */
        .competency-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 18px; display: flex; align-items: center; justify-content: space-between; gap: 14px; height: 100%; }
        .competency-icon { font-size: 1.5rem; background: #f1f5f9; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: var(--radius-sm); }
        .status-strong { background-color: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); }
        .status-strong .competency-level { color: var(--green-text); }
        .status-strong .competency-status-indicator { background-color: var(--green); color: white; }
        .status-moderate { background-color: var(--yellow-bg); color: var(--yellow); border: 1px solid var(--yellow-border); }
        .status-moderate .competency-level { color: var(--yellow-text); }
        .status-moderate .competency-status-indicator { background-color: var(--yellow); color: white; }
        .status-weak { background-color: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); }
        .status-weak .competency-level { color: var(--red-text); }
        .status-weak .competency-status-indicator { background-color: var(--red); color: white; }
        /* Impresión */
        @media print {
            body { background: #ffffff !important; }
            .card { box-shadow: none !important; border: 1px solid #cbd5e1 !important; page-break-inside: avoid; margin-bottom: 20px !important; }
            .no-print { display: none !important; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>

    <div class="report-font">
        <!-- Header Principal -->
        <header class="bg-white border text-left border-gray-200 rounded-2xl shadow-md p-8 mb-7 relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-1" style="background: linear-gradient(90deg, var(--blue), var(--green));"></div>
            <div class="flex justify-between items-center flex-wrap gap-5">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight m-0">Reporte Psicométrico por Competencias</h1>
                    <p class="text-sm text-gray-500 font-medium mt-1">Modelo de Assessment Estratificado SEDYCO v1.1</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-bold border {{ $badgeStyle }}">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        {!! $badgeIcon !!}
                    </svg>
                    {{ strtoupper($dictamen) }}
                </span>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-5 mt-7 pt-6 border-t border-gray-100">
                <div><label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Candidato</label><span class="text-sm font-medium text-gray-900">{{ $candidateData['name'] ?? 'N/A' }}</span></div>
                <div><label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Puesto</label><span class="text-sm font-medium text-gray-900">{{ $candidateData['puesto'] ?? 'N/A' }}</span></div>
                <div><label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Fecha</label><span class="text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($meta['generado_en'] ?? now())->format('d / m / Y') }}</span></div>
                <div class="col-span-2 md:col-span-4 lg:col-span-3"><label class="block text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Pruebas Aplicadas</label><span class="text-sm font-medium text-gray-900">{{ collect($psychometricResults)->pluck('prueba')->join(' · ') }}</span></div>
            </div>
        </header>

        <!-- Resumen Ejecutivo -->
        <section class="card">
            <div class="card-header">
                <h2>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                    Resumen Ejecutivo
                </h2>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 bg-[#f5f3ff] border-l-4 border-[#4f46e5] p-6 rounded-xl text-[15px] text-[#4338ca] leading-relaxed">
                        {{ $reporteBase['resumen_ejecutivo'] ?? 'Sin resumen.' }}
                    </div>
                    <div class="flex flex-col gap-3">
                        <div class="p-3.5 rounded-xl text-sm font-medium flex items-start gap-2.5 border bg-[#f0fdfa] text-[#115e59] border-[#99f6e4]">
                            <svg class="shrink-0 mt-0.5" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <div><strong>Fortaleza:</strong> {{ $reporteBase['fortaleza_principal'] ?? 'No definida' }}</div>
                        </div>
                        <div class="p-3.5 rounded-xl text-sm font-medium flex items-start gap-2.5 border bg-[#fff1f2] text-[#9f1239] border-[#fecdd3]">
                            <svg class="shrink-0 mt-0.5" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            <div><strong>Brecha:</strong> {{ $reporteBase['brecha_principal'] ?? 'No definida' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Ajuste Global -->
            <section class="card !mb-0 h-full flex flex-col">
                <div class="card-header">
                    <h2>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="6"></circle><circle cx="12" cy="12" r="2"></circle></svg>
                        Ajuste Global al Puesto
                    </h2>
                </div>
                <div class="card-body flex-1 flex flex-col items-center justify-center gap-6">
                    <div class="gauge-ring">
                        <svg viewBox="0 0 160 160">
                            <circle class="bg" cx="80" cy="80" r="70" />
                            <circle class="progress" cx="80" cy="80" r="70" id="gaugeProgress" />
                        </svg>
                        <div class="gauge-center">
                            <span class="text-4xl font-bold text-gray-900 tracking-tighter">{{ $porcentajeAjuste }}%</span>
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-widest mt-1">Ajuste</span>
                        </div>
                    </div>
                    @php
                        // Contadores para Competencias Requeridas
                        $reqFuertes = collect($competencias)->where('requerida', true)->where('nivel', 'strong')->count();
                        $reqMod = collect($competencias)->where('requerida', true)->where('nivel', 'moderate')->count();
                        $reqDeb = collect($competencias)->where('requerida', true)->where('nivel', 'weak')->count();

                        // Contadores para Competencias Adicionales
                        $adicionales = collect($competencias)->where('requerida', false)->count();
                        $adFuertes = collect($competencias)->where('requerida', false)->where('nivel', 'strong')->count();
                        $adMod = collect($competencias)->where('requerida', false)->where('nivel', 'moderate')->count();
                        $adDeb = collect($competencias)->where('requerida', false)->where('nivel', 'weak')->count();
                    @endphp
                    <div class="w-full border-t border-gray-100 pt-4 flex flex-col gap-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-medium">Nivel de Ajuste</span>
                            <span class="font-bold text-gray-900">{{ $reporteBase['resultado_global']['nivel_ajuste'] ?? 'No definido' }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-medium">Fuertes (Req)</span>
                            <span class="font-bold text-teal-600">{{ $reqFuertes }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-medium">Moderadas (Req)</span>
                            <span class="font-bold text-amber-600">{{ $reqMod }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500 font-medium">Débiles (Req)</span>
                            <span class="font-bold text-rose-600">{{ $reqDeb }}</span>
                        </div>

                        <div class="pt-2 mt-1 border-t border-dashed border-gray-200/70 flex flex-col gap-1.5">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-400 font-medium">Competencias Complementarias</span>
                                <span class="font-bold text-gray-400">{{ $adicionales }}</span>
                            </div>

                            @if($adicionales > 0)
                                <div class="flex justify-end items-center gap-1.5">
                                    @if($adFuertes > 0)
                                        <span class="flex items-center gap-1 text-[10px] font-bold text-teal-600 bg-teal-50 border border-teal-100/50 px-1.5 py-0.5 rounded-md shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span>{{ $adFuertes }}
                                    </span>
                                    @endif
                                    @if($adMod > 0)
                                        <span class="flex items-center gap-1 text-[10px] font-bold text-amber-600 bg-amber-50 border border-amber-100/50 px-1.5 py-0.5 rounded-md shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>{{ $adMod }}
                                    </span>
                                    @endif
                                    @if($adDeb > 0)
                                        <span class="flex items-center gap-1 text-[10px] font-bold text-rose-600 bg-rose-50 border border-rose-100/50 px-1.5 py-0.5 rounded-md shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>{{ $adDeb }}
                                    </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>

            <!-- Radar Cleaver -->
            <section class="card !mb-0 h-full flex flex-col">
                <div class="card-header">
                    <h2>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2.5"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        Alineación de Competencias
                    </h2>
                </div>
                <div class="card-body flex-1 flex flex-col items-center justify-center">
                    <div class="relative w-full max-w-[300px] aspect-square mx-auto">
                        <canvas id="radarChart"></canvas>
                    </div>
                    <div class="flex justify-center gap-4 flex-wrap mt-4 text-xs font-medium text-gray-500">
                        <div class="flex items-center gap-1.5"><div class="w-4 h-1 rounded-full bg-[#4f46e5]"></div><span>Ideal SEDYCO</span></div>
                        <div class="flex items-center gap-1.5"><div class="w-4 h-1 rounded-full bg-[#0d9488]"></div><span>Candidato</span></div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Semáforo de Competencias -->
        <section class="card">
            <div class="card-header">
                <h2>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2.5"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><circle cx="12" cy="7" r="2"></circle><circle cx="12" cy="12" r="2"></circle><circle cx="12" cy="17" r="2"></circle></svg>
                    Semáforo de Competencias
                </h2>
            </div>
            <div class="card-body">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 items-stretch">
                    @foreach($competencias as $comp)
                        <div class="competency-card status-{{ $comp['nivel'] }}">
                            <div class="flex items-center gap-3">
                                <div class="competency-icon">{{ $comp['icono'] }}</div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-900 leading-tight mb-1">{{ $comp['nombre'] }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="competency-level text-xs font-semibold">{{ $comp['etiqueta'] }}</span>
                                        @if(!$comp['requerida'])
                                            <span class="bg-white/50 text-gray-400 text-[8px] font-bold px-1.5 py-0.5 rounded border border-gray-100 uppercase tracking-wider">
                                                Complementaria
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0" style="background-color: var(--{{ $comp['nivel'] === 'strong' ? 'green' : ($comp['nivel'] === 'moderate' ? 'yellow' : 'red') }}); color: white;">
                                @if($comp['nivel'] === 'strong') <svg viewBox="0 0 24 24" width="14" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                @elseif($comp['nivel'] === 'moderate') <svg viewBox="0 0 24 24" width="14" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                @else <svg viewBox="0 0 24 24" width="14" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- Plan de Desarrollo 90 días -->
        @if(!empty($reporteBase['plan_desarrollo']))
            <section class="card break-inside-avoid">
                <div class="card-header">
                    <h2>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--blue)" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        Plan de Desarrollo Recomendado — 90 Días
                    </h2>
                </div>
                <div class="card-body">
                    <div class="flex flex-col gap-3.5">
                        @foreach($reporteBase['plan_desarrollo'] as $plan)
                            @php
                                $pColor = $plan['prioridad'] === 'critical' ? 'var(--red)' : ($plan['prioridad'] === 'important' ? 'var(--yellow)' : 'var(--blue)');
                                $pBgBox = $plan['prioridad'] === 'critical' ? 'rgba(254, 242, 242, 0.4)' : ($plan['prioridad'] === 'important' ? 'rgba(255, 251, 235, 0.4)' : 'rgba(245, 243, 255, 0.4)');
                                $pBgIcon = $plan['prioridad'] === 'critical' ? '#fee2e2' : ($plan['prioridad'] === 'important' ? '#fef3c7' : '#e0e7ff');
                            @endphp
                            <div class="flex items-start gap-4 p-5 rounded-xl border border-gray-200 bg-white transition hover:border-gray-300" style="border-left: 4px solid {{ $pColor }}; background-color: {{ $pBgBox }};">
                                <div class="w-9 h-9 rounded-full flex items-center justify-center shrink-0" style="background-color: {{ $pBgIcon }}; color: {{ $pColor }};">
                                    @if($plan['prioridad'] === 'critical')
                                        <svg viewBox="0 0 24 24" width="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" width="18" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path><line x1="4" y1="22" x2="4" y2="15"></line></svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2 mb-1.5">
                                        <h4 class="text-[15px] font-bold text-gray-900">{{ $plan['titulo'] }}</h4>
                                        <span class="text-xs font-bold text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full whitespace-nowrap">{{ $plan['periodo'] }}</span>
                                    </div>
                                    <p class="text-sm text-gray-600 leading-relaxed">{{ $plan['descripcion'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <!-- Footer -->
        <footer class="grid grid-cols-2 md:grid-cols-4 gap-5 pt-6 border-t border-gray-200 mt-10 text-xs text-gray-500 mb-6">
            <div><label class="block font-bold text-gray-900 mb-1">Modelo</label>SEDYCO v1.2</div>
            <div><label class="block font-bold text-gray-900 mb-1">Próxima Revisión</label>{{ \Carbon\Carbon::parse($meta['generado_en'] ?? now())->addYear()->format('d / m / Y') }}</div>
            <div class="col-span-2 text-center text-gray-400 font-medium">Documento confidencial — Propiedad exclusiva</div>
        </footer>
    </div>

    <!-- Scripts de Vista -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Animación Gauge
            const progressCircle = document.getElementById('gaugeProgress');
            if (progressCircle) {
                const percentage = {{ $porcentajeAjuste }};
                const radius = 70;
                const circumference = 2 * Math.PI * radius;
                const offset = circumference - (percentage / 100) * circumference;
                setTimeout(() => { progressCircle.style.strokeDashoffset = offset; }, 100);
            }

            // Radar Cleaver
            // Radar Cleaver
            const ctx = document.getElementById('radarChart')?.getContext('2d');
            if(ctx) {
                const rawCompetencias = @json($competencias);
                const idealProfile = @json($competenciasIdeal ?? []);

                // Ordenar para que las requeridas salgan primero juntas, luego las adicionales
                const sorted = [...rawCompetencias].sort((a, b) => (a.requerida === b.requerida) ? 0 : a.requerida ? -1 : 1);

                const labels = sorted.map(c => c.nombre);
                const candidateData = sorted.map(c => c.puntaje);
                const idealData = labels.map(label => idealProfile[label] || 70);

                new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Ideal SEDYCO',
                                data: idealData,
                                backgroundColor: 'rgba(79, 70, 229, 0.08)',
                                borderColor: '#4f46e5',
                                borderWidth: 2,
                                borderDash: [6, 4],
                                pointBackgroundColor: '#4f46e5',
                            },
                            {
                                label: 'Candidato',
                                data: candidateData,
                                backgroundColor: 'rgba(13, 148, 136, 0.20)',
                                borderColor: '#0d9488',
                                borderWidth: 2,
                                pointBackgroundColor: '#0d9488',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            r: {
                                beginAtZero: true,
                                min: 0,
                                max: 100,
                                ticks: { display: false },
                                pointLabels: {
                                    font: { size: 9, family: 'Inter' },
                                    color: (context) => {
                                        // CORRECCIÓN AQUÍ
                                        const index = context.index;
                                        const comp = sorted[index];
                                        return (comp && comp.requerida === false) ? '#94a3b8' : '#475569';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</div>
