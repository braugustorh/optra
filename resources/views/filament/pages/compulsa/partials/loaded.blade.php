@php
    /** @var \App\Models\Compulsa\Calculation $calculation */
    $estado = $calculation->estado;
    $tipo = $calculation->tipo_periodo;
    $totalEmp = (int) $calculation->total_empleados;
    $conDif = (int) $calculation->empleados_con_diferencias;
    $porcentaje = $totalEmp > 0 ? round(($conDif / $totalEmp) * 100, 1) : 0.0;
    $porcentajeColor = match (true) {
        $porcentaje > 20 => 'danger',
        $porcentaje > 5  => 'warning',
        default          => 'success',
    };
@endphp

<div class="space-y-6">

    {{-- Header con metadatos de la compulsa activa --}}
    <div class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm">
        <div class="absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-primary-500 via-primary-400 to-indigo-400"></div>

        <div class="p-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="flex items-start gap-4 min-w-0">
                    <span class="flex-shrink-0 w-12 h-12 rounded-xl bg-primary-500/10 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                        <x-heroicon-o-building-office-2 class="w-6 h-6" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                            {{ $calculation->periodLabel() }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                            {{ $calculation->razonSocial?->name ?? 'Sin razón social' }} ·
                            RP {{ $calculation->razonSocial?->registro_patronal ?? '—' }}
                        </p>
                        <div class="mt-2 flex items-center gap-2 flex-wrap">
                            <x-filament::badge :color="$estado?->getColor() ?? 'gray'" :icon="$estado?->getIcon()">
                                {{ $estado?->getLabel() ?? '—' }}
                            </x-filament::badge>
                            <x-filament::badge :color="$tipo?->getColor() ?? 'gray'">
                                {{ $tipo?->getLabel() ?? '—' }}
                            </x-filament::badge>
                        </div>
                    </div>
                </div>

                <x-filament::button color="gray" size="sm" wire:click="clearActive" icon="heroicon-o-arrow-uturn-left" class="flex-shrink-0">
                    Volver al inicio
                </x-filament::button>
            </div>

            <dl class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Prima de riesgo</dt>
                    <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">
                        {{ number_format(((float) ($calculation->prima_riesgo_aplicada ?? 0)) * 100, 5) }} %
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">({{ number_format((float) ($calculation->prima_riesgo_aplicada ?? 0), 7) }})</span>
                    </dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">UMA aplicada</dt>
                    <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">$ {{ number_format((float) ($calculation->valor_uma_aplicado ?? 0), 4) }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Salario mínimo</dt>
                    <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">$ {{ number_format((float) ($calculation->salario_minimo_aplicado ?? 0), 4) }}</dd>
                </div>
                <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-700/60">
                    <dt class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Procesado</dt>
                    <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">{{ optional($calculation->procesado_en ?? $calculation->updated_at)->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Header Widgets: Total a Pagar · Total Diferencias · Con Inconsistencias --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="relative overflow-hidden p-5 rounded-xl bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/30 dark:to-primary-900/10 border border-primary-200 dark:border-primary-800 shadow-sm hover:shadow-md transition-shadow duration-200">
            <x-heroicon-o-banknotes class="absolute -right-3 -bottom-3 w-20 h-20 text-primary-500/10 dark:text-primary-400/10" />
            <div class="relative flex items-center gap-2 text-sm text-primary-700 dark:text-primary-300 font-medium">
                <x-heroicon-o-banknotes class="w-4 h-4"/>
                Total a pagar (patrón)
            </div>
            <div class="relative mt-2 text-3xl font-bold text-primary-900 dark:text-primary-100">
                $ {{ number_format((float) $calculation->total_cuota_patron, 2) }}
            </div>
            <div class="relative text-xs text-primary-700/70 dark:text-primary-300/70 mt-1">
                IMSS reporta: $ {{ number_format((float) $calculation->total_cuota_imss, 2) }}
            </div>
        </div>

        <div class="relative overflow-hidden p-5 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-900/10 border border-amber-200 dark:border-amber-800 shadow-sm hover:shadow-md transition-shadow duration-200">
            <x-heroicon-o-arrows-right-left class="absolute -right-3 -bottom-3 w-20 h-20 text-amber-500/10 dark:text-amber-400/10" />
            <div class="relative flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300 font-medium">
                <x-heroicon-o-arrows-right-left class="w-4 h-4"/>
                Total de diferencias
            </div>
            <div class="relative mt-2 text-3xl font-bold text-amber-900 dark:text-amber-100">
                $ {{ number_format((float) $calculation->total_diferencia, 2) }}
            </div>
            <div class="relative text-xs text-amber-700/70 dark:text-amber-300/70 mt-1">
                Suma absoluta patrón vs IMSS
            </div>
        </div>

        <div @class([
            'relative overflow-hidden p-5 rounded-xl border shadow-sm hover:shadow-md transition-shadow duration-200',
            'bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/30 dark:to-red-900/10 border-red-200 dark:border-red-800' => $porcentajeColor === 'danger',
            'bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/30 dark:to-amber-900/10 border-amber-200 dark:border-amber-800' => $porcentajeColor === 'warning',
            'bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/30 dark:to-emerald-900/10 border-emerald-200 dark:border-emerald-800' => $porcentajeColor === 'success',
        ])>
            <x-heroicon-o-exclamation-triangle @class([
                'absolute -right-3 -bottom-3 w-20 h-20',
                'text-red-500/10 dark:text-red-400/10' => $porcentajeColor === 'danger',
                'text-amber-500/10 dark:text-amber-400/10' => $porcentajeColor === 'warning',
                'text-emerald-500/10 dark:text-emerald-400/10' => $porcentajeColor === 'success',
            ]) />
            <div @class([
                'relative flex items-center gap-2 text-sm font-medium',
                'text-red-700 dark:text-red-300' => $porcentajeColor === 'danger',
                'text-amber-700 dark:text-amber-300' => $porcentajeColor === 'warning',
                'text-emerald-700 dark:text-emerald-300' => $porcentajeColor === 'success',
            ])>
                <x-heroicon-o-exclamation-triangle class="w-4 h-4"/>
                Con inconsistencias
            </div>
            <div @class([
                'relative mt-2 text-3xl font-bold',
                'text-red-900 dark:text-red-100' => $porcentajeColor === 'danger',
                'text-amber-900 dark:text-amber-100' => $porcentajeColor === 'warning',
                'text-emerald-900 dark:text-emerald-100' => $porcentajeColor === 'success',
            ])>
                {{ number_format($conDif) }}
                <span class="text-lg opacity-70">/ {{ number_format($totalEmp) }}</span>
            </div>
            <div @class([
                'relative text-xs mt-1',
                'text-red-700/70 dark:text-red-300/70' => $porcentajeColor === 'danger',
                'text-amber-700/70 dark:text-amber-300/70' => $porcentajeColor === 'warning',
                'text-emerald-700/70 dark:text-emerald-300/70' => $porcentajeColor === 'success',
            ])>
                {{ number_format($porcentaje, 1) }} % del padrón
            </div>
        </div>
    </div>

    {{-- Banner de topes UMA (Fase 6) --}}
    @php $topeCount = $this->topeUmaCount; @endphp
    @if($topeCount > 0)
        <div class="p-4 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 flex items-center gap-4 shadow-sm">
            <div class="flex-shrink-0 w-11 h-11 rounded-full bg-red-100 dark:bg-red-900/40 flex items-center justify-center animate-pulse">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600 dark:text-red-400"/>
            </div>
            <div class="flex-1">
                <p class="font-semibold text-red-800 dark:text-red-200">
                    {{ $topeCount }} {{ $topeCount === 1 ? 'empleado supera' : 'empleados superan' }} el tope de 25 UMAs
                </p>
                <p class="text-sm text-red-700 dark:text-red-300">
                    El SDI declarado excede el máximo cotizable. Verifica que sea correcto o corrígelo antes de pagar.
                </p>
            </div>
            <x-filament::button
                color="danger"
                size="sm"
                icon="heroicon-o-funnel"
                wire:click="filterByTopeUma"
            >
                Ver afectados
            </x-filament::button>
        </div>
    @endif

    {{-- Tabla lado a lado --}}
    <x-filament::section icon="heroicon-o-table-cells">
        <x-slot name="heading">Detalle por empleado</x-slot>
        <x-slot name="description">
            Comparación lado a lado: IMSS · Nómina · SUA. Usa "Detalle" en cada fila para ver el desglose por rama.
            Los botones "Exportar resumen" y "Exportar completo" respetan los filtros activos.
        </x-slot>

        {{ $this->table }}
    </x-filament::section>

</div>
