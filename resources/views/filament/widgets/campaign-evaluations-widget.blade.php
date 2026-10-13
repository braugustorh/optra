<x-filament-widgets::widget class="fi-campaign-evaluations-widget">
    <style>
        /* Tarjeta de actividad - Modo Claro */
        .fi-act-card {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 12px;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        .fi-act-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .fi-act-title {
            font-size: 14px;
            font-weight: 600;
            line-height: 1.3;
            margin: 0 0 3px 0;
            color: #111827;
        }
        .fi-act-subtitle {
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            color: #4b5563;
        }
        .fi-act-progress-bg {
            background-color: #e5e7eb;
        }
        .fi-act-done-row {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            color: #374151;
        }

        /* Tarjeta de actividad - Modo Oscuro */
        .dark .fi-act-card {
            background-color: #18181b;
            border: 1px solid #27272a;
        }
        .dark .fi-act-card:hover {
            border-color: #3f3f46;
            box-shadow: 0 2px 6px -1px rgba(0, 0, 0, 0.35);
        }
        .dark .fi-act-title {
            color: #f4f4f5;
        }
        .dark .fi-act-subtitle {
            color: #a1a1aa;
        }
        .dark .fi-act-progress-bg {
            background-color: #27272a;
        }
        .dark .fi-act-done-row {
            background-color: #18181b;
            border: 1px solid #27272a;
            color: #d4d4d8;
        }

        /* Colores por Categoría / Icono (Modo Claro & Oscuro) */
        .fi-act-cat-info { background-color: rgba(14, 165, 233, 0.12); color: #0284c7; }
        .dark .fi-act-cat-info { background-color: rgba(14, 165, 233, 0.22); color: #38bdf8; }

        .fi-act-cat-purple { background-color: rgba(168, 85, 247, 0.12); color: #7e22ce; }
        .dark .fi-act-cat-purple { background-color: rgba(168, 85, 247, 0.22); color: #c084fc; }

        .fi-act-cat-success { background-color: rgba(16, 185, 129, 0.12); color: #047857; }
        .dark .fi-act-cat-success { background-color: rgba(16, 185, 129, 0.22); color: #34d399; }

        .fi-act-cat-amber { background-color: rgba(245, 158, 11, 0.12); color: #b45309; }
        .dark .fi-act-cat-amber { background-color: rgba(245, 158, 11, 0.22); color: #fbbf24; }

        .fi-act-cat-primary { background-color: rgba(13, 148, 136, 0.12); color: #0f766e; }
        .dark .fi-act-cat-primary { background-color: rgba(13, 148, 136, 0.22); color: #2dd4bf; }

        /* Estado Vacío */
        .fi-act-empty-icon {
            background-color: rgba(16, 185, 129, 0.12);
            color: #10b981;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.08);
        }
        .fi-act-empty-title {
            font-size: 15px;
            font-weight: 700;
            line-height: 1.25;
            margin: 0 0 4px 0;
            color: #111827;
        }
        .fi-act-empty-desc {
            font-size: 12.5px;
            line-height: 1.4;
            margin: 0;
            color: #6b7280;
        }
        .dark .fi-act-empty-icon {
            background-color: rgba(16, 185, 129, 0.18);
            color: #34d399;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.12);
        }
        .dark .fi-act-empty-title {
            color: #f4f4f5;
        }
        .dark .fi-act-empty-desc {
            color: #a1a1aa;
        }
    </style>

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
                    <div style="width: 70px; height: 6px; border-radius: 9999px; overflow: hidden;" class="fi-act-progress-bg">
                        <div
                            style="height: 100%; border-radius: 9999px; background-color: var(--primary-600, #0d9488); width: {{ $completionRate }}%; transition: width 0.4s ease;"
                        ></div>
                    </div>
                </div>
            </x-slot>
        @endif

        @if($pendingCount > 0)
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($pendingActivities as $activity)
                    @php
                        $catClass = match($activity['icon_color'] ?? 'primary') {
                            'info' => 'fi-act-cat-info',
                            'purple' => 'fi-act-cat-purple',
                            'success' => 'fi-act-cat-success',
                            'amber' => 'fi-act-cat-amber',
                            default => 'fi-act-cat-primary',
                        };
                    @endphp

                    <div class="fi-act-card">
                        {{-- Bloque Superior: Icono e Información --}}
                        <div style="display: flex; align-items: flex-start; gap: 12px;">
                            <div
                                style="width: 38px; height: 38px; min-width: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; padding: 6px;"
                                class="{{ $catClass }}"
                            >
                                <x-filament::icon :icon="$activity['icon']" style="width: 20px; height: 20px;" />
                            </div>

                            <div style="flex: 1; min-width: 0;">
                                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 6px; margin-bottom: 5px;">
                                    <span
                                        class="{{ $catClass }}"
                                        style="display: inline-flex; font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 4px;"
                                    >
                                        {{ $activity['category'] }}
                                    </span>

                                    <x-filament::badge :color="$activity['badge_color']" size="xs">
                                        {{ $activity['badge'] }}
                                    </x-filament::badge>

                                    @if($activity['days_left'] !== null)
                                        <span style="display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 500;" class="text-gray-500 dark:text-gray-400">
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

                                <h4 class="fi-act-title">
                                    {{ $activity['title'] }}
                                </h4>

                                <p class="fi-act-subtitle">
                                    {{ $activity['subtitle'] }}
                                </p>

                                @if($activity['progress'] !== null)
                                    <div style="display: flex; align-items: center; gap: 8px; margin-top: 8px;">
                                        <div style="width: 90px; height: 6px; border-radius: 9999px; overflow: hidden;" class="fi-act-progress-bg">
                                            <div
                                                style="height: 100%; border-radius: 9999px; background-color: var(--primary-600, #0d9488); width: {{ $activity['progress'] }}%;"
                                            ></div>
                                        </div>
                                        <span style="font-size: 11px; font-weight: 500;" class="text-gray-500 dark:text-gray-400">
                                            {{ $activity['progress'] }}%
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Bloque Inferior: Botón de Acción --}}
                        <div style="display: flex; justify-content: flex-end; padding-top: 4px;">
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
            {{-- Estado Vacío Compacto: Cero Pendientes / All Caught Up --}}
            <div style="display: flex; align-items: center; gap: 16px; padding: 12px 6px;">
                <div style="width: 44px; height: 44px; min-width: 44px; min-height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-right: 2px;" class="fi-act-empty-icon">
                    <x-filament::icon icon="heroicon-o-check-badge" style="width: 24px; height: 24px;" />
                </div>
                <div style="flex: 1; min-width: 0;">
                    <h4 class="fi-act-empty-title">
                        {{ __('¡Estás al día!') }}
                    </h4>
                    <p class="fi-act-empty-desc">
                        {{ __('Has completado todas tus evaluaciones y actividades pendientes.') }}
                    </p>
                </div>
            </div>
        @endif

        {{-- Sección Colapsable: Historial de Actividades Completadas --}}
        @if($completedCount > 0)
            <details style="margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(156, 163, 175, 0.18);">
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
                            class="fi-act-done-row"
                            style="display: flex; align-items: center; justify-content: space-between; padding: 6px 10px; border-radius: 8px; font-size: 11px;"
                        >
                            <div style="display: flex; align-items: center; gap: 6px; min-width: 0; padding-right: 8px;">
                                <span style="font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
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
