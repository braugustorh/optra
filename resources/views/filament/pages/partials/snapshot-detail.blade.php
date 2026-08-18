@php
    $dictamenColors = [
        'ALINEACIÓN ÓPTIMA' => ['bg' => '#dcfce7', 'text' => '#166534'],
        'POTENCIAL CON PLAN DE DESARROLLO' => ['bg' => '#fef3c7', 'text' => '#92400e'],
        'POTENCIAL LATENTE' => ['bg' => '#fed7aa', 'text' => '#9a3412'],
        'PERFIL NO ALINEADO AL PUESTO' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
    ];
    $dc = $dictamenColors[$snapshot->dictamen] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
@endphp

<div style="font-family: system-ui, sans-serif;">
    <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:14px;">
        <div style="flex:1; min-width:140px; padding:10px 12px; background:#eef2ff; border-radius:10px;">
            <div style="font-size:10px; color:#4338ca; text-transform:uppercase; font-weight:700;">Puesto evaluado</div>
            <div style="font-size:14px; font-weight:700; color:#1e1b4b;">{{ $snapshot->puesto_evaluado }}</div>
            @if($snapshot->isOverride())
                <div style="font-size:10px; color:#7c3aed; margin-top:2px;">↺ Reevaluación (original: {{ $snapshot->puesto_original }})</div>
            @endif
        </div>
        <div style="flex:1; min-width:120px; padding:10px 12px; background:#eff6ff; border-radius:10px;">
            <div style="font-size:10px; color:#1e40af; text-transform:uppercase; font-weight:700;">Ajuste Relativo</div>
            <div style="font-size:14px; font-weight:700; color:#1e40af;">{{ $snapshot->ajuste_relativo }}%</div>
        </div>
        <div style="flex:1; min-width:120px; padding:10px 12px; background:#f0fdf4; border-radius:10px;">
            <div style="font-size:10px; color:#166534; text-transform:uppercase; font-weight:700;">🛡️ Ajuste Global (seguridad)</div>
            <div style="font-size:14px; font-weight:700; color:#166534;">{{ $snapshot->ajuste_global }}%</div>
        </div>
        <div style="flex:1; min-width:160px; padding:10px 12px; background:{{ $dc['bg'] }}; border-radius:10px;">
            <div style="font-size:10px; color:{{ $dc['text'] }}; text-transform:uppercase; font-weight:700;">Dictamen</div>
            <div style="font-size:13px; font-weight:700; color:{{ $dc['text'] }};">{{ $snapshot->dictamen }}</div>
        </div>
    </div>

    <div style="font-size:11px; font-weight:600; color:#6b7280; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:8px;">
        Competencias
    </div>

    <table style="width:100%; border-collapse:collapse; font-size:12px;">
        <thead>
            <tr style="text-align:left; border-bottom:2px solid #e5e7eb;">
                <th style="padding:6px 8px;">Competencia</th>
                <th style="padding:6px 8px;">Puntaje</th>
                <th style="padding:6px 8px;">Nivel</th>
                <th style="padding:6px 8px;">Requerida</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($snapshot->competencias_json ?? []) as $comp)
                <tr style="border-bottom:1px solid #f3f4f6;">
                    <td style="padding:6px 8px;">{{ $comp['icono'] ?? '' }} {{ $comp['nombre'] ?? '' }}</td>
                    <td style="padding:6px 8px; font-weight:700;">{{ $comp['puntaje'] ?? '-' }}</td>
                    <td style="padding:6px 8px;">{{ $comp['etiqueta'] ?? '-' }}</td>
                    <td style="padding:6px 8px;">{{ !empty($comp['requerida']) ? 'Sí' : 'No' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:10px; font-size:11px; color:#9ca3af;">
        Generado: {{ \Carbon\Carbon::parse($snapshot->created_at)->format('d/m/Y H:i') }}
        @if($snapshot->generatedBy)
            por {{ $snapshot->generatedBy->name }}
        @endif
    </div>
</div>
