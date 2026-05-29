<?php

namespace App\Filament\Pages;

use App\Models\ActiveSurvey;
use App\Models\EvaluationsTypes;
use App\Models\RiskFactorSurvey;
use App\Models\Nom035Process;
use App\Models\Question;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class RiskFactorTest extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Guía II';
    protected static ?string $title = 'Guía II: Factores de Riesgo Psicosocial';
    protected static string $view = 'filament.pages.risk-factor-test';
    protected static ?string $navigationGroup='NOM-035';
    protected static ?int $navigationSort = 3;

    public $page = 'welcome';
    public $questions = [];
    public $currentSection = 1;
    public $totalSections = 0;
    public $sectionQuestions = [];
    public $responses = [];
    public $finishMessage = '';
    public $flagFinish = false;
    public $norma_id; //Se le quito el id 1 para que sea dinámico
    public $evaluations_type_id = null;
    public $existingResponses = false;

    // Escala Likert - Mapeo de valores
   // public $currentSection = 1;

    // Variables para las preguntas de tipo SI/NO
    public $section6Preview = null;
    public $section7Preview = null;

    // Mapeo de calificaciones por ítem
    public $itemScoreMappings = [
        'inverse' => [18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33],
        'normal' => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,34,35,36,37,38,39,40,42,43,44,46,47,48],
        'yes_no'=> [41,45]
    ];
    public $sections = [
        1 => ['count' => 9, 'type' => 'likert', 'title' => 'Sección 1',
            'description'=>'Para responder las preguntas siguientes considere las condiciones de su centro
            de trabajo, así como la cantidad y  ritmo  de trabajo.'],
        2 => ['count' => 4, 'type' => 'likert', 'title' => 'Sección 2',
            'description'=>'Las   preguntas   siguientes   están  relacionadas   con  las   actividades
            que  realiza  en   su  trabajo  y   las responsabilidades que tiene.' ],
        3 => ['count' => 4, 'type' => 'likert', 'title' => 'Sección 3',
            'description'=>'Las preguntas siguientes están relacionadas con el tiempo destinado a su trabajo y
            sus responsabilidades familiares.'],
        4 => ['count' => 5, 'type' => 'likert', 'title' => 'Sección 4',
            'description'=>'Las preguntas siguientes están relacionadas con las decisiones que puede
            tomar en su trabajo.'],
        5 => ['count' => 5, 'type' => 'likert', 'title' => 'Sección 5',
            'description'=>'Las  preguntas  siguientes  están  relacionadas  con  la  capacitación  e
            información  que  recibe  sobre  su trabajo.'],
        6 => ['count' => 13, 'type' => 'likert', 'title' => 'Sección 6',
            'description'=>'Las preguntas  siguientes  se refieren a  las relaciones con sus compañeros
            de trabajo y su jefe.'],
        '7-preview' => ['count' => 1, 'type' => 'yes_no', 'title' => 'Antes de continuar',
            'description'=>'Las preguntas siguientes están relacionadas  con la atención a clientes y usuarios.'],
        7 => ['count' => 3, 'type' => 'likert', 'title' => 'Sección 7',
            'description'=>'Las preguntas siguientes están relacionadas  con la atención a clientes y usuarios.'],
        '8-preview' => ['count' => 1, 'type' => 'yes_no', 'title' => 'Antes de continuar',
            'description'=>'Las siguientes preguntas están relacionadas con las actitudes
            de los trabajadores que supervisa.'
            ],
        8 => ['count' => 3, 'type' => 'likert', 'title' => 'Sección 8',
            'description'=>'Las siguientes preguntas están relacionadas  con las actitudes
            de los trabajadores que supervisa.'
        ],
    ];

    public $likertOptionsValues = [
        'normal' => [
            'Siempre' => 4,
            'Casi siempre' => 3,
            'Algunas veces' => 2,
            'Casi nunca' => 1,
            'Nunca' => 0
        ],
        'inverse' => [
            'Siempre' => 0,
            'Casi siempre' => 1,
            'Algunas veces' => 2,
            'Casi nunca' => 3,
            'Nunca' => 4
        ],
        'yes_no' => [
            'yes' => 1,
            'no' => 0
        ]
    ];
    public $likertOptionsDisplay = [
        'Siempre' => 'Siempre',
        'Casi siempre' => 'Casi siempre',
        'Algunas veces' => 'Algunas veces',
        'Casi nunca' => 'Casi nunca',
        'Nunca' => 'Nunca'
    ];

    // Opciones SI/NO
    public $yesNoOptions = [
        'yes' => 'Sí',
        'no' => 'No'
    ];


    public static function canView(): bool
    {
        // Verificar si hay una encuesta activa para el usuario
        $norma = Nom035Process::findActiveProcess(auth()->user()->sede_id);
        // Si no hay un proceso activo, no se puede ver la página
        if ($norma === null) {
            return false;
        }

        // Verificar si el tipo de evaluación está activo
        $activeSurvey = ActiveSurvey::where('norma_id', $norma->id)
            ->whereIn('evaluations_type_id', [
                // Aquí iría el ID del tipo de evaluación que estamos usando
                // Por ejemplo, puedes buscar por nombre:
                EvaluationsTypes::where('name', 'like', 'Nom035: Guía II')->first()->id ?? null
            ])
            ->first();

        return $activeSurvey !== null;
    }



    public static function shouldRegisterNavigation(): bool
    {
        return static::canView();
    }

    public function mount(): void
    {
        // Verificar si el usuario ya completó la encuesta
        $norma = Nom035Process::findActiveProcess(auth()->user()->sede_id);

        if ($norma) {
            $this->norma_id = $norma->id;

            // Obtener el tipo de evaluación
            $evaluationType = EvaluationsTypes::where('name', 'like', 'Nom035: Guía II')->first();
            if ($evaluationType) {
                $this->evaluations_type_id = $evaluationType->id;

                // Verificar si el usuario ya respondió
                $existingResponses = RiskFactorSurvey::where('user_id', auth()->id())
                    ->where('sede_id', auth()->user()->sede_id)
                    ->where('norma_id', $this->norma_id)
                    ->exists();

                if ($existingResponses) {

                    $this->existingResponses = true;
                    // Redirigir al dashboard si ya completó la encuesta
                    //$this->redirect('/dashboard');
                    return;
                }else{
                    // Cargar preguntas
                    $this->loadQuestions();

                    // Iniciar en la primera sección
                    $this->currentSection = 1;
                }


            }
        } else {

            // No hay proceso activo, redirigir al dashboard
            $this->redirect('/dashboard');
        }
    }

    protected function loadQuestions(): void
    {
        // Cargar todas las preguntas desde la base de datos
        $allQuestions = Question::where('evaluations_type_id', $this->evaluations_type_id)
            ->where('status', true)
            ->orderBy('order', 'asc')
            ->get()
            ->toArray();

        // Organizamos las preguntas por sección según nuestra estructura
        $this->questions = [];
        $this->sectionQuestions = [];
        $startIndex = 0;

        foreach ($this->sections as $sectionId => $sectionInfo) {
            $count = $sectionInfo['count'];
            $sectionQuestions = array_slice($allQuestions, $startIndex, $count);
            $this->sectionQuestions[$sectionId] = $sectionQuestions;
            $startIndex += $count;

            // Agregamos todas las preguntas al array general
            foreach ($sectionQuestions as $question) {
                $this->questions[] = $question;
                // Inicializamos las respuestas
                $this->responses[$question['id']] = null;
            }
        }

        $this->totalSections = count($this->sections);
    }

    public function startSurvey(): void
    {
        $this->page = 'survey';
       // $this->currentSection = 0;
    }

    public function nextSection(): void
    {
        $currentSectionId = $this->currentSection;
        $currentSectionType = $this->sections[$currentSectionId]['type'];

        // Validar respuestas según el tipo de sección
        if ($currentSectionType === 'likert') {
            $this->validateLikertResponses($currentSectionId);
        } elseif ($currentSectionType === 'yes_no') {
            $this->validateYesNoResponse($currentSectionId);
        }

        // Lógica de navegación condicional
        if ($currentSectionId === '7-preview') {
            // Si responde "Sí" va a sección 6, si responde "No" va a sección 7-preview
            $this->currentSection = ($this->section6Preview === 'yes') ? 7 : '8-preview';
        } elseif ($currentSectionId === '8-preview') {
            // Si responde "No", termina la encuesta
            if ($this->section7Preview === 'no') {
                $this->saveSurvey();
                $this->page = 'finish';
                return;
            } else {
                $this->currentSection = 8;
            }
        } elseif ($currentSectionId === 6) {
            // Después de la sección 5 va a 6-preview
            $this->currentSection = '7-preview';
        }elseif ($currentSectionId===7 ){
            $this->currentSection = '8-preview';
        } elseif ($currentSectionId === 8) {
            // Finaliza la encuesta después de la sección 7 o 6
            $this->saveSurvey();
            $this->page = 'finish';
        } else {
            // Avance normal
            $this->currentSection = $currentSectionId + 1;
        }
    }

    private function validateLikertResponses($sectionId): void
    {
        $currentQuestions = $this->sectionQuestions[$sectionId] ?? [];
        $validationRules = [];

        foreach ($currentQuestions as $question) {
            $validationRules["responses.{$question['id']}"] = 'required';
        }

        $this->validate($validationRules, [
            'responses.*.required' => 'Todas las preguntas deben ser respondidas',
        ]);
    }

    private function validateYesNoResponse($sectionId): void
    {
        if ($sectionId === '7-preview') {
            $this->validate([
                'section6Preview' => 'required',
            ], [
                'section6Preview.required' => 'Debes seleccionar una opción',
            ]);
        } elseif ($sectionId === '8-preview') {
            $this->validate([
                'section7Preview' => 'required',
            ], [
                'section7Preview.required' => 'Debes seleccionar una opción',
            ]);
        }
    }

    public function previousSection(): void
    {
        if ($this->currentSection === 1) {
            return;
        }

        // Lógica para retroceder según la estructura condicional
        if ($this->currentSection === '8-preview') {
            // Si está en 8-preview, vuelve a sección 7 o 7-preview dependiendo de la respuesta anterior
            $this->currentSection = ($this->section6Preview === 'yes') ? 7 : '7-preview';
        } elseif ($this->currentSection === 7) {
            // Si está en sección 7, vuelve a 7-preview
            $this->currentSection = '7-preview';
        } elseif ($this->currentSection === 8) {
            // Si está en sección 7, vuelve a 7-preview
            $this->currentSection = '8-preview';
        } else {
            // Si es una sección numerada, simplemente retrocede
            if (is_numeric($this->currentSection)) {
                $this->currentSection--;
            }
        }
    }

    protected function saveSurvey(): void
    {
        $surveyData = [];
        $now = now();

        // Procesar respuestas
        foreach ($this->responses as $questionId => $responseValue) {
            if ($responseValue !== null) {
                // Buscar la pregunta en el array de preguntas
                $question = collect($this->questions)->firstWhere('id', $questionId);

                if ($question) {
                    $itemNumber = $question['order'];
                    $equivalenceValue = null;

                    // Determinar el tipo de pregunta y calcular equivalencia
                    if (in_array($itemNumber, $this->itemScoreMappings['inverse'])) {
                        // Para preguntas inversas
                        $equivalenceValue = $responseValue;

                        // Convertir a valor original (0-4)
                        $originalValue = array_search($responseValue, $this->likertOptionsValues['inverse']);
                    } elseif (in_array($itemNumber, $this->itemScoreMappings['normal'])) {
                        // Para preguntas normales
                        $equivalenceValue = $responseValue;

                        // Convertir a valor original (0-4)
                        $originalValue = array_search($responseValue, $this->likertOptionsValues['normal']); // Aquí se asume que el valor ya es el original (0-4)
                    } elseif (in_array($itemNumber, $this->itemScoreMappings['yes_no'])) {
                        // Para preguntas sí/no
                        $equivalenceValue = $responseValue;
                        $originalValue = $responseValue; // En este caso el valor original y la equivalencia son iguales
                    }

                    $surveyData[] = [
                        'sede_id' => auth()->user()->sede_id,
                        'user_id' => auth()->id(),
                        'norma_id' => $this->norma_id,
                        'question_id' => $questionId,
                        'response_value' => $originalValue, // Valor original de la respuesta
                        'equivalence_response' => $equivalenceValue, // Equivalencia calculada
                        'status' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // Agregar también las respuestas de preguntas Sí/No
        if ($this->section6Preview !== null) {
            // Buscar la pregunta 41 (primera pregunta yes/no)
            $question41 = collect($this->questions)->firstWhere('order', 41);
            if ($question41) {
                $value = $this->section6Preview === 'yes' ? 'Si' : 'No';
                $surveyData[] = [
                    'sede_id' => auth()->user()->sede_id,
                    'user_id' => auth()->id(),
                    'norma_id' => $this->norma_id,
                    'question_id' => $question41['id'],
                    'response_value' => $value,
                    'equivalence_response' => null,
                    'status' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($this->section7Preview !== null) {
            // Buscar la pregunta 45 (segunda pregunta yes/no)
            $question45 = collect($this->questions)->firstWhere('order', 45);
            if ($question45) {
                $value = $this->section7Preview === 'yes' ? 'Si' : 'No';
                $surveyData[] = [
                    'sede_id' => auth()->user()->sede_id,
                    'user_id' => auth()->id(),
                    'norma_id' => $this->norma_id,
                    'question_id' => $question45['id'],
                    'response_value' => $value,
                    'equivalence_response' => null,
                    'status' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // Inserción masiva
        if (!empty($surveyData)) {
            RiskFactorSurvey::insert($surveyData);

            $this->finishMessage = 'Gracias por completar la encuesta. Tus respuestas ayudarán a mejorar el clima laboral en tu organización.';

            Notification::make()
                ->success()
                ->title('Encuesta completada')
                ->body('Tus respuestas han sido guardadas correctamente.')
                ->send();
        }
    }

    /**
     * Metodo para finalizar la encuesta y redirigir al dashboard.
     */

    public function finish(): void
    {
        $this->flagFinish = true;
        $this->redirect('/dashboard');
    }
}
