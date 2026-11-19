@php
    /** @var array{total: int, created: int, updated: int, failed_count: int, failures: array<int, array{row: int, identifier: string, errors: array<int, string>}>, mode: string, processed_at: string} $summary */
    $total = (int) ($summary['total'] ?? 0);
    $created = (int) ($summary['created'] ?? 0);
    $updated = (int) ($summary['updated'] ?? 0);
    $failedCount = (int) ($summary['failed_count'] ?? 0);
    $failures = $summary['failures'] ?? [];
    $mode = (string) ($summary['mode'] ?? 'create_only');
    $processedAt = (string) ($summary['processed_at'] ?? now()->format('d/m/Y H:i:s'));

    $processed = $created + $updated;
    $successRate = $total > 0 ? round(($processed / $total) * 100, 1) : 0.0;
    $modeLabel = $mode === 'upsert' ? 'Crear y actualizar existentes (CURP)' : 'Solo crear nuevos';

    $bannerState = match (true) {
        $total === 0 => 'warning',
        $failedCount === 0 => 'success',
        $processed > 0 => 'warning',
        default => 'danger',
    };

    $barColor = match (true) {
        $total === 0 => '#f59e0b',
        $failedCount === 0 => '#10b981',
        $processed > 0 => '#f59e0b',
        default => '#ef4444',
    };
@endphp

