<?php

namespace App\Filament\Pages;

use App\Models\Indicator;
use AWS\CRT\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\OneToOneEvaluation;
use Filament\Forms;
use App\Models\CultureTopic;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use App\Models\Campaign;
use App\Models\Evaluation360Response;
use App\Models\Psychometry;
use App\Models\User;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use mysql_xdevapi\Collection;


class OneToOne extends Page implements HasForms
{
    protected static bool $shouldRegisterNavigation = false;

    use InteractsWithForms;
    public ?array $data = [];
    protected static ?string $navigationIcon = 'heroicon-m-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Face to Face';
    protected static ?string $navigationGroup = 'Evaluaciones';
    protected ?string $heading = 'Ficha de evaluación Face to Face';
    protected ?string $subheading = '';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.face-to-face';

    public $user;
    public $users;
    public $show = false;
    public $userToEvaluated;
    public $evaluation360;
    public $campaignId;
    public $evaluationPotential;
    public $vencimiento;
    public $showEvaluation=false;
    public $themes;
    public $theme;
    public $evaluation, $evaluations;
    public $date_ended;
    public $comments;
    public $existEvaluations;
    public $showResults=false;
    public $indicatorProgresses;
    public $hideCreate=false;
    public $campaignName;
    public $quadrant;
    public $titles = [
        9 => 'Futuro Líder',
        8 => 'Estrella Emergente',
        7 => 'Líder Emergente',
        6 => 'Profesional Experimentado',
        5 => 'Futuro Prometedor',
        4 => 'Enigma',
        3 => 'Desempeño Solido',
        2 => 'Dilema',
        1 => 'Bajo Perfil',
    ];


