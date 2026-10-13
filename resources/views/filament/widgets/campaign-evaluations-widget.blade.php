<x-filament-widgets::widget class="fi-campaign-evaluations-widget">
    <x-filament::section
        icon="heroicon-o-clipboard-document-check"
        icon-color="primary"
        icon-size="lg"
    >
        <x-slot name="heading">
            <div style="display: inline-flex; align-items: center; gap: 12px;">
                <span style="font-weight: 700; font-size: 16px;">{{ __('Actividades Pendientes') }}</span>
                @if($pendingCount > 0)
                    <span style="display: inline-flex; margin-left: 4px;">
                        <x-filament::badge color="warning" size="sm">
                            {{ $pendingCount }} {{ $pendingCount === 1 ? __('pendiente') : __('pendientes') }}
                        </x-filament::badge>
                    </span>
                @else
                    <span style="display: inline-flex; margin-left: 4px;">
                        <x-filament::badge color="success" size="sm">
                            {{ __('Al día') }}
                        </x-filament::badge>
                    </span>
                @endif
            </div>
        </x-slot>

        @if($totalActivities > 0 && $pendingCount > 0)
            <x-slot name="headerEnd">
                <div style="display: flex; align-items: center; gap: 8px; font-size: 12px;" class="text-gray-500 dark:text-gray-400">
                    <span style="font-weight: 500; white-space: nowrap;" class="hidden sm:inline">
                        {{ $completedCount }}/{{ $totalActivities }}
                    </span>
                    <div style="width: 70px; height: 6px; background-color: rgba(156, 163, 175, 0.25); border-radius: 9999px; overflow: hidden;">
                        <div
                            style="height: 100%; border-radius: 9999px; background-color: var(--primary-600, #0d9488); width: {{ $completionRate }}%; transition: width 0.4s ease;"
                        ></div>
                    </div>
                </div>
            </x-slot>
        @endif

        @if($pendingCount > 0)
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach($pendingActivities as $activity)
                    @php
                        $iconBgClasses = match($activity['icon_color'] ?? 'primary') {
                            'info' => 'bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400',
                            'purple' => 'bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400',
                            'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400',
                            'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400',
                            default => 'bg-primary-50 text-primary-600 dark:bg-primary-950/60 dark:text-primary-400',
                        };

                        $catBadgeClasses = match($activity['icon_color'] ?? 'primary') {
                            'info' => 'text-sky-700 bg-sky-100/70 dark:text-sky-300 dark:bg-sky-900/40',
                            'purple' => 'text-purple-700 bg-purple-100/70 dark:text-purple-300 dark:bg-purple-900/40',
                            'success' => 'text-emerald-700 bg-emerald-100/70 dark:text-emerald-300 dark:bg-emerald-900/40',
                            'amber' => 'text-amber-700 bg-amber-100/70 dark:text-amber-300 dark:bg-amber-900/40',
                            default => 'text-primary-700 bg-primary-100/70 dark:text-primary-300 dark:bg-primary-900/40',
                        };
                    @endphp

                    <div
                        style="display: flex; flex-direction: column; gap: 10px; padding: 12px 14px; border-radius: 12px; border: 1px solid rgba(156, 163, 175, 0.2); background-color: rgba(255, 255, 255, 0.9);"
                        class="dark:bg-gray-900/60 dark:border-gray-800"
                    >
                        {{-- Bloque Superior: Icono e Información --}}
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div
                                style="width: 36px; height: 36px; min-width: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; padding: 6px;"
                                class="{{ $iconBgClasses }}"
                            >
                                <x-filament::icon :icon="$activity['icon']" style="width: 18px; height: 18px;" />
                            </div>

                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 4px;">
                                    <span
                                        class="{{ $catBadgeClasses }}"
                                        style="display: inline-flex; font-size: 10.5px; font-weight: 600; padding: 1px 6px; border-radius: 4px;"
                                    >
                                        {{ $activity['category'] }}
                                    </span>

                                    <x-filament::badge :color="$activity['badge_color']" size="xs">
                                        {{ $activity['badge'] }}
                                    </x-filament::badge>

                                    @if($activity['days_left'] !== null)
                                        <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; color: #6b7280; font-weight: 500;">
                                            <x-filament::icon icon="heroicon-m-clock" style="width: 12px; height: 12px;" />
                                            @if($activity['days_left'] <= 0)
                                                <span style="color: #ef4444; font-weight: 600;">{{ __('Vence hoy') }}</span>
                                            @elseif($activity['days_left'] === 1)
                                                <span style="color: #f59e0b; font-weight: 600;">{{ __('Vence mañana') }}</span>
                                            @else
                                                <span>{{ $activity['days_left'] }}d</span>
                                            @endif
                                        </span>
                                    @endif
                                </div>

                                <h4 style="font-size: 13.5px; font-weight: 600; line-height: 1.3; margin: 0 0 2px 0;" class="text-gray-950 dark:text-white">
                                    {{ $activity['title'] }}
                                </h4>

                                <p style="font-size: 11.5px; line-height: 1.35; margin: 0; color: #6b7280;" class="dark:text-gray-400">
                                    {{ $activity['subtitle'] }}
                                </p>

                                @if($activity['progress'] !== null)
                                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 6px;">
                                        <div style="width: 80px; height: 5px; background-color: rgba(156, 163, 175, 0.25); border-radius: 9999px; overflow: hidden;">
                                            <div
                                                style="height: 100%; border-radius: 9999px; background-color: var(--primary-600, #0d9488); width: {{ $activity['progress'] }}%;"
                                            ></div>
                                        </div>
                                        <span style="font-size: 10.5px; color: #6b7280; font-weight: 500;">
                                            {{ $activity['progress'] }}%
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Bloque Inferior: Botón de Acción --}}
                        <div style="display: flex; justify-content: flex-end; padding-top: 2px;">
                            <x-filament::button
                                :href="$activity['url']"
                                tag="a"
                                color="primary"
                                size="xs"
                                icon="heroicon-m-arrow-right"
                                icon-position="after"
                            >
                                {{ $activity['action_text'] }}
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            {{-- Estado Vacío Compacto: Cero Pendientes / All Caught Up con Estilos Directos --}}
            <div style="display: flex; align-items: center; gap: 16px; padding: 12px 6px;">
                <div style="width: 44px; height: 44px; min-width: 44px; min-height: 44px; border-radius: 50%; background-color: rgba(16, 185, 129, 0.12); color: #10b981; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-right: 2px; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.08);">
                    <x-filament::icon icon="heroicon-o-check-badge" style="width: 24px; height: 24px; color: #10b981;" />
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h4 style="font-size: 15px; font-weight: 700; line-height: 1.25; margin: 0 0 4px 0;" class="text-gray-950 dark:text-white">
                        {{ __('¡Estás al día!') }}
                    </h4>
                    <p style="font-size: 12.5px; line-height: 1.4; margin: 0; color: #6b7280;" class="dark:text-gray-400">
                        {{ __('Has completado todas tus evaluaciones y actividades pendientes.') }}
                    </p>
                </div>
            </div>
        @endif

        {{-- Sección Colapsable Opcional: Historial de Actividades Completadas --}}
        @if($completedCount > 0)
            <details style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(156, 163, 175, 0.18);">
                <summary style="display: flex; align-items: center; justify-content: space-between; cursor: pointer; font-size: 11px; font-weight: 600; color: #6b7280; user-select: none; padding: 2px 0;">
                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                        <x-filament::icon icon="heroicon-m-check-circle" style="width: 14px; height: 14px; color: #10b981;" />
                        {{ __('Completadas en el periodo') }} ({{ $completedCount }})
                    </span>
                    <x-filament::icon icon="heroicon-m-chevron-down" style="width: 14px; height: 14px;" />
                </summary>

                <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 6px;">
                    @foreach($completedActivities as $done)
                        <div
                            style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; border-radius: 8px; background-color: rgba(243, 244, 246, 0.7); border: 1px solid rgba(229, 231, 235, 0.8); font-size: 11px;"
                            class="dark:bg-gray-800/40 dark:border-gray-800"
                        >
                            <div style="display: flex; align-items: center; gap: 6px; min-width: 0; padding-right: 8px;">
                                <span style="font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" class="text-gray-800 dark:text-gray-200">
                                    {{ $done['title'] }}
                                </span>
                            </div>
                            <x-filament::badge color="success" size="xs">
                                {{ __('Completada') }}
                            </x-filament::badge>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
