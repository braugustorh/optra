<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class DocumentsLibraryWidget extends Widget
{
    protected static string $view = 'filament.widgets.documents-library';

    protected static ?string $heading = 'Biblioteca de Documentos';

    protected static ?int $sort = 5; // Posición del widget en el dashboard

    // Hacer el widget visible solo para usuarios con roles RH y RH Corp
    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['RH', 'RH Corp']);
    }
    // Obtener datos para el widget (ejemplo)
    public function getViewData(): array
    {
        // Aquí puedes agregar la lógica para obtener documentos
        // Por ejemplo, desde una tabla de documentos o archivos
        return [
            'totalDocuments' => 1, // Ejemplo
            'recentDocuments' => [
                ['name' => 'Protocolo para prevenir, atender y erradicar la violencia laboral',
                    'path' => 'protocolo_violencia.pdf',
                    'description' => 'Este es un protocolo para prevenir y erradicar la violencia en tu centro de trabajo.',
                    'type' => 'PDF',
                    'size' => '1.2 MB'],

            ],
            'categories' => [
                'Políticas' => 1,
                'Formatos' => 0,
               // 'Manuales' => 5,
            ]
        ];
    }
    public function downloadDocument(string $name){
        $storagePath = storage_path('app/documents/' . $name);

        if (file_exists($storagePath)) {
            if ($name === 'protocolo_violencia.pdf'){
                try {
                    // 1. Generar las 3 variables
                    $mes = strtoupper(\Carbon\Carbon::now()->locale('es')->isoFormat('MMMM')); // mes en mayúsculas
                    $anio = now()->format('Y'); // año a 4 dígitos
                    $user = auth()->user();
                    if ($user->sede_id===3){
                        $sede= "ADMINISTRADORA DE CENTRALES Y TERMINALES";
                    }else{
                        $sede = auth()->user()->sede?->company_name ?? 'Sin Razón Social';
                    }
                    // 2. Cargar la plantilla
                    $templatePath = storage_path('app/plantillas/protocolo-optra-CT.docx');

                    if (!file_exists($templatePath)) {
                        throw new \Exception('No se encontró la plantilla del protocolo');
                    }

                    $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

                    // 3. Insertar las variables en el documento
                    $templateProcessor->setValue('month', $mes);
                    $templateProcessor->setValue('year', $anio);
                    $templateProcessor->setValue('sedeName', $sede);

                    // 4. Guardar Word temporal
                    $nombreArchivoSalida = 'protocolo_' . time() . '.docx';
                    $tempWordPath = storage_path('app/livewire-tmp/' . $nombreArchivoSalida);

                    if (!is_dir(storage_path('app/livewire-tmp'))) {
                        mkdir(storage_path('app/livewire-tmp'), 0755, true);
                    }

                    $templateProcessor->saveAs($tempWordPath);

                    // 5. Convertir a PDF con iLovePDF
                    $ilovepdf = new \Ilovepdf\Ilovepdf('project_public_e77e6c5a886b8b19b9e83808f514bf44_uChwi13485de38f75fad79190646cb3fbacfe',
                        'secret_key_5ad6baf9deee4bbf2dee704afa6502d5_Lau4ia401a9d22bc6a6715f4c39ec19fd6dd1');

                    $task = $ilovepdf->newTask('officepdf');
                    $task->addFile($tempWordPath);
                    $task->execute();

                    $outputDir = storage_path('app/livewire-tmp');
                    $task->download($outputDir);

                    // 6. Construir ruta del PDF generado
                    $nombrePdf = str_replace('.docx', '.pdf', $nombreArchivoSalida);
                    $rutaPdf = $outputDir . '/' . $nombrePdf;

                    // 7. Limpiar el DOCX temporal
                    if (file_exists($tempWordPath)) {
                        @unlink($tempWordPath);
                    }

                    if (!file_exists($rutaPdf)) {
                        throw new \Exception('El archivo PDF no se generó correctamente');
                    }

                    return response()->download($rutaPdf, 'Protocolo_Violencia_Laboral.pdf')->deleteFileAfterSend();

                } catch (\Exception $e) {
                    \Filament\Notifications\Notification::make()
                        ->title('Error al generar protocolo')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }

            }else{
                return response()->download($storagePath, $name);
            }
        }else{
            return response()->json(['error' => 'Documento no encontrado'], 404);
        }
    }

}
