@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Compulsa\Calculation> $calculations */
    /** @var int|null $activeId */
@endphp

<div class="space-y-3">
    @forelse ($calculations as $calc)
        @php
            $isActive = $activeId === $calc->id;
            $estado = $calc->estado;
            $tipo = $calc->tipo_periodo;
        @endphp
        <div @class([
            'group relative flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 pl-5 rounded-xl border overflow-hidden transition-all duration-150',
            'border-primary-300 bg-primary-50/70 dark:bg-primary-900/20 dark:border-primary-700 shadow-sm' => $isActive,
            'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm' => ! $isActive,
        ])>
            <span @class([
                'absolute left-0 top-0 bottom-0 w-1',
                'bg-primary-500' => $isActive,
                'bg-transparent group-hover:bg-gray-200 dark:group-hover:bg-gray-600' => ! $isActive,
            ])></span>

            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{ $calc->periodLabel() }}
                    </span>
                    @if($isActive)
                        <x-filament::badge color="primary" size="xs" icon="heroicon-o-eye">
                            Activa
                        </x-filament::badge>
                    @endif
                    <x-filament::badge :color="$estado?->getColor() ?? 'gray'" size="xs">
                        {{ $estado?->getLabel() ?? '—' }}
                    </x-filament::badge>
                    <x-filament::badge :color="$tipo?->getColor() ?? 'gray'" size="xs">
                        {{ $tipo?->getLabel() ?? '—' }}
                    </x-filament::badge>
                </div>
                <p class="mt-1.5 flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 truncate">
                    <x-heroicon-o-building-office-2 class="w-3.5 h-3.5 flex-shrink-0" />
                    {{ $calc->razonSocial?->name ?? 'Sin razón social' }}
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    RP {{ $calc->razonSocial?->registro_patronal ?? '—' }}
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    {{ $calc->created_at?->format('d/m/Y H:i') }}
                </p>
                <div class="mt-2 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                    <span class="inline-flex items-center gap-1">
                        <x-heroicon-o-users class="w-3.5 h-3.5" />
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ number_format((int) $calc->total_empleados) }}</span> empleados
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" />
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ number_format((int) $calc->empleados_con_diferencias) }}</span> con diferencias
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <x-heroicon-o-banknotes class="w-3.5 h-3.5" />
                        <span class="font-medium text-gray-700 dark:text-gray-300">$ {{ number_format((float) $calc->total_diferencia, 2) }}</span> total dif.
                    </span>
                </div>
            </div>

            <div class="flex-shrink-0 flex items-center gap-2 self-start sm:self-center">
                <x-filament::button
                    size="sm"
                    :color="$isActive ? 'gray' : 'primary'"
                    icon="heroicon-o-arrow-down-tray"
                    wire:click="loadCalculation({{ $calc->id }})"
                >
                    {{ $isActive ? 'Recargar' : 'Cargar' }}
                </x-filament::button>

                <x-filament::icon-button
                    icon="heroicon-o-trash"
                    color="danger"
                    label="Eliminar"
                    wire:click="deleteCalculation({{ $calc->id }})"
                    wire:confirm="¿Eliminar esta compulsa? Se borrarán todos sus registros y archivos de forma permanente."
                />
            </div>
        </div>
    @empty
        <div class="text-center py-14 text-gray-500 dark:text-gray-400">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-800 mb-3">
                <x-heroicon-o-clock class="w-7 h-7 opacity-60" />
            </div>
            <p class="font-medium text-gray-700 dark:text-gray-300">Todavía no hay compulsas registradas.</p>
            <p class="text-sm mt-1">Crea una nueva desde el botón "Nueva compulsa".</p>
        </div>
    @endforelse
</div>
