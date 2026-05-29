<?php

namespace App\Filament\Pages;

use App\Models\ActiveSurvey;
use App\Models\EvaluationsTypes;
use App\Models\IdentifiedCollaborator;
use App\Models\Nom035Process;
use App\Models\Question;
use App\Models\RiskFactorSurvey;
use App\Models\RiskFactorSurveyOrganizational;
use Filament\Notifications\Notification;
use Filament\Pages\Page;


class RiskFactorTestOrgEnviroment extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Guía III';
    protected static ?string $title = 'Guía III: Factores de riesgo psicosocial en el entorno organizacional';
    protected static ?string $navigationGroup='NOM-035';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.risk-factor-test-org-enviroment';

    public $page = 'welcome';
    public $questions = [];
    public $currentSection = 1;
    public $existingResponses=false;
    public $totalSections = 0;
    public $sectionQuestions = [];
    public $responses = [];
    public $finishMessage = '';
    public $flagFinish = false;
    public $norma;
    public $section13Preview = null;
    public $section14Preview = null;

    public $itemScoreMappings = [
        'inverse' => [
            1, 4, 23, 24, 25, 26, 27, 28, 30, 31,
            32, 33, 34, 35, 36, 37, 38, 39, 40,
            41, 42, 43, 44, 45, 46, 47, 48, 49,
            50, 51, 52, 53, 55, 56, 57
        ],
        'normal' => [
            2, 3, 5, 6, 7, 8, 9, 10, 11, 12,
            13, 14, 15, 16, 17, 18, 19, 20,
            21, 22, 29, 54, 58, 59, 60, 61, 62, 63, 64, 66,
            67, 68, 69, 71, 72,73,74

        ],
        'yes_no'=> [65,70]
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

    public $sections = [
        1 => ['count' => 5, 'type' => 'likert', 'title' => 'Sección 1',
            'description'=>'Para responder las preguntas siguientes considere las condiciones ambientales de su centro de trabajo.'],
        2 => ['count' => 3, 'type' => 'likert', 'title' => 'Sección 2',
            'description'=>'Para responder a las preguntas siguientes piense en la cantidad y ritmo de trabajo que tiene.' ],
        3 => ['count' => 4, 'type' => 'likert', 'title' => 'Sección 3',
            'description'=>'Las preguntas siguientes están relacionadas con el esfuerzo mental que le exige su trabajo.'],
        4 => ['count' => 4, 'type' => 'likert', 'title' => 'Sección 4',
            'description'=>'Las preguntas siguientes están relacionadas con las actividades que realiza en su trabajo y las
             responsabilidades que tiene.'],
        5 => ['count' => 6, 'type' => 'likert', 'title' => 'Sección 5',
            'description'=>'Las preguntas siguientes están relacionadas con su jornada de trabajo.'],
        6 => ['count' => 6, 'type' => 'likert', 'title' => 'Sección 6',
            'description'=>'Las preguntas siguientes están relacionadas con las decisiones que puede tomar en su trabajo.'],
        7=> ['count' => 2, 'type' => 'likert', 'title' => 'Sección 7',
            'description'=>'Las preguntas siguientes están relacionadas con cualquier tipo de cambio que ocurra en su trabajo
(considere los últimos cambios realizados).'],
        8=> ['count' => 6, 'type' => 'likert', 'title' => 'Sección 8',
            'description'=>'Las preguntas siguientes están relacionadas con la capacitación e información que se le proporciona
            sobre su trabajo.'],
        9 => ['count' => 5, 'type' => 'likert', 'title' => 'Sección 9',
            'description'=>'Las preguntas siguientes están relacionadas con el o los jefes con quien tiene contacto.'],
        10 => ['count' => 5, 'type' => 'likert', 'title' => 'Sección 10',
            'description'=>'Las preguntas siguientes se refieren a las relaciones con sus compañeros.'],
        11=> ['count' => 10, 'type' => 'likert', 'title' => 'Sección 11',
            'description'=>'Las preguntas siguientes están relacionadas con la información que recibe sobre su rendimiento en el trabajo, el reconocimiento, el sentido de pertenencia y la estabilidad que le ofrece su trabajo.'],
        12=> ['count' => 8, 'type' => 'likert', 'title' => 'Sección 12',
            'description'=>'Las preguntas siguientes están relacionadas con actos de violencia laboral (malos tratos, acoso,
             hostigamiento, acoso psicológico).'],
       '13-preview'=> ['count' => 1, 'type' => 'yes_no', 'title' => 'Antes de continuar',
            'description'=>'Las siguientes preguntas están relacionadas con la atención a clientes y usuarios.'
        ],
        13 => ['count' => 4, 'type' => 'likert', 'title' => 'Sección 13',
            'description'=>'Las preguntas siguientes están relacionadas con la atención a clientes y usuarios.'],
        '14-preview' => ['count' => 1, 'type' => 'yes_no', 'title' => 'Antes de continuar',
            'description'=>'Las siguientes preguntas están relacionadas con las actitudes de los trabajadores que supervisa.'
        ],
        14 => ['count' => 4, 'type' => 'likert', 'title' => 'Sección 7',
            'description'=>'Las preguntas siguientes están relacionadas con las actitudes de las personas que supervisa.'],

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
                EvaluationsTypes::where('name', 'like', 'Nom035: Guía III')->first()->id ?? null
            ])
            ->first();

        return $activeSurvey !== null;
    }


    public static function shouldRegisterNavigation(): bool
    {
        // Esto controla la visibilidad en la navegación.
        return static::canView();
    }
    public function mount()
    {
        // Verificar si el usuario ya completó la encuesta
        $this->norma = Nom035Process::findActiveProcess(auth()->user()->sede_id);
        if ($this->norma === null) {
            $this->flagFinish = true;
            $this->finishMessage = 'No hay un proceso activo para esta sede.';
            return;
        }


        // Obtener el tipo de evaluación
        $evaluationType = EvaluationsTypes::where('name', 'like', 'Nom035: Guía III')->first();

        if ($evaluationType) {
            $this->evaluations_type_id = $evaluationType->id;

            // Verificar si el usuario ya respondió
            $existingResponses = RiskFactorSurveyOrganizational::where('user_id', auth()->id())
                ->where('sede_id', auth()->user()->sede_id)
                ->where('norma_id', $this->norma->id)
                ->exists();

            if ($existingResponses) {

                $this->existingResponses = true;
                // Redirigir al dashboard si ya completó la encuesta
                //$this->redirect('/dashboard');
                return;
            } else {
                // Cargar preguntas
                $this->loadQuestions();

                // Iniciar en la primera sección
                $this->currentSection = 1;
            }
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

    /*
     * Métodos para manejar la navegación entre secciones de la encuesta
     */
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
        if ($currentSectionId === '13-preview') {
            // Si responde "Sí" va a sección 13, si responde "No" va a sección 14-preview
            $this->currentSection = ($this->section13Preview === 'yes') ? 13 : '14-preview';
        } elseif ($currentSectionId === '14-preview') {
            // Si responde "No", termina la encuesta
            if ($this->section14Preview === 'no') {
                $this->saveSurvey();
                $this->page = 'finish';
                return;
            } else {
                $this->currentSection = 14;
            }
        } elseif ($currentSectionId === 12) {
            // Después de la sección 5 va a 6-preview
            $this->currentSection = '13-preview';
        }elseif ($currentSectionId===13 ){
            $this->currentSection = '14-preview';
        } elseif ($currentSectionId === 14) {
            // Finaliza la encuesta después de la sección 7 o 6
            $this->saveSurvey();
            $this->page = 'finish';
        } else {
            // Avance normal
            $this->currentSection = $currentSectionId + 1;
        }
    }
    public function previousSection(): void
    {
        if ($this->currentSection === 1) {
            $this->page = 'welcome';
            return;
        }

        // Lógica para retroceder según la estructura condicional
        if ($this->currentSection === '14-preview') {
            // Si está en 8-preview, vuelve a sección 7 o 7-preview dependiendo de la respuesta anterior
            $this->currentSection = ($this->section13Preview === 'yes') ? 13 : '13-preview';
        } elseif ($this->currentSection === 13) {
            // Si está en sección 7, vuelve a 7-preview
            $this->currentSection = '13-preview';
        } elseif ($this->currentSection === 14) {
            // Si está en sección 7, vuelve a 7-preview
            $this->currentSection = '14-preview';
        } else {
            // Si es una sección numerada, simplemente retrocede
            if (is_numeric($this->currentSection)) {
                $this->currentSection--;
            }
        }
    }
    /*
     * Método para validar las respuestas de las secciones de tipo Likert
     */
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
        if ($sectionId === '13-preview') {
            $this->validate([
                'section13Preview' => 'required',
            ], [
                'section13Preview.required' => 'Debes seleccionar una opción',
            ]);
        } elseif ($sectionId === '14-preview') {
            $this->validate([
                'section14Preview' => 'required',
            ], [
                'section14Preview.required' => 'Debes seleccionar una opción',
            ]);
        }
    }
    /*
     * Método para guardar las respuestas de la encuesta
     */
    protected function saveSurvey(): void
    {
        $surveyData = [];
        $now = now();

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
                        'norma_id' => $this->norma->id,
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
        if ($this->section13Preview !== null) {
            // Buscar la pregunta 65 (primera pregunta yes/no)
            $question65 = collect($this->questions)->firstWhere('order', 65);
            if ($question65) {
                $value = $this->section13Preview === 'yes' ? 'Si' : 'No';
                $surveyData[] = [
                    'sede_id' => auth()->user()->sede_id,
                    'user_id' => auth()->id(),
                    'norma_id' => $this->norma->id,
                    'question_id' => $question65['id'],
                    'response_value' => $value,
                    'equivalence_response' => null,
                    'status' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($this->section14Preview !== null) {
            // Buscar la pregunta 45 (segunda pregunta yes/no)
            $question70 = collect($this->questions)->firstWhere('order', 70);
            if ($question70) {
                $value = $this->section14Preview === 'yes' ? 'Si' : 'No';
                $surveyData[] = [
                    'sede_id' => auth()->user()->sede_id,
                    'user_id' => auth()->id(),
                    'norma_id' => $this->norma->id,
                    'question_id' => $question70['id'],
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
            RiskFactorSurveyOrganizational::insert($surveyData);

            $this->finishMessage = 'Gracias por completar la encuesta. Tus respuestas ayudarán a mejorar el clima laboral en tu organización.';

            Notification::make()
                ->success()
                ->title('Encuesta completada')
                ->body('Tus respuestas han sido guardadas correctamente.')
                ->send();
        }

        // Guardar las respuestas en la base de datos


    }
    public function finish(): void
    {
        $this->flagFinish = true;
        $this->redirect('/dashboard');
    }




}