<style>
    .uirm-root {
        display: flex;
        flex-direction: column;
        gap: 1.125rem;
        font-family: inherit;
    }

    .uirm-banner {
        display: flex;
        align-items: flex-start;
        gap: 0.875rem;
        padding: 1rem 1.125rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
    }

    .uirm-banner--success {
        background-color: #ecfdf5;
        border-color: #a7f3d0;
        color: #065f46;
    }

    .uirm-banner--warning {
        background-color: #fffbeb;
        border-color: #fde68a;
        color: #92400e;
    }

    .uirm-banner--danger {
        background-color: #fef2f2;
        border-color: #fecaca;
        color: #991b1b;
    }

    .dark .uirm-banner--success {
        background-color: rgba(6, 78, 59, 0.28);
        border-color: rgba(16, 185, 129, 0.35);
        color: #a7f3d0;
    }

    .dark .uirm-banner--warning {
        background-color: rgba(120, 53, 15, 0.28);
        border-color: rgba(245, 158, 11, 0.35);
        color: #fde68a;
    }

    .dark .uirm-banner--danger {
        background-color: rgba(127, 29, 29, 0.28);
        border-color: rgba(239, 68, 68, 0.35);
        color: #fecaca;
    }

    .uirm-banner-icon {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .uirm-banner--success .uirm-banner-icon {
        background-color: #d1fae5;
        color: #059669;
    }

    .uirm-banner--warning .uirm-banner-icon {
        background-color: #fef3c7;
        color: #d97706;
    }

    .uirm-banner--danger .uirm-banner-icon {
        background-color: #fee2e2;
        color: #dc2626;
    }

    .dark .uirm-banner--success .uirm-banner-icon {
        background-color: rgba(16, 185, 129, 0.2);
        color: #34d399;
    }

    .dark .uirm-banner--warning .uirm-banner-icon {
        background-color: rgba(245, 158, 11, 0.2);
        color: #fbbf24;
    }

    .dark .uirm-banner--danger .uirm-banner-icon {
        background-color: rgba(239, 68, 68, 0.2);
        color: #f87171;
    }

    .uirm-banner-title {
        font-size: 0.9375rem;
        font-weight: 700;
        line-height: 1.35;
        margin: 0;
    }

    .uirm-banner-desc {
        font-size: 0.8125rem;
        line-height: 1.45;
        margin: 0.25rem 0 0 0;
        opacity: 0.9;
    }

    .uirm-kpi-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    @media (min-width: 640px) {
        .uirm-kpi-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    .uirm-kpi-card {
        padding: 0.875rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        background-color: #f9fafb;
    }

    .dark .uirm-kpi-card {
        background-color: rgba(31, 41, 55, 0.65);
        border-color: #374151;
    }

    .uirm-kpi-card--green {
        background-color: #ecfdf5;
        border-color: #a7f3d0;
    }

    .dark .uirm-kpi-card--green {
        background-color: rgba(6, 78, 59, 0.24);
        border-color: rgba(16, 185, 129, 0.35);
    }

    .uirm-kpi-card--blue {
        background-color: #eff6ff;
        border-color: #bfdbfe;
    }

    .dark .uirm-kpi-card--blue {
        background-color: rgba(30, 58, 138, 0.24);
        border-color: rgba(59, 130, 246, 0.35);
    }

    .uirm-kpi-card--red {
        background-color: #fef2f2;
        border-color: #fecaca;
    }

    .dark .uirm-kpi-card--red {
        background-color: rgba(127, 29, 29, 0.24);
        border-color: rgba(239, 68, 68, 0.35);
    }

    .uirm-kpi-label {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
    }

    .dark .uirm-kpi-label {
        color: #9ca3af;
    }

    .uirm-kpi-card--green .uirm-kpi-label { color: #047857; }
    .dark .uirm-kpi-card--green .uirm-kpi-label { color: #6ee7b7; }
    .uirm-kpi-card--blue .uirm-kpi-label { color: #1d4ed8; }
    .dark .uirm-kpi-card--blue .uirm-kpi-label { color: #93c5fd; }
    .uirm-kpi-card--red .uirm-kpi-label { color: #b91c1c; }
    .dark .uirm-kpi-card--red .uirm-kpi-label { color: #fca5a5; }

    .uirm-kpi-value {
        margin-top: 0.375rem;
        font-size: 1.625rem;
        font-weight: 800;
        line-height: 1.1;
        color: #111827;
    }

    .dark .uirm-kpi-value {
        color: #f9fafb;
    }

    .uirm-kpi-card--green .uirm-kpi-value { color: #065f46; }
    .dark .uirm-kpi-card--green .uirm-kpi-value { color: #a7f3d0; }
    .uirm-kpi-card--blue .uirm-kpi-value { color: #1e40af; }
    .dark .uirm-kpi-card--blue .uirm-kpi-value { color: #bfdbfe; }
    .uirm-kpi-card--red .uirm-kpi-value { color: #991b1b; }
    .dark .uirm-kpi-card--red .uirm-kpi-value { color: #fecaca; }

    .uirm-progress-box {
        padding: 0.875rem 1rem;
        border-radius: 0.75rem;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
    }

    .dark .uirm-progress-box {
        background-color: rgba(31, 41, 55, 0.45);
        border-color: #374151;
    }

    .uirm-progress-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        font-size: 0.75rem;
        color: #4b5563;
        margin-bottom: 0.5rem;
    }

    .dark .uirm-progress-meta {
        color: #d1d5db;
    }

    .uirm-progress-track {
        width: 100%;
        height: 8px;
        border-radius: 9999px;
        background-color: #e5e7eb;
        overflow: hidden;
    }

    .dark .uirm-progress-track {
        background-color: #374151;
    }

    .uirm-table-card {
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        background-color: #ffffff;
        overflow: hidden;
    }

    .dark .uirm-table-card {
        border-color: #374151;
        background-color: #111827;
    }

    .uirm-table-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        background-color: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }

    .dark .uirm-table-toolbar {
        background-color: rgba(31, 41, 55, 0.65);
        border-bottom-color: #374151;
    }

    .uirm-toolbar-title-wrap {
        display: flex;
        align-items: center;
        gap: 0.625rem;
    }

    .uirm-toolbar-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #111827;
        margin: 0;
    }

    .dark .uirm-toolbar-title {
        color: #f9fafb;
    }

    .uirm-toolbar-subtitle {
        font-size: 0.75rem;
        color: #6b7280;
        margin: 0.125rem 0 0 0;
    }

    .dark .uirm-toolbar-subtitle {
        color: #9ca3af;
    }

    .uirm-toolbar-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    .uirm-search-input {
        font-size: 0.8125rem;
        line-height: 1.25;
        padding: 0.4375rem 0.75rem;
        min-width: 215px;
        border-radius: 0.5rem;
        border: 1px solid #d1d5db;
        background-color: #ffffff;
        color: #111827;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .uirm-search-input:focus {
        border-color: #0d9488;
        box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2);
    }

    .dark .uirm-search-input {
        border-color: #4b5563;
        background-color: #1f2937;
        color: #f3f4f6;
    }

    .uirm-table-scroll {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 24rem;
    }

    .uirm-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
        font-size: 0.8125rem;
    }

    .uirm-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        padding: 0.625rem 1rem;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #6b7280;
        background-color: #f3f4f6;
        border-bottom: 1px solid #e5e7eb;
        white-space: nowrap;
    }

    .dark .uirm-table thead th {
        color: #9ca3af;
        background-color: #1f2937;
        border-bottom-color: #374151;
    }

    .uirm-table tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.12s ease;
    }

    .uirm-table tbody tr:last-child {
        border-bottom: none;
    }

    .uirm-table tbody tr:hover {
        background-color: #f9fafb;
    }

    .dark .uirm-table tbody tr {
        border-bottom-color: #1f2937;
    }

    .dark .uirm-table tbody tr:hover {
        background-color: rgba(31, 41, 55, 0.5);
    }

    .uirm-table tbody td {
        padding: 0.75rem 1rem;
        vertical-align: top;
    }

    .uirm-identifier {
        font-weight: 600;
        color: #111827;
    }

    .dark .uirm-identifier {
        color: #f3f4f6;
    }

    .uirm-error-list {
        margin: 0;
        padding: 0;
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        font-size: 0.75rem;
        color: #b91c1c;
    }

    .dark .uirm-error-list {
        color: #fca5a5;
    }

    .uirm-error-item {
        display: flex;
        align-items: flex-start;
        gap: 0.375rem;
        line-height: 1.4;
    }
</style>

<div class="uirm-root">
    {{-- Banner de Estado General --}}
    @if ($total === 0)
        <div class="uirm-banner uirm-banner--warning">
            <div class="uirm-banner-icon">
                <x-heroicon-o-information-circle style="width: 1.25rem; height: 1.25rem;" />
            </div>
            <div>
                <p class="uirm-banner-title">
                    El archivo no contenía colaboradores para procesar
                </p>
                <p class="uirm-banner-desc">
                    Verifica que los datos estén capturados en la primera hoja ("Colaboradores") a partir de la fila 2.
                </p>
            </div>
        </div>
    @elseif ($failedCount === 0)
        <div class="uirm-banner uirm-banner--success">
            <div class="uirm-banner-icon">
                <x-heroicon-o-check-circle style="width: 1.25rem; height: 1.25rem;" />
            </div>
            <div>
                <p class="uirm-banner-title">
                    Importación completada al 100% sin errores
                </p>
                <p class="uirm-banner-desc">
                    Se procesaron {{ number_format($total) }} fila(s) correctamente bajo la modalidad "{{ $modeLabel }}".
                </p>
            </div>
        </div>
    @elseif ($processed > 0)
        <div class="uirm-banner uirm-banner--warning">
            <div class="uirm-banner-icon">
                <x-heroicon-o-exclamation-triangle style="width: 1.25rem; height: 1.25rem;" />
            </div>
            <div>
                <p class="uirm-banner-title">
                    Importación parcial: {{ number_format($processed) }} procesado(s) y {{ number_format($failedCount) }} con errores
                </p>
                <p class="uirm-banner-desc">
                    Los colaboradores válidos ya fueron guardados en el sistema. Corrige únicamente las {{ number_format($failedCount) }} fila(s) detalladas abajo y vuelve a importarlas.
                </p>
            </div>
        </div>
    @else
        <div class="uirm-banner uirm-banner--danger">
            <div class="uirm-banner-icon">
                <x-heroicon-o-x-circle style="width: 1.25rem; height: 1.25rem;" />
            </div>
            <div>
                <p class="uirm-banner-title">
                    No se pudo importar ningún colaborador ({{ number_format($failedCount) }} fila(s) rechazada(s))
                </p>
                <p class="uirm-banner-desc">
                    Revisa las causas por fila en la tabla inferior o descarga el reporte Excel de errores para corregir tu archivo.
                </p>
            </div>
        </div>
    @endif

    {{-- Tarjetas KPI de Resumen --}}
    <div class="uirm-kpi-grid">
        <div class="uirm-kpi-card">
            <div class="uirm-kpi-label">Filas Analizadas</div>
            <div class="uirm-kpi-value">{{ number_format($total) }}</div>
        </div>

        <div class="uirm-kpi-card uirm-kpi-card--green">
            <div class="uirm-kpi-label">Nuevos Creados</div>
            <div class="uirm-kpi-value">{{ number_format($created) }}</div>
        </div>

        <div class="uirm-kpi-card uirm-kpi-card--blue">
            <div class="uirm-kpi-label">Actualizados</div>
            <div class="uirm-kpi-value">{{ number_format($updated) }}</div>
        </div>

        <div @class(['uirm-kpi-card', 'uirm-kpi-card--red' => $failedCount > 0])>
            <div class="uirm-kpi-label">Con Error</div>
            <div class="uirm-kpi-value">{{ number_format($failedCount) }}</div>
        </div>
    </div>

    {{-- Barra de Progreso y Metadatos --}}
    <div class="uirm-progress-box">
        <div class="uirm-progress-meta">
            <span>
                Efectividad del lote: <strong>{{ number_format($successRate, 1) }}%</strong>
                ({{ number_format($processed) }} de {{ number_format($total) }} filas aplicadas)
            </span>
            <span>Procesado: {{ $processedAt }}</span>
        </div>
        <div class="uirm-progress-track">
            <div style="width: {{ $successRate }}%; height: 100%; background-color: {{ $barColor }}; transition: width 0.3s ease;"></div>
        </div>
    </div>

    {{-- Detalle Completo de Filas con Error --}}
    @if ($failedCount > 0)
        <div x-data="{ search: '' }" class="uirm-table-card">
            <div class="uirm-table-toolbar">
                <div class="uirm-toolbar-title-wrap">
                    <x-heroicon-o-exclamation-circle style="width: 1.25rem; height: 1.25rem; color: #dc2626; flex-shrink: 0;" />
                    <div>
                        <h4 class="uirm-toolbar-title">
                            Detalle de filas rechazadas ({{ number_format($failedCount) }})
                        </h4>
                        <p class="uirm-toolbar-subtitle">
                            Se muestran todas las filas con error sin truncamiento.
                        </p>
                    </div>
                </div>

                <div class="uirm-toolbar-actions">
                    <input
                        type="search"
                        x-model="search"
                        placeholder="Filtrar fila, CURP o error..."
                        class="uirm-search-input"
                    />

                    <x-filament::button
                        color="danger"
                        size="sm"
                        icon="heroicon-o-document-arrow-down"
                        wire:click="downloadImportFailuresReport"
                        wire:loading.attr="disabled"
                    >
                        Descargar Errores (.xlsx)
                    </x-filament::button>
                </div>
            </div>

            <div class="uirm-table-scroll">
                <table class="uirm-table">
                    <thead>
                        <tr>
                            <th>Fila Excel</th>
                            <th>Colaborador / Identificador</th>
                            <th>Motivo(s) del Rechazo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($failures as $failure)
                            @php
                                $searchableText = mb_strtolower(
                                    'fila ' . $failure['row'] . ' ' . ($failure['identifier'] ?? '') . ' ' . implode(' ', $failure['errors'] ?? []),
                                    'UTF-8'
                                );
                            @endphp
                            <tr x-show="search.trim() === '' || @js($searchableText).includes(search.trim().toLowerCase())">
                                <td style="white-space: nowrap;">
                                    <x-filament::badge color="danger" size="sm">
                                        Fila {{ $failure['row'] }}
                                    </x-filament::badge>
                                </td>
                                <td class="uirm-identifier">
                                    {{ $failure['identifier'] }}
                                </td>
                                <td>
                                    <ul class="uirm-error-list">
                                        @foreach ($failure['errors'] as $error)
                                            <li class="uirm-error-item">
                                                <span style="font-weight: 700;">•</span>
                                                <span>{{ $error }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
