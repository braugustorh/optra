@php
    /** @var \App\Models\Compulsa\CalculationRecord $record */
    $desglose = $record->desglose_cuotas ?? [];
    $alertas = $record->alertas_json ?? [];
    $observaciones = $record->observaciones;
    $tipo = $desglose['tipo'] ?? null;
    $patron = $desglose['patron'] ?? [];
    $imss = $desglose['imss'] ?? [];
    $sua = $desglose['sua'] ?? ($record->desglose_sua ?? []);
    $patronDesglose = $patron['desglose'] ?? [];
    $imssDesglose = $imss['desglose'] ?? [];
    $suaDesglose = $sua['desglose'] ?? [];
    $hasSuaData = ! empty($suaDesglose) || ((float) ($record->cuota_sua ?? 0) > 0);
    $primaRiesgo = $desglose['prima_riesgo'] ?? null;
    $valorUma = $desglose['valor_uma'] ?? null;
    $sdiDeclarado = $desglose['sdi_declarado'] ?? null;
    $sdiTopadoDec = $desglose['sdi_topado_declarado'] ?? null;
    $sdiTopadoImss = $desglose['sdi_topado_imss'] ?? null;
    $diasDeclarado = $desglose['dias_declarado'] ?? null;
    $estatus = $record->estatus_conciliacion;

    // Crédito INFONAVIT
    $creditoInfonavit = $desglose['credito_infonavit'] ?? null;
    $amortImss = (float) ($record->amortizacion_imss ?? $creditoInfonavit['amortizacion_imss'] ?? ($imssDesglose['amortizacion'] ?? 0));
    $amortSua = (float) ($record->amortizacion_sua ?? $creditoInfonavit['amortizacion_sua'] ?? ($sua['amortizacion'] ?? ($suaDesglose['amortizacion'] ?? 0)));
    $numCredito = $record->numero_credito ?? $creditoInfonavit['numero_credito'] ?? ($imssDesglose['numero_credito'] ?? ($sua['numero_credito'] ?? null));
    $hasCredit = ($tipo === 'bimestral') && ($amortImss > 0 || $amortSua > 0 || ! empty($numCredito));
    $diffAmort = $creditoInfonavit['diferencia'] ?? round($amortSua - $amortImss, 2);
    $coincideCredito = $creditoInfonavit['coincide'] ?? (abs($diffAmort) <= 0.50);

    // Labels legibles por rama (EMA + EBA)
    $branchLabels = [
        'cuota_fija' => 'Cuota Fija',
        'excedente_patronal' => 'Excedente (patronal)',
        'excedente_obrero' => 'Excedente (obrero)',
        'prest_dinero_patronal' => 'Prest. en dinero (patronal)',
        'prest_dinero_obrero' => 'Prest. en dinero (obrero)',
        'gmp_patronal' => 'Gastos Méd. Pens. (patronal)',
        'gmp_obrero' => 'Gastos Méd. Pens. (obrero)',
        'riesgos_trabajo' => 'Riesgos de Trabajo',
        'iv_patronal' => 'Invalidez y Vida (patronal)',
        'iv_obrero' => 'Invalidez y Vida (obrero)',
        'guarderias_patronal' => 'Guarderías',
        'retiro' => 'Retiro',
        'cesantia_patronal' => 'Cesantía y Vejez (patronal)',
        'cesantia_obrero' => 'Cesantía y Vejez (obrero)',
        'infonavit' => 'INFONAVIT (Aportación Patronal 5%)',
    ];

    // Excluir claves de crédito o totales de las filas de ramas ordinarias
    $excludedBranchKeys = ['amortizacion', 'numero_credito', 'subtotal_rcv', 'subtotal_infonavit', 'total'];
    $allKeys = array_values(array_filter(
        array_unique(array_merge(array_keys($imssDesglose), array_keys($patronDesglose), array_keys($suaDesglose))),
        fn ($k) => ! in_array($k, $excludedBranchKeys, true)
    ));

    $imssSubtotalCuotas = (float) ($imss['cuotas_subtotal'] ?? 0);
    $patronSubtotalCuotas = (float) ($patron['cuotas_subtotal'] ?? 0);
    $suaSubtotalCuotas = (float) ($sua['cuotas_subtotal'] ?? 0);

    if ($imssSubtotalCuotas <= 0 && ! empty($allKeys)) {
        $imssSubtotalCuotas = array_sum(array_map(fn($k) => (float)($imssDesglose[$k] ?? 0), $allKeys));
    }
    if ($patronSubtotalCuotas <= 0 && ! empty($allKeys)) {
        $patronSubtotalCuotas = array_sum(array_map(fn($k) => (float)($patronDesglose[$k] ?? 0), $allKeys));
    }
    if ($suaSubtotalCuotas <= 0 && ! empty($allKeys) && ! empty($suaDesglose)) {
        $suaSubtotalCuotas = array_sum(array_map(fn($k) => (float)($suaDesglose[$k] ?? 0), $allKeys));
    }
    $diffSubtotalCuotas = round($patronSubtotalCuotas - $imssSubtotalCuotas, 2);
