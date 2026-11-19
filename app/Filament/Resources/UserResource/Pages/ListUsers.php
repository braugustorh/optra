<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Exports\Users\UserImportFailuresExport;
use App\Exports\Users\UserImportTemplateExport;
use App\Filament\Resources\UserResource;
use App\Imports\UsersImport;
use App\Models\Sede;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
    protected static ?string $title='Usuarios';

    public ?array $lastImportSummary = null;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isGlobalAdmin = $user && $user->hasAnyRole(['Super Administrador', 'Administrador', 'RH Corp']);
        $isLocalRh = $user && $user->hasRole('RH') && ! $isGlobalAdmin;

        $disabled = true;
        if ($isGlobalAdmin) {
            $disabled = false;
        } elseif ($isLocalRh) {
            $sede = Sede::find($user->sede_id);
            $disabled = $sede && ($sede->count_positions($sede->id) >= $sede->open_positions);
        }

        $canManageBulkUsers = $isGlobalAdmin || $isLocalRh;

        return [
            Actions\Action::make('downloadTemplate')
                ->label('Descargar Plantilla')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->visible($canManageBulkUsers)
                ->action(fn () => Excel::download(
                    new UserImportTemplateExport(auth()->user()),
                    'plantilla_importacion_colaboradores.xlsx'
                )),

            Actions\Action::make('importUsers')
                ->label('Importar Usuarios')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible($canManageBulkUsers)
                ->disabled($disabled)
                ->tooltip($disabled ? 'Se ha alcanzado el límite de posiciones abiertas' : null)
                ->modalHeading('Importación Masiva de Colaboradores')
                ->modalDescription('Descargue primero la plantilla oficial, llene los datos de los colaboradores y súbala en formato .xlsx o .csv.')
                ->modalSubmitActionLabel('Procesar Importación')
                ->modalWidth('xl')
                ->form([
                    FileUpload::make('file')
                        ->label('Archivo de Plantilla (.xlsx o .csv)')
                        ->disk('local')
                        ->directory('imports/users')
                        ->preserveFilenames()
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'application/csv',
                            'text/plain',
                            'application/zip',
                            'application/octet-stream',
                        ])
                        ->helperText('Formato recomendado: .xlsx generado desde "Descargar Plantilla".')
                        ->required(),
                    Select::make('mode')
                        ->label('Modalidad de Importación')
                        ->options([
                            'create_only' => 'Solo crear nuevos colaboradores',
                            'upsert' => 'Crear nuevos y actualizar existentes (por CURP)',
                        ])
                        ->default('create_only')
                        ->native(false)
                        ->required(),
                    Toggle::make('auto_create_structure')
                        ->label('Crear automáticamente Departamentos y Puestos si no existen en la Sede destino')
                        ->default(false)
                        ->visible(fn () => $isGlobalAdmin),
                    Toggle::make('auto_adjust_open_positions')
                        ->label('Ajustar automáticamente las plazas disponibles (open_positions) de la Sede')
                        ->default(true)
                        ->visible(fn () => $isGlobalAdmin),
                ])
                ->action(function (array $data) use ($isGlobalAdmin): void {
                    $disk = Storage::disk('local');
                    $relativePath = is_array($data['file'] ?? null) ? reset($data['file']) : ($data['file'] ?? null);

                    if (! is_string($relativePath) || $relativePath === '' || ! $disk->exists($relativePath)) {
                        Notification::make()
                            ->danger()
                            ->title('Archivo no encontrado')
                            ->body('No se pudo localizar el archivo cargado. Inténtelo de nuevo.')
                            ->send();

                        return;
                    }

                    $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
                    if (! in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
                        $disk->delete($relativePath);
                        Notification::make()
                            ->danger()
                            ->title('Formato no válido')
                            ->body('Solo se permiten archivos .xlsx, .xls o .csv.')
                            ->send();

                        return;
                    }

                    $importer = new UsersImport(
                        actor: auth()->user(),
                        mode: (string) ($data['mode'] ?? 'create_only'),
                        autoCreateStructure: $isGlobalAdmin && ! empty($data['auto_create_structure']),
                        autoAdjustOpenPositions: $isGlobalAdmin ? ! empty($data['auto_adjust_open_positions']) : false,
                    );

                    try {
                        Excel::import($importer, $disk->path($relativePath));
                    } catch (\Throwable $e) {
                        Log::error('Error en importación masiva de usuarios: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('Error al procesar el archivo')
                            ->body($e->getMessage())
                            ->persistent()
                            ->send();

                        return;
                    } finally {
                        if ($disk->exists($relativePath)) {
                            $disk->delete($relativePath);
                        }
                    }

                    $created = $importer->getCreatedCount();
                    $updated = $importer->getUpdatedCount();
                    $failures = $importer->getFailures();
                    $failedCount = count($failures);

                    $this->lastImportSummary = [
                        'total' => $created + $updated + $failedCount,
                        'created' => $created,
                        'updated' => $updated,
                        'failed_count' => $failedCount,
                        'failures' => $failures,
                        'mode' => (string) ($data['mode'] ?? 'create_only'),
                        'processed_at' => now()->format('d/m/Y H:i:s'),
                    ];

                    if ($failedCount === 0) {
                        if ($created === 0 && $updated === 0) {
                            Notification::make()
                                ->warning()
                                ->title('Sin registros procesados')
                                ->body('El archivo no contenía filas de colaboradores para importar.')
                                ->send();
                        } else {
                            Notification::make()
                                ->success()
                                ->title('Importación completada')
                                ->body("Se crearon {$created} colaborador(es) y se actualizaron {$updated} correctamente.")
                                ->send();
                        }
                    } elseif ($importer->getProcessedCount() > 0) {
                        Notification::make()
                            ->warning()
                            ->title('Importación completada con observaciones')
                            ->body("Procesados: {$importer->getProcessedCount()} | Con error: {$failedCount}. Revisa el detalle en el modal.")
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('No se pudieron importar los registros')
                            ->body("Se detectaron {$failedCount} fila(s) con error. Revisa el detalle en el modal.")
                            ->send();
                    }

                    $this->replaceMountedAction('importResultsModal');
                }),

            Actions\CreateAction::make()
                ->label('Agregar Usuario')
                ->icon('heroicon-o-plus')
                ->disabled($disabled)
                ->tooltip($disabled ? 'Se ha alcanzado el límite de posiciones abiertas' : null),
        ];
    }

    public function importResultsModalAction(): Actions\Action
    {
        return Actions\Action::make('importResultsModal')
            ->modalHeading('Resultados de la Importación de Colaboradores')
            ->modalDescription('Resumen de registros procesados y detalle completo de filas con observaciones.')
            ->modalWidth('4xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn () => view('filament.resources.user-resource.partials.import-results-modal', [
                'summary' => $this->lastImportSummary ?? [
                    'total' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'failed_count' => 0,
                    'failures' => [],
                    'mode' => 'create_only',
                    'processed_at' => now()->format('d/m/Y H:i:s'),
                ],
            ]));
    }

    public function downloadImportFailuresReport()
    {
        $failures = $this->lastImportSummary['failures'] ?? [];
        if (empty($failures)) {
            return null;
        }

        return Excel::download(
            new UserImportFailuresExport($failures),
            'reporte_errores_importacion_colaboradores.xlsx'
        );
    }

    public function getTabs(): array
    {
        return [
            'activos' => Tab::make('Activos')
                ->icon('heroicon-o-check-circle')
                ->badge(fn () => UserResource::applyUserScopeQuery(
                    UserResource::getModel()::query()->where('status', true)
                )->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', true)),

            'inactivos' => Tab::make('Inactivos')
                ->icon('heroicon-o-user-minus')
                ->badge(fn () => UserResource::applyUserScopeQuery(
                    UserResource::getModel()::query()->where('status', false)
                )->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', false)),

            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-users')
                ->badge(fn () => UserResource::applyUserScopeQuery(
                    UserResource::getModel()::query()
                )->count())
                ->badgeColor('gray'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'activos';
    }

    protected function getHeaderWidgets(): array
    {
        return [
          UserResource\Widgets\UsersStatsOverview::class,
        ];
    }
}
