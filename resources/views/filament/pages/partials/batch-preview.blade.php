@php
    $puestoColors = [
        'Directivo'      => ['bg' => '#fee2e2', 'text' => '#991b1b', 'border' => '#fca5a5'],
        'Mando Medio'    => ['bg' => '#fef3c7', 'text' => '#92400e', 'border' => '#fcd34d'],
        'Supervisor'     => ['bg' => '#dbeafe', 'text' => '#1e40af', 'border' => '#93c5fd'],
        'Administrativo' => ['bg' => '#dcfce7', 'text' => '#166534', 'border' => '#86efac'],
    ];
    $pc = $puestoColors[$batchData['puesto']] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'border' => '#d1d5db'];

    $totalSec = $batchData['total_elapsed'];
    $h = intdiv($totalSec, 3600);
    $m = intdiv($totalSec % 3600, 60);
    $s = $totalSec % 60;
    $totalFormatted = $h > 0
        ? sprintf('%dh %02dm %02ds', $h, $m, $s)
        : sprintf('%dm %02ds', $m, $s);

    $completedCount = collect($batchData['evaluations'])->where('status', 'completed')->count();
    $totalCount     = count($batchData['evaluations']);
    $progressPct    = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
@endphp

<div style="font-family: system-ui, sans-serif;">

    {{-- ── HEADER PERSONA ── --}}
    <div style="display:flex; align-items:center; gap:12px; padding:14px 16px;
                background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
                border-radius:12px; border:1px solid #e0e7ff; margin-bottom:14px;">

        {{-- Avatar inicial --}}
        <div style="width:44px; height:44px; border-radius:50%; background:#4f46e5;
                    display:flex; align-items:center; justify-content:center;
                    color:white; font-weight:700; font-size:18px; flex-shrink:0;">
            {{ strtoupper(mb_substr($batchData['evaluable_name'], 0, 1)) }}
        </div>

        {{-- Nombre + puesto --}}
        <div style="flex:1; min-width:0;">
            <div style="font-weight:700; color:#1e1b4b; font-size:15px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                {{ $batchData['evaluable_name'] }}
            </div>
            <div style="display:flex; align-items:center; gap:8px; margin-top:4px; flex-wrap:wrap;">
                <span style="display:inline-flex; align-items:center; padding:2px 10px; border-radius:9999px;
                             font-size:11px; font-weight:600;
                             background:{{ $pc['bg'] }}; color:{{ $pc['text'] }}; border:1px solid {{ $pc['border'] }};">
                    {{ $batchData['puesto'] ?? 'Sin puesto' }}
                </span>
                @if($batchData['assigned_at'])
                <span style="font-size:11px; color:#6b7280;">
                    📅 Asignado: {{ \Carbon\Carbon::parse($batchData['assigned_at'])->format('d/m/Y') }}
                </span>
                @endif
            </div>
        </div>

        {{-- Tiempo total --}}
        <div style="text-align:right; flex-shrink:0;">
            <div style="font-size:10px; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em;">Tiempo total</div>
            <div style="font-family:monospace; font-weight:700; color:#4f46e5; font-size:14px;">⏱ {{ $totalFormatted }}</div>
        </div>
    </div>

    {{-- ── BARRA DE PROGRESO GLOBAL ── --}}
    <div style="margin-bottom:14px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:5px;">
            <span style="font-size:11px; font-weight:600; color:#374151; text-transform:uppercase; letter-spacing:0.05em;">
                Progreso del batch
            </span>
            <span style="font-size:11px; font-weight:700; color:#4f46e5;">
                {{ $completedCount }}/{{ $totalCount }} completadas ({{ $progressPct }}%)
            </span>
        </div>
        <div style="width:100%; background:#e5e7eb; border-radius:9999px; height:8px; overflow:hidden;">
            <div style="width:{{ $progressPct }}%; height:100%;
                        background: linear-gradient(90deg, #4f46e5, #7c3aed);
                        border-radius:9999px; transition:width 0.5s ease;">
            </div>
        </div>
    </div>

    {{-- ── LISTA DE EVALUACIONES ── --}}
    <div style="margin-bottom:14px;">
        <div style="font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">
            Detalle de evaluaciones
        </div>

        @foreach($batchData['evaluations'] as $eval)
        @php
            $isCompleted = $eval['status'] === 'completed';
            $isStarted   = $eval['status'] === 'started';
            $sec = $eval['elapsed_seconds'];
            $eh  = intdiv($sec, 3600);
            $em  = intdiv($sec % 3600, 60);
            $es  = $sec % 60;
            $timeStr = $eh > 0
                ? sprintf('%dh %02dm %02ds', $eh, $em, $es)
                : sprintf('%dm %02ds', $em, $es);

            if ($isCompleted) {
                $rowBg     = '#f0fdf4';
                $rowBorder = '#bbf7d0';
                $iconBg    = '#16a34a';
                $iconSvg   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>';
            } elseif ($isStarted) {
                $rowBg     = '#eff6ff';
                $rowBorder = '#bfdbfe';
                $iconBg    = '#3b82f6';
                $iconSvg   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';
            } else {
                $rowBg     = '#f9fafb';
                $rowBorder = '#e5e7eb';
                $iconBg    = '#9ca3af';
                $iconSvg   = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
            }
        @endphp

        <div style="display:flex; align-items:center; gap:10px; padding:10px 12px;
                    border-radius:10px; border:1px solid {{ $rowBorder }};
                    background:{{ $rowBg }}; margin-bottom:6px;">

            {{-- Ícono de estado --}}
            <div style="width:28px; height:28px; border-radius:50%; background:{{ $iconBg }};
                        display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <svg width="14" height="14" fill="none" stroke="white" viewBox="0 0 24 24">
                    {!! $iconSvg !!}
                </svg>
            </div>

            {{-- Nombre + badges --}}
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                    <span style="font-weight:600; font-size:13px; color:#111827;">
                        {{ $eval['test_name'] }}
                    </span>
                    @if($eval['is_required'])
                    <span style="display:inline-flex; align-items:center; padding:1px 7px; border-radius:9999px;
                                 font-size:10px; font-weight:600; background:#e0e7ff; color:#3730a3;">
                        ★ Requerida
                    </span>
                    @else
                    <span style="display:inline-flex; align-items:center; padding:1px 7px; border-radius:9999px;
                                 font-size:10px; font-weight:500; background:#f3f4f6; color:#6b7280;">
                        Opcional
                    </span>
                    @endif
                </div>
                @if($isCompleted && $eval['completed_at'])
                <div style="font-size:10px; color:#6b7280; margin-top:2px;">
                    Completada: {{ \Carbon\Carbon::parse($eval['completed_at'])->format('d/m/Y H:i') }}
                </div>
                @endif
            </div>

            {{-- Tiempo / estado textual --}}
            <div style="text-align:right; flex-shrink:0;">
                @if($isCompleted && $eval['elapsed_seconds'] > 0)
                <span style="font-family:monospace; font-size:12px; font-weight:700; color:#374151;">
                    {{ $timeStr }}
                </span>
                @elseif($isStarted)
                <span style="font-size:11px; color:#3b82f6; font-style:italic;">En progreso…</span>
                @else
                <span style="font-size:11px; color:#9ca3af; font-style:italic;">Pendiente</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── ESTADO FINAL: ¿LISTO PARA GENERAR? ── --}}
    @if($batchData['can_generate'])
    <div style="display:flex; align-items:center; gap:10px; padding:12px 14px;
                background:#f0fdf4; border:1px solid #86efac; border-radius:10px;">
        <svg width="20" height="20" fill="none" stroke="#16a34a" viewBox="0 0 24 24" style="flex-shrink:0;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <div style="font-weight:700; color:#166534; font-size:13px;">✅ Listo para generar reporte</div>
            <div style="font-size:11px; color:#15803d; margin-top:1px;">
                Todas las pruebas requeridas para el puesto <strong>{{ $batchData['puesto'] }}</strong> están completadas.
            </div>
        </div>
    </div>
    @else
    <div style="display:flex; align-items:flex-start; gap:10px; padding:12px 14px;
                background:#fffbeb; border:1px solid #fcd34d; border-radius:10px;">
        <svg width="20" height="20" fill="none" stroke="#d97706" viewBox="0 0 24 24" style="flex-shrink:0; margin-top:1px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div>
            <div style="font-weight:700; color:#92400e; font-size:13px;">⚠ Evaluaciones pendientes</div>
            <div style="font-size:11px; color:#b45309; margin-top:2px;">
                Faltan las siguientes pruebas requeridas para <strong>{{ $batchData['puesto'] }}</strong>:
                <strong>{{ implode(', ', $batchData['missing_required']) }}</strong>
            </div>
        </div>
    </div>
    @endif

</div>

