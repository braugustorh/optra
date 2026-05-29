<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\IndicatorProgressTemplateExport;
use App\Imports\IndicatorProgressImport;
use Illuminate\Support\Facades\Validator;
use Filament\Notifications\Notification;

class BulkImportIndicator extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-m-arrow-up-on-square';
    protected static ?string $navigationLabel = 'Importación de Indicadores';
    protected static ?string $navigationGroup = 'Tablero de Control';
    protected ?string $heading = 'Tablero de Control';
    protected ?string $subheading = 'Importación masiva de indicadores';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.bulk-import-indicator';

    public ?array $bulkImport = [
    ];

    public static function canView(): bool
    {
        //Este Panel solo lo debe de ver los Jefes de Área y el Administrador
        //Se debe de agregar la comprobación de que estpo se cumpla para que solo sea visible para los Jefes de Área
        if (\auth()->user()->hasAnyRole('RH Corp','RH','Supervisor','Administrador','Gerente')) {
            return true;
        }else{
            return false;
        }

    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getForms(): array
    {
        return ['bulkImportForm'];
    }

    public function bulkImportForm(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('file')
                    ->label('Archivo de Carga Masiva')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                        'text/csv',
                    ])
                    ->disk('sedyco_disk')
                    ->visibility('public')
                    ->rules([
                        'required',
                        'file',
                        'mimes:xlsx,csv',
                    ])
                    ->key('file')
                    ->helperText('Selecciona el archivo de Plantilla con los indicadores a importar')
                    ->statePath('bulkImport.file'),
                Actions::make([
                    Action::make('import')
                        ->label('Importar')
                        ->action('import')
                        ->color('primary')
                        ->icon('heroicon-s-cloud-arrow-up'),
                    Action::make('downloadTemplate')
                        ->label('Descargar Plantilla')
                        ->action('downloadTemplate')
                        ->color('info')
                        ->icon('heroicon-s-document-arrow-down'),
                ]),
            ]);
    }

    public function downloadTemplate()
    {
        // Obtener los usuarios e indicadores basados en la consulta del supervisor
        $supervisorId = auth()->user()->position_id;

        $users = User::where('status', true)
            ->whereNotNull('department_id')
            ->whereNotNull('position_id')
            ->whereNotNull('sede_id')
            ->whereHas('position', function ($query) use ($supervisorId) {
                $query->where('supervisor_id', $supervisorId);
            })
            ->with('indicators') // Cargar los indicadores de cada usuario
            ->get();

        // Generar y descargar la plantilla
        return Excel::download(new IndicatorProgressTemplateExport($users), 'indicator_progress_template.xlsx');
    }

    public function import()
    {
        // Validar que el archivo se haya cargado
        if (!isset($this->bulkImport['file']) || !is_array($this->bulkImport['file']) || empty($this->bulkImport['file'])) {
            Notification::make()
                ->danger()
                ->title('Error en la importación')
                ->body('No se ha seleccionado ningún archivo.')
                ->duration(5000)
                ->send();
            return;
        }

        $fileInfo = reset($this->bulkImport['file']); // Obtiene el primer (y único) elemento del array
        $localFilePath = $fileInfo; // El valor es la ruta local temporal

        Log::debug('Ruta del archivo temporal local:', ['path' => $localFilePath]);

        try {
            // Verificar si el archivo temporal existe
            if (!file_exists($localFilePath)) {
                Notification::make()
                    ->danger()
                    ->title('Error en la importación')
                    ->body('No se encontró el archivo temporal.')
                    ->duration(5000)
                    ->send();
                return;
            }

            // Subir el archivo a S3
            $s3FileName = 'imports/' . time() . '_' . basename($localFilePath); // Genera un nombre único para S3
            \Storage::disk('sedyco_disk')->putFileAs('imports', $localFilePath, basename($s3FileName));
            $s3FilePath = Storage::disk('sedyco_disk')->url($s3FileName);

            Log::debug('Archivo subido a S3:', ['path' => $s3FilePath]);

            // Importar el archivo desde el stream de S3
            $stream = Storage::disk('sedyco_disk')->readStream('imports/' . basename($s3FileName));

            if (!$stream) {
                Notification::make()
                    ->danger()
                    ->title('Error al leer el archivo desde S3')
                    ->body('No se pudo leer el archivo desde S3 después de la carga.')
                    ->duration(5000)
                    ->send();
                return;
            }

            $import = new IndicatorProgressImport($this->getValidUserIds());
            Excel::import($import, $stream, null, \Maatwebsite\Excel\Excel::XLSX);

            Notification::make()
                ->success()
                ->title('Importación Realizada')
                ->body('Se importaron ' . $import->getRowCount() . ' registros correctamente.')
                ->duration(5000)
                ->send();

            $this->reset('bulkImport');
            $this->dispatch('reset-file');

            // Eliminar el archivo temporal local
            if (file_exists($localFilePath)) {
                unlink($localFilePath);
                Log::debug('Archivo temporal local eliminado:', ['path' => $localFilePath]);
            }

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $errors = $e->validator->errors()->all();
            Notification::make()
                ->danger()
                ->title('Error en la importación')
                ->body('Errores encontrados: <br>' . implode(', ', $errors))
                ->duration(10000)
                ->send();
            $this->reset('bulkImport');
            // Eliminar el archivo temporal local en caso de error
            if (file_exists($localFilePath)) {
                unlink($localFilePath);
            }
        } catch (\Exception $e) {
            Log::error('Error durante la importación:', ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]);
            Notification::make()
                ->danger()
                ->title('Error en la importación')
                ->body('Ocurrió un error durante la importación: ' . $e->getMessage())
                ->duration(10000)
                ->send();
            $this->reset('bulkImport');
            // Eliminar el archivo temporal local en caso de error
            if (file_exists($localFilePath)) {
                unlink($localFilePath);
            }
        }
    }

    protected function getValidUserIds(): array
    {
        $supervisorId = auth()->user()->position_id;
        return User::where('status', true)
            ->whereNotNull('department_id')
            ->whereNotNull('position_id')
            ->whereNotNull('sede_id')
            ->whereHas('position', function ($query) use ($supervisorId) {
                $query->where('supervisor_id', $supervisorId);
            })
            ->pluck('id')->toArray();
    }


   /* public function import()
    {
        // Validar que el archivo exista
        if (!isset($this->bulkImport['file'])) {
            Notification::make()
                ->danger()
                ->title('Error en la importación')
                ->body('No se ha seleccionado ningún archivo.')
                ->duration(5000)
                ->send();
            return;
        }

        $key=array_keys($this->bulkImport['file']);
        $name=$this->bulkImport['file'][$key[0]]->getFilename();
        $filePath = storage_path('app/livewire-tmp/' . $name);
        // Verifica que el archivo exista
        if (!file_exists($filePath)) {
            Notification::make()
                ->danger()
                ->title('Error en la importación')
                ->body('El archivo no existe en la ruta especificada.')
                ->duration(5000)
                ->send();
            return;
        }

        // Obtener los usuarios válidos basados en la consulta del supervisor
        $supervisorId = auth()->user()->position_id;
        $validUserIds = User::where('status', true)
            ->whereNotNull('department_id')
            ->whereNotNull('position_id')
            ->whereNotNull('sede_id')
            ->whereHas('position', function ($query) use ($supervisorId) {
                $query->where('supervisor_id', $supervisorId);
            })
            ->pluck('id')->toArray();

        // Procesar el archivo

        $import = new IndicatorProgressImport($validUserIds);

        try {
            Excel::import($import, $filePath,'sedyco_disk');

            // Mostrar resultados
            Notification::make()
                ->success()
                ->title('Importación Realizada')
                ->body('Se importaron ' . $import->getRowCount() . ' registros correctamente.')
                ->duration(5000)
                ->send();

            // Limpiar formulario
            $this->reset('bulkImport');
            $this->dispatch('reset-file');

            // Eliminar el archivo temporal
            if (file_exists($filePath)) {
                unlink($filePath);
            }

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e)  {
            // Captura los errores de validación y muestra los mensajes personalizados
            $errors = $e->validator->errors()->all();

            Notification::make()
                ->danger()
                ->title('Error en la importación')
                ->body('Errores encontrados: <br>' . implode(', ', $errors))
                ->duration(10000)
                ->send();
            // Limpiar formulario
            $this->reset('bulkImport');
            // Eliminar el archivo temporal
            if (file_exists($filePath)) {
                unlink($filePath);
            }

        } catch (\Exception $e) {
            // Captura cualquier otro error

            Notification::make()
                ->danger()
                ->title('Error en la importación')
                ->body('Ocurrió un error durante la importación: ' . $e->getMessage())
                ->duration(10000)
                ->send();
            // Limpiar formulario
            $this->reset('bulkImport');
            // Eliminar el archivo temporal
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }



    }*/
}