    protected function getForms(): array
    {
        return [
            'formCultura',
            'formRetroalimentacion',
            'formDesempeno',
            'formDesarrollo',
            'formAsuntos',
        ];
    }
    public static function canView(): bool
    {
        //Este Panel solo lo debe de ver los Jefes de Área y el Administrador
        //Se debe de agregar la comprobación de que estpo se cumpla para que solo sea visible para los Jefes de Área
        if (\auth()->user()->hasRole('Supervisor')
            || \auth()->user()->hasRole('RH')
            || \auth()->user()->hasRole('RH Corp')
            || \auth()->user()->hasRole('Administrador')
            || \auth()->user()->hasRole('Visor'))
        {
            return true;
        }else{
            return false;
        }

    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    public function mount($evaluationId = null)
        //Esto se debe de quitar para que no dependa de una campaña.
    {
        //$this->campaignId = Campaign::whereStatus('Activa')->first();
        //if ($this->campaignId) {
        $this->campaignId = Campaign::whereStatus('Activa')->first()
            ?? Campaign::latest('created_at')->whereStatus('Concluida')->first();
        $this->campaignName=$this->campaignId ? $this->campaignId->name : "No Existe Registro";
        $this->campaignId = $this->campaignId ? $this->campaignId->id : 0;

          //  $this->campaignId = $this->campaignId->id;
            $supervisorId = auth()->user()->position_id;
            $this->themes = collect();
            $this->users = User::where('status', true)
                ->whereNotNull('department_id')
                ->whereNotNull('position_id')
                ->whereNotNull('sede_id')
                ->whereHas('position', function ($query) use ($supervisorId) {
                    $query->where('supervisor_id', $supervisorId);
                })
                ->get();
            $this->evaluation = new OneToOneEvaluation();
       /* }else{
            $this->users = collect();
            $this->evaluation = new OneToOneEvaluation();
        }*/

    }
    public function formCultura(Form $form): Form
    {
        return $form
            ->model($this->evaluation)
            ->schema([
                Repeater::make('cultureTopics')
                    ->relationship() // Relación con el modelo CultureTopic
                    ->schema([
                        TextInput::make('theme')
                            ->label('Tema')
                            ->required(),
                        DatePicker::make('scheduled_date')
                            ->label('Fecha programada')
                            ->minDate(now())
                            ->required(),
                        Textarea::make('comments')
                            ->label('Comentarios')
                            ->rows(3)
                            ->hidden()
                            ->placeholder('Escribe los comentarios aquí...'),
                        Textarea::make('commitments')
                            ->label('Compromisos')
                            ->rows(3)

                            ->placeholder('Escribe los compromisos aquí...'),

                        TextInput::make('progress')
                            ->label('Avance (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                    ])
                    ->label('Temas de Cultura')
                    ->itemLabel(fn (array $state): ?string => $state['theme'] ?? null)
                    ->collapsed()
                    ->columns(2),
            ])

            ->statePath('data');
    }
    public function formDesempeno(Form $form): Form
    {

        return $form
            ->model($this->evaluation)
            ->schema([
                Repeater::make('performanceEvaluations')
                    ->relationship('performanceEvaluations') // Relación con el modelo PerformanceEvaluation
                    ->schema([
                        Textarea::make('comments')
                            ->label('Comentarios')
                            ->placeholder('Ingresa los comentarios, acuerdos y compromisos...')
                            ->required(),
                        Textarea::make('commitments')
                            ->label('Compromisos')
                            ->placeholder('Ingresa los comentarios, acuerdos y compromisos...')
                            ->required(),
                    ])
                    ->label('Comentarios y Compromisos')
                    ->itemLabel('Comentarios y Compromisos')
                    ->collapsed()
                    ->maxItems(1)
                    ->columns(2)
            ])->statePath('data');
              // Guarda los datos en dataDos

    }
    public function formRetroalimentacion(Form $form):Form
    {
        return $form
            ->model($this->evaluation)
            ->schema([
                Repeater::make('performanceFeedback')
                    ->relationship('performanceFeedback') // Relación con el modelo PerformanceFeedback
                    ->schema([
                        Textarea::make('strengths')
                        ->label('Fortalezas Detectadas')
                        ->rows(3)
                        ->placeholder('Describe las fortalezas detectadas...'),
                        Textarea::make('opportunities')
                            ->label('Áreas de Oportunidad')
                            ->rows(3)
                            ->placeholder('Describe las áreas de oportunidad...'),
                    ])
                    ->label('Evaluaciones de Desempeño')
                    ->itemLabel('Comentarios y Compromisos')
                    ->columns(2)
                    ->maxItems(1)
            ])
            ->statePath('data');

    }
    public function formDesarrollo(Form $form): Form
    {
        return $form
            ->model($this->evaluation)
            ->schema([
                Repeater::make('developmentPlans')
                    ->relationship('developmentPlans') // Relación con el modelo DevelopmentPlan
                    ->schema([
                        TextInput::make('development_area')
                            ->label('Área de Desarrollo')
                            ->required(),
                        Select::make('learning_type')
                            ->label('Tipo de Aprendizaje')
                            ->options([
                                'experiential' => '70% Aprendizaje a través de la experiencia ',
                                'social' => '20% Aprendizaje a través de la interacción social - mentoría',
                                'structured' => '10% Aprendizaje a través de la formación formal',
                            ])
                            ->required(),
                        TextInput::make('progress')
                            ->label('Avance (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->required(),
                        DatePicker::make('scheduled_date')
                            ->label('Fecha programada')
                            ->required(),
                    ])
                    ->label('Plan de Desarrollo')
                   // ->itemLabel('Planes de Desarrollo')
                    ->itemLabel(fn (array $state): ?string => $state['development_area'] ?? null)
                    ->collapsed()
                    ->columns(2),
            ])
            ->statePath('data');
    }
    public function formAsuntos(Form $form): Form
    {
        return $form
            ->model($this->evaluation)
            ->schema([
                Repeater::make('miscellaneousTopics')
                    ->relationship('miscellaneousTopics') // Relación con el modelo MiscellaneousTopic
                    ->schema([
                        Select::make('who_says')
                            ->label('Generado por')
                            ->options([
                                'collaborator' => 'Colaborador',
                                'supervisor' => 'Supervisor',
                                'both' => 'Ambos',
                            ])
                            ->required(),
                        TextInput::make('topic')
                            ->label('Asunto')
                            ->required(),
                        Textarea::make('comments')
                            ->label('Comentarios')
                            ->placeholder('Describe los comentarios, acuerdos y compromisos...')
                            ->required(),
                        Textarea::make('follow_up')
                            ->label('Seguimiento')
                            ->placeholder('Describe la situación...')
                            ->visible(function (){
                                return $this->evaluation->initial;
                            })
                            ->required(),

                    ])
                    ->label('Asuntos Varios')
                    ->itemLabel(fn (array $state): ?string => $state['topic'] ?? null)
                    ->collapsed()
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function updatedUser($value): void
    {
        // Reinicializar todas las variables de estado
        $this->reset([
            'showResults',
            'show',
            'showEvaluation',
            'evaluation360',
            'evaluationPotential',
            'quadrant',
            'hideCreate',
        ]);

        if (!$value) {
            $this->reset(['evaluations', 'existEvaluations']);
            return;
        }
        // Cargar datos del usuario
        $this->userToEvaluated = User::find($value);
        $userId=$this->userToEvaluated->id;

        // Cargar TODAS las evaluaciones del año actual
        $this->evaluations = OneToOneEvaluation::where('user_id', $userId)
            ->whereYear('evaluation_date', now()->year)
            ->orderBy('evaluation_date', 'desc')
            ->get();

        // Establecer existEvaluations basado en la colección de evaluaciones
        $this->existEvaluations = $this->evaluations->isNotEmpty();

        // Solo asignar la última evaluación si existe
        if ($this->existEvaluations) {
            $this->evaluation = $this->evaluations->first();
        } else {
            $this->evaluation = new OneToOneEvaluation();
        }
        // Calcular promedios
        $this->evaluation360 = round($this->getAverage360($userId, $this->campaignId), 2);
        $this->evaluationPotential = round($this->getAveragePotential($userId), 2);

        // Calcular niveles y cuadrante
        $performanceLevel = $this->mapScoreToLevel($this->evaluation360);
        $potentialLevel = $this->mapScoreToLevel($this->evaluationPotential);
        $this->quadrant = ($performanceLevel - 1) * 3 + $potentialLevel;

        $this->getIndicatorProgressesForUser($userId);
        // Activar las banderas de visualización
        $this->showResults = true;
        $this->show = true;
    }
    public function getAverage360($user_id, $campaign_id)
    {
        return Evaluation360Response::where('campaign_id', $campaign_id)
            ->where('evaluated_user_id', $user_id)
            ->avg('response');
    }
    public function getAveragePotential($user_id)
    {
        $vencimiento=Psychometry::where('user_id', $user_id)
            ->first();
        $this->vencimiento=$vencimiento->expiration_date??'null';
        // Obtener el promedio de las columnas de la tabla Psychometry
        return Psychometry::select('user_id',DB::raw('
            (SUM(leadership) +
            SUM(communication) +
            SUM(conflict_management) +
            SUM(negotiation) +
            SUM(organization) +
            SUM(problem_analysis) +
            SUM(decision_making) +
            SUM(strategic_thinking) +
            SUM(resilience) +
            SUM(focus_on_results) +
            SUM(teamwork) +
            SUM(willingness_service)) /
            (COUNT(leadership) +
            COUNT(communication) +
            COUNT(conflict_management) +
            COUNT(negotiation) +
            COUNT(organization) +
            COUNT(problem_analysis) +
            COUNT(decision_making) +
            COUNT(strategic_thinking) +
            COUNT(resilience) +
            COUNT(focus_on_results) +
            COUNT(teamwork) +
            COUNT(willingness_service))
            as total_average
        '))
            ->where('user_id', $user_id)
            ->groupBy('user_id')
            ->pluck('total_average','user_id')
            ->first();

    }

    public function getIndicatorProgressesForUser($userId)
    {

        $this->indicatorProgresses = Indicator::where('user_id', $userId)
            ->with('progresses')// Cargar progresos relacionados
            ->get(); // Devuelve los indicadores con progresos

        return $this->indicatorProgresses;
    }
    public function addTheme()
    {
        $this->dispatch('open-modal', id: 'add-theme-modal');

    }
    public function saveTheme()
    {
        $this->themes->push($this->theme);
        $this->theme = '';
        $this->dispatch('close-modal','add-theme-modal');
    }
    public function createOneToOneEvaluation()
    {
        //Rutina para crear la evaluación si es primera evalaución
        //Rutina para actualizar la evaluación si ya existe

        $existingEvaluation = OneToOneEvaluation::where('user_id', $this->userToEvaluated->id)
            ->whereYear('evaluation_date', now()->year)
            ->first();

        if ($existingEvaluation) {
            Notification::make()
                ->warning()
                ->title('Ya existe una evaluación para este colaborador en el año actual.')
                ->send();
            return;
        }
        DB::beginTransaction();

        try {
            // Crea la evaluación
            $this->evaluation = OneToOneEvaluation::create([
                'user_id' => $this->userToEvaluated->id,
                'supervisor_id' => auth()->id(),
                'evaluation_date' => now(),
                'status' => 'in_progress',
                'initial' => false,
                'follow_up' => false,
                'consolidated' => false,
                'final' => false,
            ]);
            DB::commit();

            Notification::make()
                ->success()
                ->title('Se ha creado la Evaluación One to One')
                ->send();


        } catch (QueryException $e) {
            DB::rollBack();
            Notification::make()
                ->danger()
                ->title('Error al crear la evaluación')
                ->body('Ocurrió un error inesperado. Por favor, inténtalo de nuevo.')
                ->send();
            // Registrar el error en los logs
            \Log::info('Error al crear la evaluación One to One: ' . $this->evaluation->id);
            \Log::error('Error al crear la evaluación One to One: ' . $e->getMessage());
            return;
        }
        // Notifica al colaborador
       // $this->userToEvaluated->notify(new OneToOneEvaluationCreated($this->evaluation));



        // Actualiza el estado para mostrar los formularios
        $this->show = false;
        $this->showEvaluation = true;
        $this->existEvaluations=false;
        $this->hideCreate=true;

        \Log::info('evaluation ' . $this->evaluation->id);
        // Llama a mount() para llenar los formularios con la evaluación recién creada
        $this->editEvaluation($this->evaluation->id);


    }

    public function editEvaluation($evaluationId)
    {
        $this->showResults = false;
        $this->showEvaluation = true;

        // Carga la evaluación existente con sus relaciones
        $this->evaluation = OneToOneEvaluation::with([
            'cultureTopics',
            'performanceEvaluations',
            'performanceFeedback',
            'developmentPlans',
            'miscellaneousTopics',
        ])->findOrFail($evaluationId);

        // Asigna los datos a la propiedad $data
        $this->data = [
            'cultureTopics' => $this->evaluation->cultureTopics->toArray(),
            'performanceEvaluations' => $this->evaluation->performanceEvaluations->toArray(),
            'performanceFeedback' => $this->evaluation->performanceFeedback->toArray(),
            'developmentPlans' => $this->evaluation->developmentPlans->toArray(),
            'miscellaneousTopics' => $this->evaluation->miscellaneousTopics->toArray()
        ];
        // Reinicializa los formularios
        $this->formCultura->fill($this->data);
        $this->formDesempeno->fill($this->data);
        $this->formRetroalimentacion->fill($this->data);
        $this->formDesarrollo->fill($this->data);
        $this->formAsuntos->fill($this->data);

    }
    private function mapScoreToLevel($score)
    {
        if ($score >= 4.0 && $score <= 5.0) {
            return 3; // Alto
        } elseif ($score >= 3.0 && $score <= 3.9) {
            return 2; // Medio
        } else {
            return 1; // Bajo
        }
    }

    public function saveEvaluation(): void
    {

        DB::beginTransaction();

        try {
            // Guardar el modelo asociado al formulario
            $this->evaluation->save();

            // Guardar los datos de cada formulario
            $this->formCultura->saveRelationships();
            $this->formDesempeno->saveRelationships();
            $this->formRetroalimentacion->saveRelationships();
            $this->formDesarrollo->saveRelationships();
            $this->formAsuntos->saveRelationships();

            DB::commit();

            // Mostrar una notificación de éxito
            Notification::make()
                ->success()
                ->title('Evaluación guardada correctamente')
                ->send();
        } catch (QueryException $e) {
            DB::rollBack();
            // Mostrar una notificación de error
            Notification::make()
                ->danger()
                ->title('Error al guardar la evaluación')
                ->body('Ocurrió un error inesperado. Por favor, inténtalo de nuevo.')
                ->send();

            // Registrar el error en los logs
            \Log::error('Error al guardar la evaluación One to One: ' . $e->getMessage());
        }
    }
    public function clearResults():void
    {
        $this->reset([
            'showResults',
            'show',
            'showEvaluation',
            'evaluation360',
            'evaluationPotential',
            'quadrant',
            'user',
            'userToEvaluated',
            'evaluation',
            'evaluations',
            'existEvaluations',
        ]);
        $this->mount();
    }
    public function finishEvaluation()
    {
        $this->validate();
        if ($this->evaluation->initial && $this->evaluation->follow_up) {
            $this->evaluation->final = true;
            $this->evaluation->status = 'completed';
            Notification::make()
                ->success()
                ->title('Evaluación finalizada')
                ->send();

        }elseif($this->evaluation->initial){
            $this->evaluation->follow_up=true;
        }elseif(!$this->evaluation->initial){
            $this->evaluation->initial=true;
        }else {
            Notification::make()
                ->warning()
                ->title('No se puede finalizar la evaluación')
                ->body('La evaluación debe estar en estado "Inicial", "Seguimiento" y "Consolidada" para poder finalizarla.')
                ->send();
        }


        DB::beginTransaction();

        try {
            // Guardar el modelo asociado al formulario
            $this->evaluation->save();

            // Guardar los datos de cada formulario
            $this->formCultura->saveRelationships();
            $this->formRetroalimentacion->saveRelationships();
            $this->formDesempeno->saveRelationships();
            $this->formDesarrollo->saveRelationships();
            $this->formAsuntos->saveRelationships();

            DB::commit();

            // Mostrar una notificación de éxito
            Notification::make()
                ->success()
                ->title('Evaluación finalizada correctamente para el usuario ' . $this->userToEvaluated->name)
                ->send();
        } catch (QueryException $e) {

            DB::rollBack();
            // Mostrar una notificación de error
            Notification::make()
                ->danger()
                ->title('Error al guardar la evaluación')
                ->body('Ocurrió un error inesperado. Por favor, inténtalo de nuevo.')
                ->send();

            // Registrar el error en los logs
            \Log::error('Error al guardar la evaluación One to One: ' . $e->getMessage());
        }
    }
    public function generatePdf()
    {
        // Cargar la evaluación con todas sus relaciones
        $evaluation = OneToOneEvaluation::with([
            'user',
            'cultureTopics',
            'performanceEvaluations',
            'performanceFeedback',
            'developmentPlans',
            'miscellaneousTopics'
        ])->findOrFail($this->evaluation->id);
        // Verificar si la evaluación tiene datos


        if (($evaluation->cultureTopics->isEmpty() && $evaluation->performanceEvaluations->isEmpty() && $evaluation->performanceFeedback->isEmpty() && $evaluation->developmentPlans->isEmpty() && $evaluation->miscellaneousTopics->isEmpty())) {

            Notification::make()
                ->danger()
                ->title('No se encontraron datos para generar el PDF')
                ->send();
            return;
        }
        // Generar el PDF
        $pdf = Pdf::loadView('pdf.report-face-to-face', [
            'evaluation' => $evaluation,
            'eva360' => $this->evaluation360,
            'evaPotential' => $this->evaluationPotential,
            'quadrant' => $this->quadrant,
            'interpretation'=>$this->titles[$this->quadrant],
            'indicadores' => $this->indicatorProgresses,
        ]);

        // Descargar el PDF
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, "F2F-{$evaluation->user->name}.pdf");
    }
    public function evaluateProgressRange($progressValue, $ranges)
    {

        // Evaluar si está en rango excelente
        if ($this->isInRange($progressValue, $ranges->expression_excellent, $ranges->excellent_threshold, $ranges->excellent_maximum_value ?? null)) {
            return 'excellent';
        }

        // Evaluar si está en rango satisfactorio
        if ($this->isInRange($progressValue, $ranges->expression_satisfactory, $ranges->satisfactory_threshold, $ranges->satisfactory_maximum_value ?? null)) {
            return 'satisfactory';
        }

        // Evaluar si está en rango insatisfactorio
        if ($this->isInRange($progressValue, $ranges->expression_unsatisfactory, $ranges->unsatisfactory_threshold, $ranges->unsatisfactory_maximum_value ?? null)) {
            return 'unsatisfactory';
        }

        // Si no está en ningún rango, devolver insatisfactorio por defecto
        return 'unsatisfactory';
    }

    /**
     * Determina si un valor está dentro del rango especificado
     *
     * @param float $value Valor a evaluar
     * @param int $expression Tipo de expresión (1-6)
     * @param float $threshold Valor umbral
     * @param float|null $maxValue Valor máximo (para expresión 'Entre')
     * @return bool
     */
    private function isInRange($value, $expression, $threshold, $maxValue = null)
    {
        switch ($expression) {
            case '1': // Mayor que
                return $value > $threshold;
            case '2': // Menor que
                return $value < $threshold;
            case '3': // Igual a
                return $value == $threshold;
            case '4': // Mayor o igual que
                return $value >= $threshold;
            case '5': // Menor o igual que
                return $value <= $threshold;
            case '6': // Entre
                return $value >= $threshold && $value <= $maxValue;
            default:
                return false;
        }
    }


}