@endphp

<div class="space-y-5">

    {{-- Identificación --}}
    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">NSS</dt>
            <dd class="font-mono font-semibold text-gray-900 dark:text-white">{{ $record->nss }}</dd>
        </div>
        @if($record->rfc)
        <div>
            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">RFC</dt>
            <dd class="font-mono text-gray-900 dark:text-white">{{ $record->rfc }}</dd>
        </div>
        @endif
        @if($record->curp)
        <div class="col-span-2">
            <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">CURP</dt>
            <dd class="font-mono text-gray-900 dark:text-white">{{ $record->curp }}</dd>
        </div>
        @endif
    </div>

    {{-- Semáforo + Alertas --}}
    @if($estatus)
        <div class="flex flex-wrap items-center gap-2">
            <x-filament::badge :color="$estatus->getColor()" :icon="$estatus->getIcon()">
                {{ $estatus->getLabel() }}
            </x-filament::badge>
            @if($record->supera_tope_uma)
                <x-filament::badge color="danger" icon="heroicon-o-arrow-trending-up">
                    SDI supera 25 UMAs
                </x-filament::badge>
            @endif
            @foreach($alertas as $alerta)
                @continue(in_array($alerta, ['supera_tope_uma']))
                <x-filament::badge color="warning" size="xs">
                    {{ str_replace('_', ' ', $alerta) }}
                </x-filament::badge>
            @endforeach
        </div>
    @endif

    @if($observaciones)
        <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-sm text-amber-800 dark:text-amber-200">
            <div class="flex items-start gap-2">
                <x-heroicon-o-light-bulb class="w-5 h-5 flex-shrink-0 mt-0.5"/>
                <div>{{ $observaciones }}</div>
            </div>
        </div>
    @endif

    {{-- Datos por fuente --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div @class([
            'p-3 rounded-xl border transition-shadow hover:shadow-sm',
            'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700' => $record->presente_imss,
            'bg-red-50/50 dark:bg-red-900/10 border-red-200 dark:border-red-800 opacity-60' => ! $record->presente_imss,
        ])>
            <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1">
                <x-heroicon-o-building-library class="w-4 h-4"/> IMSS
                @if(! $record->presente_imss)
                    <span class="ml-auto text-red-600 dark:text-red-400 text-xs font-normal">(no presente)</span>
                @endif
            </div>
            <div class="text-sm space-y-1">
                <div><span class="text-gray-500">Días:</span> <span class="font-semibold">{{ $record->dias_imss ?? '—' }}</span></div>
                <div><span class="text-gray-500">SDI:</span> <span class="font-semibold">$ {{ number_format((float)($record->sdi_imss ?? 0), 4) }}</span></div>
                @if($sdiTopadoImss !== null)
                    <div class="text-xs text-gray-500">SDI topado: $ {{ number_format((float)$sdiTopadoImss, 4) }}</div>
                @endif
            </div>
        </div>

        <div @class([
            'p-3 rounded-xl border transition-shadow hover:shadow-sm',
            'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700' => $record->presente_sua,
            'bg-amber-50/50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-800 opacity-70' => ! $record->presente_sua,
        ])>
            <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1">
                <x-heroicon-o-cpu-chip class="w-4 h-4"/> SUA
                @if(! $record->presente_sua)
                    <span class="ml-auto text-amber-600 dark:text-amber-400 text-xs font-normal">(no presente)</span>
                @endif
            </div>
            <div class="text-sm space-y-1">
                <div><span class="text-gray-500">Días:</span> <span class="font-semibold">{{ $record->dias_sua ?? '—' }}</span></div>
                <div><span class="text-gray-500">SDI:</span> <span class="font-semibold">$ {{ number_format((float)($record->sdi_sua ?? 0), 4) }}</span></div>
            </div>
        </div>

        <div @class([
            'p-3 rounded-xl border transition-shadow hover:shadow-sm',
            'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700' => $record->presente_nomina,
            'bg-red-50/50 dark:bg-red-900/10 border-red-200 dark:border-red-800 opacity-60' => ! $record->presente_nomina,
        ])>
            <div class="text-xs uppercase font-semibold text-gray-500 dark:text-gray-400 mb-2 flex items-center gap-1">
                <x-heroicon-o-briefcase class="w-4 h-4"/> Nómina
                @if(! $record->presente_nomina)
                    <span class="ml-auto text-red-600 dark:text-red-400 text-xs font-normal">(no presente)</span>
                @endif
            </div>
            <div class="text-sm space-y-1">
                <div><span class="text-gray-500">Días:</span> <span class="font-semibold">{{ $record->dias_nomina ?? '—' }}</span></div>
                <div><span class="text-gray-500">SDI:</span> <span class="font-semibold">$ {{ number_format((float)($record->sdi_nomina ?? 0), 4) }}</span></div>
                @if($sdiTopadoDec !== null)
                    <div class="text-xs text-gray-500">SDI topado: $ {{ number_format((float)$sdiTopadoDec, 4) }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Parámetros base aplicados --}}
    <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 text-xs grid grid-cols-2 md:grid-cols-4 gap-3">
        <div>
            <div class="text-gray-500">Tipo de emisión</div>
            <div class="font-semibold">{{ $tipo === 'bimestral' ? 'EBA (Bimestral)' : 'EMA (Mensual)' }}</div>
        </div>
        @if($primaRiesgo !== null)
        <div>
            <div class="text-gray-500">Prima de riesgo</div>
            <div class="font-semibold">{{ number_format(((float)$primaRiesgo) * 100, 5) }} % <span class="text-xs text-gray-500 font-normal">({{ number_format((float)$primaRiesgo, 7) }})</span></div>
        </div>
        @endif
        @if($valorUma !== null)
        <div>
            <div class="text-gray-500">UMA</div>
            <div class="font-semibold">$ {{ number_format((float)$valorUma, 4) }}</div>
        </div>
        @endif
        @if($diasDeclarado !== null)
        <div>
            <div class="text-gray-500">Días cotizados</div>
            <div class="font-semibold">{{ $diasDeclarado }} días</div>
        </div>
        @endif
    </div>

    {{-- BLOQUE 1: Cuotas Obrero-Patronales (Seguridad Social) --}}
    @if(!empty($allKeys))
        <x-filament::section icon="heroicon-o-table-cells">
            <x-slot name="heading">
                {{ $tipo === 'bimestral' ? 'Bloque 1: Cuotas Obrero-Patronales (Seguridad Social)' : 'Desglose por rama' }}
            </x-slot>

            <div class="overflow-x-auto -mx-2">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 dark:border-gray-700">
                        <tr class="text-gray-500 dark:text-gray-400 text-xs">
                            <th class="text-left px-2 py-2 font-medium">Concepto</th>
                            <th class="text-right px-2 py-2 font-medium">IMSS (Facturado)</th>
                            <th class="text-right px-2 py-2 font-medium">SUA (Archivo)</th>
                            <th class="text-right px-2 py-2 font-medium">Cálculo Legal (Optra)</th>
                            <th class="text-right px-2 py-2 font-medium">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allKeys as $key)
                            @php
                                $imssVal = (float) ($imssDesglose[$key] ?? 0);
                                $hasSua = isset($suaDesglose[$key]);
                                $suaVal = $hasSua ? (float) $suaDesglose[$key] : null;
                                $patronVal = (float) ($patronDesglose[$key] ?? 0);
                                $diff = round($patronVal - $imssVal, 2);
                                $label = $branchLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
                                $hasDiff = abs($diff) > 0.01;
                            @endphp
                            <tr class="border-b border-gray-100 dark:border-gray-800 last:border-0 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-2 py-2 text-gray-800 dark:text-gray-200">{{ $label }}</td>
                                <td class="px-2 py-2 text-right font-mono text-gray-700 dark:text-gray-300">$ {{ number_format($imssVal, 2) }}</td>
                                <td class="px-2 py-2 text-right font-mono text-gray-700 dark:text-gray-300">
                                    @if($hasSua)
                                        $ {{ number_format((float)$suaVal, 2) }}
                                    @else
                                        <span class="text-gray-400 italic text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-right font-mono font-medium text-gray-800 dark:text-gray-200">$ {{ number_format($patronVal, 2) }}</td>
                                <td @class([
                                    'px-2 py-2 text-right font-mono',
                                    'text-amber-600 dark:text-amber-400 font-semibold' => $hasDiff && $diff > 0,
                                    'text-red-600 dark:text-red-400 font-semibold' => $hasDiff && $diff < 0,
                                    'text-gray-400' => ! $hasDiff,
                                ])>
                                    {{ $diff >= 0 ? '+' : '−' }}$ {{ number_format(abs($diff), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-gray-300 dark:border-gray-600">
                        @if($hasCredit)
                            {{-- Si hay crédito, el pie de esta tabla es el Subtotal de Cuotas --}}
                            <tr class="font-bold text-gray-900 dark:text-white bg-gray-50/50 dark:bg-gray-800/30">
                                <td class="px-2 py-2.5">Subtotal Cuotas Seguridad Social</td>
                                <td class="px-2 py-2.5 text-right font-mono">$ {{ number_format($imssSubtotalCuotas, 2) }}</td>
                                <td class="px-2 py-2.5 text-right font-mono">
                                    @if($hasSuaData)
                                        $ {{ number_format($suaSubtotalCuotas, 2) }}
                                    @else
                                        <span class="text-gray-400 italic font-normal text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2.5 text-right font-mono">$ {{ number_format($patronSubtotalCuotas, 2) }}</td>
                                <td @class([
                                    'px-2 py-2.5 text-right font-mono',
                                    'text-green-600 dark:text-green-400' => abs($diffSubtotalCuotas) < 0.01,
                                    'text-amber-600 dark:text-amber-400' => $diffSubtotalCuotas > 0.01,
                                    'text-red-600 dark:text-red-400' => $diffSubtotalCuotas < -0.01,
                                ])>
                                    {{ $diffSubtotalCuotas >= 0 ? '+' : '−' }}$ {{ number_format(abs($diffSubtotalCuotas), 2) }}
                                </td>
                            </tr>
                        @else
                            {{-- Si no hay crédito, es el Total directo --}}
                            <tr class="font-bold text-gray-900 dark:text-white">
                                <td class="px-2 py-2">Total</td>
                                <td class="px-2 py-2 text-right font-mono">$ {{ number_format((float)($imss['total'] ?? 0), 2) }}</td>
                                <td class="px-2 py-2 text-right font-mono">
                                    @if($hasSuaData)
                                        $ {{ number_format((float)($sua['total'] ?? $suaSubtotalCuotas), 2) }}
                                    @else
                                        <span class="text-gray-400 italic font-normal text-xs">—</span>
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-right font-mono">$ {{ number_format((float)($patron['total'] ?? 0), 2) }}</td>
                                <td @class([
                                    'px-2 py-2 text-right font-mono',
                                    'text-green-600 dark:text-green-400' => abs((float)$record->diferencia_total) < 0.01,
                                    'text-amber-600 dark:text-amber-400' => (float)$record->diferencia_total > 0.01,
                                    'text-red-600 dark:text-red-400' => (float)$record->diferencia_total < -0.01,
                                ])>
                                    {{ (float)$record->diferencia_total >= 0 ? '+' : '−' }}$ {{ number_format(abs((float)$record->diferencia_total), 2) }}
                                </td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>

            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                    <div class="text-xs uppercase font-semibold text-blue-700 dark:text-blue-300 mb-1">Cuota patronal</div>
                    <div class="text-gray-700 dark:text-gray-300 text-xs space-y-0.5">
                        <div>IMSS: <span class="font-mono font-medium">$ {{ number_format((float)($imss['patronal'] ?? 0), 2) }}</span></div>
                        @if($hasSuaData && isset($sua['patronal']))
                            <div>SUA: <span class="font-mono font-medium">$ {{ number_format((float)$sua['patronal'], 2) }}</span></div>
                        @endif
                        <div>Cálculo Legal: <span class="font-mono font-medium">$ {{ number_format((float)($patron['patronal'] ?? 0), 2) }}</span></div>
                    </div>
                </div>
                <div class="p-3 rounded-xl bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800">
                    <div class="text-xs uppercase font-semibold text-purple-700 dark:text-purple-300 mb-1">Cuota obrera</div>
                    <div class="text-gray-700 dark:text-gray-300 text-xs space-y-0.5">
                        <div>IMSS: <span class="font-mono font-medium">$ {{ number_format((float)($imss['obrera'] ?? 0), 2) }}</span></div>
                        @if($hasSuaData && isset($sua['obrera']))
                            <div>SUA: <span class="font-mono font-medium">$ {{ number_format((float)$sua['obrera'], 2) }}</span></div>
                        @endif
                        <div>Cálculo Legal: <span class="font-mono font-medium">$ {{ number_format((float)($patron['obrera'] ?? 0), 2) }}</span></div>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @else
        <div class="p-6 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 text-center text-gray-500 dark:text-gray-400">
            <x-heroicon-o-clock class="w-8 h-8 mx-auto mb-2 opacity-60"/>
            <p class="text-sm">Sin desglose registrado. Reprocesa la compulsa para generar los cálculos.</p>
        </div>
    @endif

    {{-- BLOQUE 2: Crédito de Vivienda INFONAVIT (Solo colaboradores con crédito) --}}
    @if($hasCredit)
        <x-filament::section icon="heroicon-o-home-modern">
            <x-slot name="heading">
                Bloque 2: Crédito de Vivienda INFONAVIT (Amortizaciones)
            </x-slot>
            <x-slot name="headerEnd">
                @if($coincideCredito)
                    <x-filament::badge color="success" icon="heroicon-o-check-circle">
                        Amortización Conciliada
                    </x-filament::badge>
                @elseif($amortImss > 0 && $amortSua <= 0.01)
                    <x-filament::badge color="warning" icon="heroicon-o-exclamation-triangle">
                        Crédito no aplicado en SUA
                    </x-filament::badge>
                @elseif($amortSua > 0 && $amortImss <= 0.01)
                    <x-filament::badge color="warning" icon="heroicon-o-exclamation-triangle">
                        Amortización SUA sin crédito en EBA
                    </x-filament::badge>
                @else
                    <x-filament::badge color="warning" icon="heroicon-o-exclamation-triangle">
                        Diferencia en Amortización
                    </x-filament::badge>
                @endif
            </x-slot>

            <div class="mb-3 flex items-center gap-2 text-xs">
                <span class="text-gray-500 uppercase tracking-wide">Número de Crédito:</span>
                <span class="font-mono font-bold text-gray-900 dark:text-white px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                    {{ $numCredito ?? 'Sin número reportado' }}
                </span>
            </div>

            <div class="overflow-x-auto -mx-2">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 dark:border-gray-700">
                        <tr class="text-gray-500 dark:text-gray-400">
                            <th class="text-left px-2 py-2 font-medium">Concepto</th>
                            <th class="text-right px-2 py-2 font-medium">EBA Facturado (IMSS)</th>
                            <th class="text-right px-2 py-2 font-medium">SUA Determinado (Patrón)</th>
                            <th class="text-right px-2 py-2 font-medium">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="px-2 py-2.5 text-gray-800 dark:text-gray-200">
                                Amortización de Crédito (Retención Bimestral)
                            </td>
                            <td class="px-2 py-2.5 text-right font-mono text-gray-700 dark:text-gray-300">
                                $ {{ number_format($amortImss, 2) }}
                            </td>
                            <td class="px-2 py-2.5 text-right font-mono text-gray-700 dark:text-gray-300">
                                $ {{ number_format($amortSua, 2) }}
                            </td>
                            <td @class([
                                'px-2 py-2.5 text-right font-mono',
                                'text-green-600 dark:text-green-400' => $coincideCredito,
                                'text-amber-600 dark:text-amber-400 font-semibold' => ! $coincideCredito && $diffAmort > 0,
                                'text-red-600 dark:text-red-400 font-semibold' => ! $coincideCredito && $diffAmort < 0,
                            ])>
                                {{ $diffAmort >= 0 ? '+' : '−' }}$ {{ number_format(abs($diffAmort), 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- BLOQUE 3: Resumen Total General --}}
        <x-filament::section icon="heroicon-o-calculator">
            <x-slot name="heading">Resumen Total General (Cuotas + Amortizaciones)</x-slot>

            <div class="overflow-x-auto -mx-2">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-200 dark:border-gray-700">
                        <tr class="text-gray-500 dark:text-gray-400 text-xs">
                            <th class="text-left px-2 py-2 font-medium">Concepto</th>
                            <th class="text-right px-2 py-2 font-medium">IMSS (EBA)</th>
                            <th class="text-right px-2 py-2 font-medium">SUA (Cédula)</th>
                            <th class="text-right px-2 py-2 font-medium">Cálculo Legal (Optra)</th>
                            <th class="text-right px-2 py-2 font-medium">Diferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-2 py-2 text-gray-700 dark:text-gray-300">Cuotas Obrero-Patronales (Seguridad Social)</td>
                            <td class="px-2 py-2 text-right font-mono">$ {{ number_format($imssSubtotalCuotas, 2) }}</td>
                            <td class="px-2 py-2 text-right font-mono">
                                @if($hasSuaData)
                                    $ {{ number_format($suaSubtotalCuotas, 2) }}
                                @else
                                    <span class="text-gray-400 italic text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-right font-mono">$ {{ number_format($patronSubtotalCuotas, 2) }}</td>
                            <td class="px-2 py-2 text-right font-mono text-gray-400">
                                {{ $diffSubtotalCuotas >= 0 ? '+' : '−' }}$ {{ number_format(abs($diffSubtotalCuotas), 2) }}
                            </td>
                        </tr>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-2 py-2 text-gray-700 dark:text-gray-300">Amortización Créditos INFONAVIT</td>
                            <td class="px-2 py-2 text-right font-mono">$ {{ number_format($amortImss, 2) }}</td>
                            <td class="px-2 py-2 text-right font-mono">$ {{ number_format($amortSua, 2) }}</td>
                            <td class="px-2 py-2 text-right font-mono">$ {{ number_format($amortSua, 2) }}</td>
                            <td class="px-2 py-2 text-right font-mono text-gray-400">
                                {{ $diffAmort >= 0 ? '+' : '−' }}$ {{ number_format(abs($diffAmort), 2) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t-2 border-gray-300 dark:border-gray-600">
                        <tr class="font-bold text-gray-900 dark:text-white bg-gray-50/50 dark:bg-gray-800/30">
                            <td class="px-2 py-2.5">TOTAL GENERAL A PAGAR</td>
                            <td class="px-2 py-2.5 text-right font-mono">$ {{ number_format((float)($record->cuota_imss ?? 0), 2) }}</td>
                            <td class="px-2 py-2.5 text-right font-mono">
                                @if($hasSuaData)
                                    $ {{ number_format((float)($record->cuota_sua ?? ($suaSubtotalCuotas + $amortSua)), 2) }}
                                @else
                                    <span class="text-gray-400 italic font-normal text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-2 py-2.5 text-right font-mono">$ {{ number_format((float)($record->cuota_patron ?? 0), 2) }}</td>
                            <td @class([
                                'px-2 py-2.5 text-right font-mono',
                                'text-green-600 dark:text-green-400' => abs((float)$record->diferencia_total) < 0.01,
                                'text-amber-600 dark:text-amber-400' => (float)$record->diferencia_total > 0.01,
                                'text-red-600 dark:text-red-400' => (float)$record->diferencia_total < -0.01,
                            ])>
                                {{ (float)$record->diferencia_total >= 0 ? '+' : '−' }}$ {{ number_format(abs((float)$record->diferencia_total), 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </x-filament::section>
    @endif

</div>

