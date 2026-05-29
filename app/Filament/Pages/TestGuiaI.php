<?php

namespace App\Filament\Pages;

use App\Models\ActiveSurvey;
use App\Models\EvaluationsTypes;
use App\Models\IdentifiedCollaborator;
use App\Models\Nom035Process;
use App\Models\Question;
use App\Models\TraumaticEventSurvey;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use function PHPUnit\Framework\assertFalse;

class TestGuiaI extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Guía I';
    protected static ?string $title= 'Guía I: Identificación de factores de riesgo psicosocial';
    protected static string $view = 'filament.pages.test-guia-i';
    protected static ?string $navigationGroup='NOM-035';
    protected static ?int $navigationSort = 2;

    public $page='welcome';
    // Variables para la sección I
    public $s1p1;
    //Variables para la sección II
    public $s2p1,$s2p2;
    //Variables para la sección III
    public $s3p1,$s3p2,$s3p3, $s3p4,$s3p5,$s3p6,$s3p7;
    //Variables para la sección IV
    public $s4p1,$s4p2,$s4p3,$s4p4,$s4p5;
    // Variables determination
    public $reqAtt= false;
    public $finalMessage = '';
    public $existingResponses = false;
    public $flagFinish = false;
    public $norma_id;
    public $sede_id;
    /**
     * Verifica si el usuario puede ver la página.
     *
     * @return bool
     */

    public static function canView(): bool
    {
        $sede_id = auth()->user()->sede_id ?? null;
        $norma = Nom035Process::findActiveProcess($sede_id);

        if($norma !== null) {
            $activeSurvey = ActiveSurvey::where('norma_id', $norma->id)->get();

            // Buscar el ID de forma segura
            $guideIType = EvaluationsTypes::where('name', 'Nom035: Guía I')->first();

            if (!$guideIType) {
                return false; // Si no existe el tipo de evaluación, no mostrar la página
            }

            $existProcess = Nom035Process::where('sede_id', $sede_id)
                ->whereIn('status', ['iniciado', 'en_progreso'])
                ->get();

            if($activeSurvey->contains('evaluations_type_id', $guideIType->id) &&
                $activeSurvey->where('evaluations_type_id', $guideIType->id)->first()->some_users) {

                return $norma->identifiedCollaborators()
                    ->where('sede_id', $sede_id)
                    ->where('user_id', auth()->id())
                    ->where('norma_id', $norma->id)
                    ->where('type_identification','manual')
                    ->exists();

            } elseif($activeSurvey->contains('evaluations_type_id', $guideIType->id) &&
                !IdentifiedCollaborator::where('user_id',auth()->id())
                    ->where('sede_id',$sede_id)
                    ->where('norma_id',$norma->id)
                    ->exists()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    public static function shouldRegisterNavigation(): bool
    {
        // Esto controla la visibilidad en la navegación.
        return static::canView();

    }

    public function mount(): void
    {
        $this->sede_id = auth()->user()->sede_id ?? null;
        $norma=Nom035Process::findActiveProcess($this->sede_id);
        $this->norma_id= $norma->id ?? null;
        if(!$existingSurvey = TraumaticEventSurvey::where('sede_id', $this->sede_id)
            ->where('user_id', auth()->id())
            ->where('norma_id', $this->norma_id)
            ->first()){
            $this->page = 'welcome';
        }else{
            // Si el usuario ya ha sido identificado, redirigir al panel
            //$this->redirect('/dashboard'); //En lugar de redirigir al dashboard, notificamos que ya ha contestado
            Notification::make()
                ->warning()
                ->title('Encuesta ya contestada')
                ->body('Ya has completado esta encuesta anteriormente.')
                ->send();
            $this->existingResponses=true;
            //Se podría mostrar un mensaje de notificación e incluso una pantalla personalizada poniendo
            //que ya ha sido identificado y no puede volver a realizar la encuesta
            //$this->page = 'contestada';

        }

    }

    public function startTest(): void
    {
        $this->page = 'Section I';
    }

    public function navigateSection(string $direction = 'next'): void
    {
        $sections = ['welcome', 'Section I', 'Section II', 'Section III', 'Section IV', 'Finish'];
        $currentIndex = array_search($this->page, $sections);

        if($this->page==='welcome'){
            $this->page = 'Section I';
            return;
        }

        if ($direction === 'next' && $currentIndex < count($sections) - 1) {
            // Validar según la sección actual
            if ($this->page === 'Section I') {
                $this->validate([
                    's1p1' => 'required|in:si,no',
                ]);
            } elseif ($this->page === 'Section II' && $this->s1p1 === 'si') {
                $this->validate([
                    's2p1' => 'required|in:si,no',
                    's2p2' => 'required|in:si,no',
                ]);
                $this->reqAtt = ($this->s2p1 === 'si'?1:0)+
                                ($this->s2p2 === 'si'?1:0)>=1;
            } elseif ($this->page === 'Section III' && $this->s1p1 === 'si') {
                $this->validate([
                    's3p1' => 'required|in:si,no',
                    's3p2' => 'required|in:si,no',
                    's3p3' => 'required|in:si,no',
                    's3p4' => 'required|in:si,no',
                    's3p5' => 'required|in:si,no',
                    's3p6' => 'required|in:si,no',
                    's3p7' => 'required|in:si,no',
                ]);
                /* verificar si se requiere atención
                    para la sección III debe de contestar "sí" a tres o más preguntas
                    de la sección III, si no, no se requiere atención
                */
                $this->reqAtt = (($this->s3p1 === 'si' ? 1 : 0) +
                                ($this->s3p2 === 'si' ? 1 : 0) +
                                ($this->s3p3 === 'si' ? 1 : 0) +
                                ($this->s3p4 === 'si' ? 1 : 0) +
                                ($this->s3p5 === 'si' ? 1 : 0) +
                                ($this->s3p6 === 'si' ? 1 : 0) +
                                ($this->s3p7 === 'si' ? 1 : 0) >= 3)||$this->reqAtt;

            } elseif ($this->page === 'Section IV' && $this->s1p1 === 'si') {
                $this->validate([
                    's4p1' => 'required|in:si,no',
                    's4p2' => 'required|in:si,no',
                    's4p3' => 'required|in:si,no',
                    's4p4' => 'required|in:si,no',
                    's4p5' => 'required|in:si,no',
                ]);

                /* verificar si se requiere atención
                *    para la sección IV debe de contestar "sí" a dos o más preguntas
                *   de la sección IV, si no, no se requiere atención
                */
                $this->reqAtt = (($this->s4p1 === 'si' ? 1 : 0) +
                                ($this->s4p2 === 'si' ? 1 : 0) +
                                ($this->s4p3 === 'si' ? 1 : 0) +
                                ($this->s4p4 === 'si' ? 1 : 0) +
                                ($this->s4p5 === 'si' ? 1 : 0) >= 2)||$this->reqAtt;

                if($this->reqAtt){
                    $this->finalMessage =
                        'Hemos revisado tus respuestas de manera confidencial y, con base en los lineamientos
                         de la Norma,<strong><span class="text-primary-600">se ha identificado que podrías beneficiarte de una
                         evaluación clínica</span></strong> con
                         un especialista (como un psicólogo o profesional de la salud) para brindarte el apoyo
                         necesario. En los próximos días, el equipo de Recursos Humanos de tu sede se pondrá en
                         contacto contigo de forma privada para coordinar esta atención, garantizando siempre tu
                         privacidad y bienestar.';
                }else{
                    $this->finalMessage =
                        'Hemos revisado tus respuestas de manera confidencial y, con base en los lineamientos
                         de la Norma, <strong> no se ha identificado </strong> la necesidad de una evaluación clínica con un
                         especialista. Sin embargo, si en algún momento sientes que necesitas apoyo o tienes
                         inquietudes relacionadas con tu bienestar emocional, te animamos a que te acerques a
                         Recursos Humanos o o llamar a la <strong>línea de atención en crisis</strong> al
                        <a href="tel:8002900024" class="text-primary-600 hover:underline">
                            <strong>800-290-0024</strong>
                        </a>
                        o al
                        <a href="tel:8009112000" class="text-primary-600 hover:underline">
                            <strong>800-911-2000</strong>
                        </a>';
                }
                $this->save();
            }//Termina Else Sección IV

            $this->page = $sections[$currentIndex + 1];

            //Si responde NO en la primera sección, ir directamente al final
            if($this->page==='Section II' && $this->s1p1==='no'){
                $this->page='Finish';
                $this->finalMessage=
                    '<strong>No se han identificado</strong> factores de riesgo. Sin embargo, si en algún momento sientes que necesitas apoyo o tienes
                     inquietudes relacionadas con tu bienestar emocional, recuerda que puedes contactar al equipo de Recursos Humanos o llamar a la <strong>línea de atención en crisis</strong> al
                        <a href="tel:8002900024" class="text-primary-600 hover:underline">
                            <strong>800-290-0024</strong>
                        </a>
                        o al
                        <a href="tel:8009112000" class="text-primary-600 hover:underline">
                            <strong>800-911-2000</strong>
                        </a>';
                $this->save();
            }
        } elseif ($direction === 'previous' && $currentIndex > 0) {
            $this->page = $sections[$currentIndex - 1];
        }
    }

    public function finish(): void
    {
        $this->flagFinish = true;
        // Aquí puedes guardar todas las respuestas en la base de datos
        Notification::make()
            ->success()
            ->title('Cuestionario completado')
            ->body('Gracias por completar el cuestionario')
            ->send();

        $this->redirect('/dashboard');
    }

    // Define los mensajes de error correctamente
    protected function messages(): array
    {
        return [
            's1p1.required' => 'La respuesta de la pregunta 1 de la sección I es obligatoria.',
            's1p1.in' => 'La respuesta debe ser Sí o No.',
            's2p1.required' => 'La respuesta de la pregunta 1 de la sección II es obligatoria.',
            's2p2.required' => 'La respuesta de la pregunta 2 de la sección II es obligatoria.',
            's3p1.required' => 'La respuesta de la pregunta 1 de la sección III es obligatoria.',
            's3p2.required' => 'La respuesta de la pregunta 2 de la sección III es obligatoria.',
            's3p3.required' => 'La respuesta de la pregunta 3 de la sección III es obligatoria.',
            's3p4.required' => 'La respuesta de la pregunta 4 de la sección III es obligatoria.',
            's3p5.required' => 'La respuesta de la pregunta 5 de la sección III es obligatoria.',
            's3p6.required' => 'La respuesta de la pregunta 6 de la sección III es obligatoria.',
            's3p7.required' => 'La respuesta de la pregunta 7 de la sección III es obligatoria.',
            's4p1.required' => 'La respuesta de la pregunta 1 de la sección IV es obligatoria.',
            's4p2.required' => 'La respuesta de la pregunta 2 de la sección IV es obligatoria.',
            's4p3.required' => 'La respuesta de la pregunta 3 de la sección IV es obligatoria.',
            's4p4.required' => 'La respuesta de la pregunta 4 de la sección IV es obligatoria.',
            's4p5.required' => 'La respuesta de la pregunta 5 de la sección IV es obligatoria.',
        ];
    }

    public function save(): void
    {
        //No es necesario validad aquí, ya que las validaciones se realizan en el metodo navigateSection
        //Verificamos que el usuario esté autenticado y que no haya respuestas previas para esta sede y usuario

        if (!auth()->check()) {
            Notification::make()
                ->danger()
                ->title('Error de autenticación')
                ->body('Debes iniciar sesión para guardar tus respuestas.')
                ->send();
            return;
        }
        // Verificar si ya existe una respuesta para el usuario y la sede
        $existingSurvey = TraumaticEventSurvey::where('sede_id', $this->sede_id)
            ->where('user_id', auth()->id())
            ->where('norma_id', $this->norma_id) // Asumiendo que el ID de la norma es 1
            ->first();
        // Si ya existe una respuesta, no permitir guardar nuevamente
        if ($existingSurvey){
            Notification::make()
                ->warning()
                ->title('Respuestas ya guardadas')
                ->body('Ya has completado esta encuesta anteriormente.')
                ->send();
            return;
        }
        $responses = [
            's1p1' => $this->s1p1,
            's2p1' => $this->s2p1,
            's2p2' => $this->s2p2,
            's3p1' => $this->s3p1,
            's3p2' => $this->s3p2,
            's3p3' => $this->s3p3,
            's3p4' => $this->s3p4,
            's3p5' => $this->s3p5,
            's3p6' => $this->s3p6,
            's3p7' => $this->s3p7,
            's4p1' => $this->s4p1,
            's4p2' => $this->s4p2,
            's4p3' => $this->s4p3,
            's4p4' => $this->s4p4,
            's4p5' => $this->s4p5,
        ];

// Obtener todas las preguntas del tipo de evaluación "Nom035: Guía I"
        $questions = Question::whereHas('evaluationType', function ($query) {
            $query->where('name', 'like', 'Nom035: Guía I');
        })->get();

// Mapear las preguntas con posiciones específicas
        $questionMap = [];
        // Mapeo correcto según la estructura real de preguntas por sección
        $sectionQuestionCount = [
            1 => 1,  // Sección 1: 1 pregunta
            2 => 2,  // Sección 2: 2 preguntas
            3 => 7,  // Sección 3: 7 preguntas
            4 => 5,  // Sección 4: 5 preguntas
        ];

        $counter = 0;
        $section = 1;

        foreach ($questions as $question) {
            // Si ya procesamos todas las preguntas de esta sección, avanzamos a la siguiente
            if ($counter >= $sectionQuestionCount[$section]) {
                $section++;
                $counter = 0;
                // Si llegamos a una sección que no existe, salimos del bucle
                if (!isset($sectionQuestionCount[$section])) {
                    break;
                }
            }

            $position = $counter + 1; // La posición dentro de la sección
            $key = "s{$section}p{$position}";
            $questionMap[$key] = $question->id;
            $counter++;
        }

// Preparar datos para inserción masiva
        $surveyData = [];
        $now = now();

        foreach ($responses as $key => $response) {
            // Solo procesar si hay una respuesta y existe un mapeo para la clave
            if ($response && isset($questionMap[$key])) {
                $surveyData[] = [
                    'sede_id' => $this->sede_id,
                    'user_id' => auth()->id(),
                    'norma_id' => $this->norma_id, // ID de la norma
                    'question_id' => $questionMap[$key],
                    'response' => $response,
                    'status' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        // Inserción masiva si hay datos para guardar
        if (!empty($surveyData)) {
            TraumaticEventSurvey::insert($surveyData);
        }

        //Verificamos que el usuario se encuentra identificado en la sede previamente.
        // Buscamos si existe una identificación previa del colaborador
        $userIdentified = IdentifiedCollaborator::where([
            'user_id' => auth()->id(),
            'sede_id' => $this->sede_id,
            'norma_id' => $this->norma_id,
        ])->first();

        // Actuamos según si el usuario requiere atención y si ya existe un registro
        if ($this->reqAtt) {
            // Si requiere atención, creamos o actualizamos el registro
            IdentifiedCollaborator::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'sede_id' => $this->sede_id,
                    'norma_id' => $this->norma_id,
                ],
                [
                    'type_identification' => 'encuesta',
                    'identified_by' => auth()->id(),
                    'identified_at' => now(),
                ]
            );
        } elseif ($userIdentified) {
            // Si no requiere atención pero existe un registro, lo eliminamos
            $userIdentified->delete();
        }
        // Aquí puedes implementar la lógica para guardar las respuestas en la base de datos
        // Por ejemplo, crear un registro en una tabla específica o enviar las respuestas a un servicio externo
        Notification::make()
            ->success()
            ->title('Respuestas guardadas')
            ->body('Tus respuestas han sido guardadas correctamente.')
            ->send();
    }
}
