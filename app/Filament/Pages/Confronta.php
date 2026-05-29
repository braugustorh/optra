<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Form;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use App\Models\Differences;
use Illuminate\Support\Collection;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DifferencesExport;
use Filament\Notifications\Notification;


class Confronta extends Page implements HasTable, HasForms, HasActions
{
    protected static bool $shouldRegisterNavigation = false;

    use InteractsWithTable, InteractsWithForms, InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.confronta';

    public Collection $differences;
    public $differencesQuery;
    public array $formData = [];
    public bool $showTable = false; // Nueva propiedad para controlar la visibilidad

    public function mount(): void
    {
        $this->differences = collect();
        $this->formData = ['file1' => null, 'file2' => null];
        $this->showTable = false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('formData.file1')
                    ->label('Archivo de Nomina')
                    ->helperText('Los archivos permitidos son hojas de calculo en formato oxml, xlsx, xls')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->disk('local')
                    ->directory('uploads')
                    ->preserveFilenames()
                    ->required(),
                FileUpload::make('formData.file2')
                    ->label('Archivo del IMSS')
                    ->helperText('Los archivos permitidos son hojas de calculo en formato oxml, xlsx, xls')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ])
                    ->disk('local')
                    ->directory('uploads')
                    ->preserveFilenames()
                    ->required(),
            ])
            ->statePath('formData');
    }

    public function submit(): void
    {
        try {
            $data = $this->form->getState();

            $file1 = $data['formData']['file1'] ?? null;
            $file2 = $data['formData']['file2'] ?? null;

            if (!$file1 || !$file2) {
                Notification::make()
                    ->title('Error')
                    ->body('Ambos archivos son requeridos')
                    ->danger()
                    ->send();
                return;
            }

            $this->processStoredFiles($file1, $file2);

            // Limpia el formulario
            $this->form->fill(['file1' => null, 'file2' => null]);
            $this->dispatch('close-modal', id: 'modal-form');

            Notification::make()
                ->title('Archivos cargados exitosamente')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Error al subir archivos: ' . $e->getMessage());

            Notification::make()
                ->title('Error al cargar los archivos')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function processStoredFiles(string $file1Path, string $file2Path): void
    {
        try {
            $data1 = Excel::toArray([], storage_path('app/' . $file1Path))[0];
            $data2 = Excel::toArray([], storage_path('app/' . $file2Path))[0];

            $headers1 = array_shift($data1);
            $headers2 = array_shift($data2);

            // Busca las columnas (case insensitive)
            $nombreIndex1 = $this->findColumnIndex($headers1, 'nombre');
            $sumaIndex1 = $this->findColumnIndex($headers1, 'suma');
            $nombreIndex2 = $this->findColumnIndex($headers2, 'nombre');
            $sumaIndex2 = $this->findColumnIndex($headers2, 'suma');

            if ($nombreIndex1 === false || $sumaIndex1 === false || $nombreIndex2 === false || $sumaIndex2 === false) {
                Notification::make()
                    ->title('Error en Archivos')
                    ->body('Uno o ambos archivos no tienen las columnas "nombre" y "suma".')
                    ->danger()
                    ->send();
                return;
            }

            $map1 = collect($data1)->keyBy(fn ($row) => $row[$nombreIndex1]);
            $map2 = collect($data2)->keyBy(fn ($row) => $row[$nombreIndex2]);

            $this->differences = collect();

            foreach ($map1 as $nombre => $row1) {
                if ($map2->has($nombre)) {
                    $row2 = $map2[$nombre];
                    $suma1 = $row1[$sumaIndex1];
                    $suma2 = $row2[$sumaIndex2];
                    if ($suma1 != $suma2) {
                        $this->differences->push([
                            'nombre' => $nombre,
                            'suma_file1' => $suma1,
                            'suma_file2' => $suma2,
                            'diferencia' => abs($suma1 - $suma2),
                        ]);
                    }
                } else {
                    $this->differences->push([
                        'nombre' => $nombre,
                        'suma_file1' => $row1[$sumaIndex1],
                        'suma_file2' => 'No existe',
                        'diferencia' => 'Falta en File2',
                    ]);
                }
            }

            foreach ($map2 as $nombre => $row2) {
                if (!$map1->has($nombre)) {
                    $this->differences->push([
                        'nombre' => $nombre,
                        'suma_file1' => 'No existe',
                        'suma_file2' => $row2[$sumaIndex2],
                        'diferencia' => 'Falta en File1',
                    ]);
                }
            }

            // Actualiza los datos en el modelo
            Differences::setDifferencesData($this->differences);
            // Limpia la caché de Sushi
            cache()->forget('sushi.'.Differences::class);
            // Muestra la tabla
            $this->showTable = $this->differences->isNotEmpty();
            // Refresca la tabla de Filament
            $this->refreshSushiModel();
            $this->differencesQuery=Differences::getDifferencesData();



            Notification::make()
                ->title('Proceso Completado')
                ->body('Datos procesados. Diferencias encontradas: ' . $this->differences->count())
                ->success()
                ->send();


        } catch (\Exception $e) {
            Log::error('Error al procesar archivos: ' . $e->getMessage());

            Notification::make()
                ->title('Error al procesar los archivos')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Fuerza la recarga del modelo Sushi
     */
    private function refreshSushiModel(): void
    {
        // Limpia toda la caché de Sushi
        cache()->forget('sushi.'.Differences::class);

        // Resetea la tabla de Filament
        $this->dispatch('$refresh');
    }

    /**
     * Encuentra el índice de una columna (case insensitive)
     */
    private function findColumnIndex(array $headers, string $columnName): int|false
    {
        foreach ($headers as $index => $header) {
            if (strtolower(trim($header)) === strtolower($columnName)) {
                return $index;
            }
        }
        return false;
    }

    public function table(Table $table): Table
    {

        return $table
            ->query(Differences::query())
            ->poll('5s')
            ->deferLoading()
            ->recordTitleAttribute('nombre')
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->sortable()->searchable(),
                TextColumn::make('suma_file1')->label('Valor en File 1')->sortable(),
                TextColumn::make('suma_file2')->label('Valor en File 2')->sortable(),
                BadgeColumn::make('diferencia')
                    ->color(fn ($record): string => str_contains($record?->diferencia ?? '', 'Falta') ? 'warning' : 'danger')
                    ->label(fn ($record): string => is_numeric($record?->diferencia ?? null)
                        ? 'Diferencia: ' . $record->diferencia
                        : ($record?->diferencia ?? 'N/A'))
            ])
            ->filters([
                SelectFilter::make('diferencia')
                    ->options([
                        'Falta en File1' => 'Falta en File1',
                        'Falta en File2' => 'Falta en File2',
                    ])
                    ->searchable(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([
                //Exportar a Excel
                BulkAction::make('exportar')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records) {
                        return Excel::download(
                            new DifferencesExport($records->pluck('nombre')->toArray()),
                            'diferencias_' . now()->format('Y-m-d_His') . '.xlsx'
                        );
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Exportar Diferencias')
                    ->modalDescription('¿Deseas exportar las diferencias seleccionadas a un archivo Excel?')
                    ->modalSubmitActionLabel('Exportar')

            ])
            ->emptyStateHeading('Sin Diferencias')
            ->emptyStateDescription('No se encontraron diferencias entre los archivos. Carga y procesa los archivos.');
    }

    public function openModalForm()
    {
        $this->dispatch('open-modal', id: 'modal-form');
    }

    public function closeModal(): void
    {
        $this->form->fill(['file1' => null, 'file2' => null]);
        $this->dispatch('close-modal', id: 'modal-form');
    }

    /**
     * Método para limpiar y empezar de nuevo
     */
    public function clearResults(): void
    {
        $this->differences = collect();
        Differences::clearDifferencesData();
        $this->showTable = false;
    }
}
