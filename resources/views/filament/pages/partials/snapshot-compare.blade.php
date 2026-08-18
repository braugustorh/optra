@php
    $snapshots = $comparison['snapshots'] ?? [];
    $competencias = $comparison['competencias'] ?? [];
@endphp

<div style="font-family: system-ui, sans-serif;">
    @if(isset($comparison['error']))
        <div style="padding:12px;color:#991b1b;background:#fee2e2;border-radius:8px;">{{ $comparison['error'] }}</div>
    @else
        <table style="width:100%; border-collapse:collapse; font-size:12px;">
            <thead>
                <tr style="text-align:left; border-bottom:2px solid #e5e7eb;">
                    <th style="padding:6px 8px;">Competencia</th>
                    @foreach($snapshots as $s)
                        <th style="padding:6px 8px;">{{ $s['puesto_evaluado'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($competencias as $nombre => $valores)
                    <tr style="border-bottom:1px solid #f3f4f6;">
                        <td style="padding:6px 8px;">{{ $nombre }}</td>
                        @foreach($snapshots as $s)
                            <td style="padding:6px 8px; font-weight:700;">{{ $valores[$s['id']] ?? '-' }}</td>
                        @endforeach
                    </tr>
                @endforeach
                <tr style="border-top:2px solid #e5e7eb; font-weight:700;">
                    <td style="padding:6px 8px;">Ajuste Relativo</td>
                    @foreach($snapshots as $s)
                        <td style="padding:6px 8px;">{{ $s['ajuste_relativo'] }}%</td>
                    @endforeach
                </tr>
                <tr>
                    <td style="padding:6px 8px; color:#6b7280;">🛡️ Ajuste Global (seguridad)</td>
                    @foreach($snapshots as $s)
                        <td style="padding:6px 8px; color:#6b7280;">{{ $s['ajuste_global'] }}%</td>
                    @endforeach
                </tr>
                <tr>
                    <td style="padding:6px 8px;">Dictamen</td>
                    @foreach($snapshots as $s)
                        <td style="padding:6px 8px;">{{ $s['dictamen'] }}</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    @endif
</div>
