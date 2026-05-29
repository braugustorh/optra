<?php

namespace App\Filament\Pages;

use App\Models\ActiveSurvey;
use App\Models\Campaign;
use App\Models\Evaluation;
use App\Models\EvaluationsTypes;
use App\Models\IdentifiedCollaborator;
use App\Models\Nom035Process;
use App\Models\RiskFactorSurvey;
use App\Models\RiskFactorSurveyOrganizational;
use App\Models\TraumaticEvent;
use App\Models\TraumaticEventSurvey;
use App\Models\User;
use Barryvdh\DomPDF\PDF;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Settings as PhpWordSettings;
use PhpOffice\PhpWord\Shared\ZipArchive;
use PhpOffice\PhpWord\Writer\PDF\DomPDF;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\PhpPresentation;
use Ilovepdf\Ilovepdf;


class Nom035 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title= '';
    protected static ?string $navigationGroup = 'NOM-035';
    protected static ?string $navigationLabel= 'Panel de Control';
    protected static string $view = 'filament.pages.nom35';
    protected static ?int $navigationSort = 1;

    // Propiedades existentes
    public $selected_sede_id = null;
    public $sedes_monitor = [];
    public $stage = 'welcome';
    public $colabs = [];
    public $muestra;
    // Propiedades para el modal de identificación
    public $selectedCollaborator = null;
    public $identifiedColaborators = [];
    public $colaborators = [];
    public $availableColaborators = [];
    // Propiedades para el modal de identificación
    public $selectedEventType = null;
    public $eventDescription = '';
    public $eventDate = null;
    public $colabResponsesG1 = 0;
    public $colabResponsesG2 = 0;
    public $colabResponsesG3 = 0;
    public $norma;
    public $calificacion;
    public $resultCuestionario;
    public Int $level = 0; //Nivel de encuesta depende de la cantidad de colaboradores
    public $activeGuideI = false, $activeGuideII = false, $activeGuideIII = false;
    public $eventTypesByCategory = [];
    public $muestraGuideIII = 0;
    public $responsesTotalG2;
    public $generalResults = [];
    public $generalResultsCategory = [];
    public $domainResults = [];
    public $generalResultsGuideIII = [];
    public $resultCuestionarioG3;
    public $calificacionG3;
    public $totalResponsesG3 = 0;
    public $generalResultsGuideIIICategory=[];
    public $generalDomainResultsGuideIII= [];
    public $fechaInicioG2, $fechaFinG2;
    //Variables cover para categorias
    public $coverAmbientResponses, $coverLeadershipResponses, $coverActivityResponses, $coverTimeResponses,$coverEntornoResponses;
    //Variables cover para dominio
    public $coverWorkActivityResponses, $coverWorkControlResponses, $coverConditionResponses,
        $coverWorkJourneyResponses, $coverWordAndFamilyResponses, $coverWorkRelationsResponses,
        $coverViolenceResponses,$coverDomainLeadershipResponses,$coverInestableResponses,$coverPerformanceResponses;

    public $dataGeneral;

    public $categories=[
      'Ambiente de trabajo'=> [2,1,3],
      'Factores propios de la actividad ' =>[4,9,5,6,7,8,41,42,43,10,11,12,13,20,21,22,18,19,26,27],

    ];



    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Administrador', 'RH Corp']);
    }
    public static function shouldRegisterNavigation(): bool
    {
        // Esto controla la visibilidad en la navegación.
        return static::canView();

    }
    public function getCurrentSedeId()
    {
        if (auth()->check() && auth()->user()->hasRole('RH Corp')) {
            return session('selected_sede_id');
        }
        $user = auth()->user();
        return $user ? $user->getAttribute('sede_id') : null;
    }

    public function selectSede($id)
    {
        session(['selected_sede_id' => $id]);
        return redirect()->to(request()->header('Referer'));
    }

    public function clearSelectedSede()
    {
        session()->forget('selected_sede_id');
        return redirect()->to(request()->header('Referer'));
    }

    public function loadMonitorData()
    {
        $this->sedes_monitor = \App\Models\Sede::with('razonSocials')->get()->map(function($sede) {
            $process = Nom035Process::where('sede_id', $sede->id)
                ->orderBy('created_at', 'desc')
                ->first();

            // Cálculo básico de avance para el monitor
            $totalColabs = User::where('sede_id', $sede->id)->where('status', true)->count();
            $responses = 0;
            if ($process) {
                $responses = TraumaticEventSurvey::where('norma_id', $process->id)->distinct('user_id')->count();
            }

            return [
                'id' => $sede->id,
                'name' => $sede->name,
                'location' => $sede->city . ($sede->state ? ', ' . $sede->state : ''),
                'status' => $process ? $process->status : 'Sin activar',
                'progress' => $totalColabs > 0 ? round(($responses / $totalColabs) * 100) : 0,
                'total_colabs' => $totalColabs,
                'responses' => $responses,
                'razones_sociales' => $sede->razonSocials->pluck('name')->toArray(),
            ];
        })->toArray();
    }

    // Inicializar datos
    public $current_sede_name = '';

    public function mount()
    {
        $this->selected_sede_id = $this->getCurrentSedeId();

        if (auth()->user()->hasRole('RH Corp') && !$this->selected_sede_id) {
            $this->stage = 'monitor';
            $this->loadMonitorData();
            return;
        }

        $effectiveSedeId = $this->selected_sede_id ?? $this->getCurrentSedeId();
        $currentSede = \App\Models\Sede::find($effectiveSedeId);
        $this->current_sede_name = $currentSede ? $currentSede->name : 'No definido';

        $this->norma=Nom035Process::findActiveProcess($effectiveSedeId);
        Log::info('Mounting NOM-035 page for user ID: ' . auth()->id() . ' with effective sede ID: ' . $effectiveSedeId);
        Log::info($this->norma);

        // Cargar colaboradores de la sede actual
        if($this->norma !== null){
            Log::info('Norma Count'.$this->norma->identifiedCollaboratorsCount()??0);
            // Si ya existe un proceso activo, redirigir al panel
            $activeSurvey = ActiveSurvey::where('norma_id', $this->norma->id)->get();
/*
            $guideTypes = EvaluationsTypes::where('name', 'like', 'Nom035: Guía %')
                ->pluck('id', 'name');



            $this->activeGuideI = $activeSurvey->contains('evaluations_type_id', $guideTypes['Nom035: Guía I'] ?? 0);
            $this->activeGuideII = $activeSurvey->contains('evaluations_type_id', $guideTypes['Nom035: Guía II'] ?? 0);
            $this->activeGuideIII = $activeSurvey->contains('evaluations_type_id', $guideTypes['Nom035: Guía III'] ?? 0);
           */
            $this->colabResponsesG1= TraumaticEventSurvey::where('norma_id', $this->norma->id)
                ->where('sede_id', $this->norma->sede_id)
                ->distinct('user_id')
                ->count();
            // Debug: verificar qué registros existen
            $guideTypes = EvaluationsTypes::where('name', 'like', 'Nom035: Guía %')
                ->pluck('id', 'name');

            // Temporal: ver qué claves tienes disponibles
            \Log::info('Available guide types:', $guideTypes->toArray());

            // Usar get() en lugar de acceso directo al array
            $guideIId = EvaluationsTypes::where('name', 'Nom035: Guía I')->first()?->id;
            $guideIIId = EvaluationsTypes::where('name', 'Nom035: Guía II')->first()?->id;
            $guideIIIId = EvaluationsTypes::where('name', 'Nom035: Guía III')->first()?->id;

            $this->activeGuideI = $activeSurvey->contains('evaluations_type_id', $guideIId);
            $this->activeGuideII = $activeSurvey->contains('evaluations_type_id', $guideIIId);
            $this->activeGuideIII = $activeSurvey->contains('evaluations_type_id', $guideIIIId);


            $this->stage = 'panel';
            $queryResG2=RiskFactorSurvey::where('norma_id', $this->norma->id)
                        ->where('sede_id', $this->norma->sede_id);
            $calificacion=$queryResG2->sum('equivalence_response');
            $this->responsesTotalG2=$queryResG2->distinct('user_id')->count('user_id');
            $this->calificacion = $this->responsesTotalG2 > 0 ? $calificacion / $this->responsesTotalG2 : 0;

            $this->loadIdentifiedEvents();
        }else{
            $this->norma=collect();
        }

        $this->colabs =User::where('sede_id', $this->getCurrentSedeId() ?? null)
            ->where('status', true)
            ->get();
        $this->colaborators = $this->colabs;
        $this->availableColaborators = $this->colaborators;

        // Calcular muestra según el número de colaboradores
        if (count($this->colabs) >= 51) {
            $this->muestra = $this->calculateSampleSize(count($this->colabs));
            $this->level=3; //Guía III
        }else if (count($this->colabs)>=16 && count($this->colabs)<=50) {
            $this->muestra =count($this->colabs);
            $this->level=2; //Guía II
        } else {
            $this->muestra = count($this->colabs);
            $this->level=1; //Guía I
        }

        $this->selectedCollaborator = null;
        $this->selectedEventType = null;
        $this->eventDate = now()->format('Y-m-d');
        $this->eventTypesByCategory = \App\Enums\TraumaticEventType::getByCategory();
    }

    // Metodo para crear registro (existente)
    public function createRecord()
    {
        // Simular carga
        $saveProcess= new Nom035Process();
        $saveProcess->sede_id = $this->getCurrentSedeId();
        $saveProcess->hr_manager_id = auth()->id();
        $saveProcess->start_date = now();
        $saveProcess->status = 'iniciado';
        $saveProcess->total_employees = count($this->colabs);
        $saveProcess->survey_applicable = count($this->colabs)>=16;
        $saveProcess->save();
        $this->norma = $saveProcess;
        // Notificación de éxito
        Notification::make()
            ->title('Proceso NOM-035 iniciado')
            ->body('El proceso ha sido creado exitosamente.')
            ->success()
            ->send();
        $this->stage = 'panel';
    }

    // Metodo para abrir el modal de identificación
    public function openIdentificationModal()
    {

        $this->resetIdentificationModal();
        $this->dispatch('open-modal', id: 'identify-modal');
    }

    // Metodo para cerrar el modal de identificación
    public function closeIdentificationModal()
    {
        $this->dispatch('close-modal', id: 'identify-modal');
        $this->resetIdentificationModal();
    }

    public function openModalResults()
    {
        if($this->calificacion>=90){
            $this->resultCuestionario='Muy Alto';
        }elseif ($this->calificacion>=70 && $this->calificacion<90 ) {
            $this->resultCuestionario='Alto';
        }elseif ($this->calificacion>=45 && $this->calificacion<70) {
            $this->resultCuestionario='Medio';
        }elseif ($this->calificacion>=20 && $this->calificacion<45) {
            $this->resultCuestionario='Bajo';
        }elseif ($this->calificacion<20) {
            $this->resultCuestionario='Despreciable';
        }


        $responses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });

        //Asigna el rango del resultado
        $this->generalResults = [
            'total' => $responses->count(),
            'null' => $responses->filter(fn($value) => $value < 20)->count(),
            'low' => $responses->filter(fn($value) => $value >= 20 && $value < 45)->count(),
            'medium' => $responses->filter(fn($value) => $value >= 45 && $value < 70)->count(),
            'high' => $responses->filter(fn($value) => $value >= 70 && $value < 90)->count(),
            'very_high' => $responses->filter(fn($value) => $value >= 90)->count(),
        ];
        /*
         * Asignación de los resultados generales por categoría
         */

        $ambientResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [1, 2, 3]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverAmbientResponses = $ambientResponses;
        $activityResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 18, 19,20,21,22,26,27,42,43,44]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverActivityResponses = $activityResponses;
        $timeResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [14,15,16,17]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverTimeResponses = $timeResponses;
        $leadershipResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [23,24,25,28,29,30,31,32,33,34,35,36,37,38,39,40,46,47,48]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverLeadershipResponses = $leadershipResponses;
        //Se revisó la asignación de los resultados vs  excel; y salio bien la asignación
        $this->generalResultsCategory=[
            'ambiente'=>[
                'nombre' => 'Ambiente de trabajo',
                'total' => $ambientResponses->count(),
                'null' => $ambientResponses->filter(fn($value) => $value < 3)->count(),
                'low' => $ambientResponses->filter(fn($value) => $value >= 3 && $value < 5)->count(),
                'medium' => $ambientResponses->filter(fn($value) => $value >= 5 && $value < 7)->count(),
                'high' => $ambientResponses->filter(fn($value) => $value >= 7 && $value < 9)->count(),
                'very_high' => $ambientResponses->filter(fn($value) => $value >= 9)->count(),
            ],
            'actividad'=>[
                'nombre' => 'Factores propios de la actividad',
                'total' => $activityResponses->count(),
                'null' => $activityResponses->filter(fn($value) => $value < 10)->count(),
                'low' => $activityResponses->filter(fn($value) => $value >= 10 && $value < 20)->count(),
                'medium' => $activityResponses->filter(fn($value) => $value >= 20 && $value < 30)->count(),
                'high' => $activityResponses->filter(fn($value) => $value >= 30 && $value < 40)->count(),
                'very_high' => $activityResponses->filter(fn($value) => $value >= 40)->count(),
            ],
            'tiempo'=>[
                'nombre' => 'Organización del tiempo de trabajo',
                'total' => $timeResponses->count(),
                'null' => $timeResponses->filter(fn($value) => $value < 4)->count(),
                'low' => $timeResponses->filter(fn($value) => $value >= 4 && $value < 6)->count(),
                'medium' => $timeResponses->filter(fn($value) => $value >= 6 && $value < 9)->count(),
                'high' => $timeResponses->filter(fn($value) => $value >= 9 && $value < 12)->count(),
                'very_high' => $timeResponses->filter(fn($value) => $value >= 12)->count(),
            ],
            'liderazgo'=>[
                'nombre' => 'Liderazgo y relaciones en el trabajo',
                'total' => $leadershipResponses->count(),
                'null' => $leadershipResponses->filter(fn($value) => $value < 10)->count(),
                'low' => $leadershipResponses->filter(fn($value) => $value >= 10 && $value < 18)->count(),
                'medium' => $leadershipResponses->filter(fn($value) => $value >= 18 && $value < 28)->count(),
                'high' => $leadershipResponses->filter(fn($value) => $value >= 28 && $value < 38)->count(),
                'very_high' => $leadershipResponses->filter(fn($value) => $value >= 38)->count(),
            ]
        ];
        /*
         ******** SECCIÓN DE DOMINIO
         */
        $conditionResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [1, 2, 3]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverConditionResponses = $conditionResponses;
        $workActivityResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [4, 5, 6, 7, 8, 9, 10, 11, 12, 13,42,43,44]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverWorkActivityResponses = $workActivityResponses;
        $workControlResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [20,21,22,18,19,26,27]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverWorkControlResponses = $workControlResponses;

        $workJourneyResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [14,15]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverWorkJourneyResponses = $workJourneyResponses;

        $wordAndFamilyResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [16,17]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverWordAndFamilyResponses = $wordAndFamilyResponses;
        $leadershipResponses= RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [23,24,25,28,29]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverDomainLeadershipResponses = $leadershipResponses;
        $workRelationsResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [30,31,32,46,47,48]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverWorkRelationsResponses = $workRelationsResponses;
        $violenceResponses = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [33,34,35,36,37,38,39,40]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverViolenceResponses = $violenceResponses;
        // Asignar los resultados de dominio en un array
        $this->domainResults=[
            'conditions'=>[
                'nombre' => 'Condiciones del entorno de trabajo',
                'total' => $conditionResponses->count(),
                'total_cali'=>$conditionResponses->avg(),
                'riesgo'=> $this->getDomainRiskLevel('conditions',$conditionResponses->avg()),
                'null' => $conditionResponses->filter(fn($value) => $value < 3)->count(),
                'low' => $conditionResponses->filter(fn($value) => $value >= 3 && $value < 5)->count(),
                'medium' => $conditionResponses->filter(fn($value) => $value >= 5 && $value < 7)->count(),
                'high' => $conditionResponses->filter(fn($value) => $value >= 7 && $value < 9)->count(),
                'very_high' => $conditionResponses->filter(fn($value) => $value >= 9)->count(),
            ],
            'work_activity'=>[
                'nombre' => 'Carga de Trabajo',
                'total' => $workActivityResponses->count(),
                'total_cali'=>$workActivityResponses->avg(),
                'riesgo'=> $this->getDomainRiskLevel('work_activity',$workActivityResponses->avg()),
                'null' => $workActivityResponses->filter(fn($value) => $value < 12)->count(),
                'low' => $workActivityResponses->filter(fn($value) => $value >= 12 && $value < 16)->count(),
                'medium' => $workActivityResponses->filter(fn($value) => $value >= 16 && $value < 20)->count(),
                'high' => $workActivityResponses->filter(fn($value) => $value >= 20 && $value < 24)->count(),
                'very_high' => $workActivityResponses->filter(fn($value) => $value >= 24)->count(),
            ],
            'work_control'=>[
                'nombre' => 'Control del trabajo',
                'total' => $workControlResponses->count(),
                'total_cali'=>$workControlResponses->avg(),
                'riesgo'=> $this->getDomainRiskLevel('work_control',$workControlResponses->avg()),
                'null' => $workControlResponses->filter(fn($value) => $value < 5)->count(),
                'low' => $workControlResponses->filter(fn($value) => $value >= 5 && $value < 8)->count(),
                'medium' => $workControlResponses->filter(fn($value) => $value >= 8 && $value < 11)->count(),
                'high' => $workControlResponses->filter(fn($value) => $value >= 11 && $value < 14)->count(),
                'very_high' => $workControlResponses->filter(fn($value) => $value >= 14)->count(),
            ],
            'work_journey'=>[
                'nombre' => 'Organización del tiempo de trabajo',
                'total' => $workJourneyResponses->count(),
                'total_cali'=>$workJourneyResponses->avg(),
                'riesgo'=> $this->getDomainRiskLevel('work_journey',$workJourneyResponses->avg()),
                'null' => $workJourneyResponses->filter(fn($value) => $value < 1)->count(),
                'low' => $workJourneyResponses->filter(fn($value) => $value >= 1 && $value < 2)->count(),
                'medium' => $workJourneyResponses->filter(fn($value) => $value >= 2 && $value < 4)->count(),
                'high' => $workJourneyResponses->filter(fn($value) => $value >= 4 && $value < 6)->count(),
                'very_high' => $workJourneyResponses->filter(fn($value) => $value >= 6)->count(),
            ],
            'work_family'=>[
                'nombre' => 'Interferencia trabajo-familia',
                'total' => $wordAndFamilyResponses->count(),
                'total_cali'=>$wordAndFamilyResponses->avg(),
                'riesgo'=> $this->getDomainRiskLevel('work_family',$wordAndFamilyResponses->avg()),
                'null' => $wordAndFamilyResponses->filter(fn($value) => $value < 1)->count(),
                'low' => $wordAndFamilyResponses->filter(fn($value) => $value >= 1 && $value < 2)->count(),
                'medium' => $wordAndFamilyResponses->filter(fn($value) => $value >= 2 && $value < 4)->count(),
                'high' => $wordAndFamilyResponses->filter(fn($value) => $value >= 4 && $value < 6)->count(),
                'very_high' => $wordAndFamilyResponses->filter(fn($value) => $value >= 6)->count(),
            ],
            'leadership'=>[
                'nombre' => 'Liderazgo y relaciones en el trabajo',
                'total' => $leadershipResponses->count(),
                'total_cali'=>$leadershipResponses->avg(),
                'riesgo'=> $this->getDomainRiskLevel('leadership',$leadershipResponses->avg()),
                'null' => $leadershipResponses->filter(fn($value) => $value < 3)->count(),
                'low' => $leadershipResponses->filter(fn($value) => $value >= 3 && $value < 5)->count(),
                'medium' => $leadershipResponses->filter(fn($value) => $value >= 5 && $value < 8)->count(),
                'high' => $leadershipResponses->filter(fn($value) => $value >= 8 && $value < 11)->count(),
                'very_high' => $leadershipResponses->filter(fn($value) => $value >= 11)->count(),
            ],
            'work_relations'=>[
                'nombre' => 'Relaciones en el trabajo',
                'total' => $workRelationsResponses->count(),
                'total_cali'=>$workRelationsResponses->avg(),
                'riesgo'=> $this->getDomainRiskLevel('work_relations',$workRelationsResponses->avg()),
                'null' => $workRelationsResponses->filter(fn($value) => $value < 5)->count(),
                'low' => $workRelationsResponses->filter(fn($value) => $value >= 5 && $value < 8)->count(),
                'medium' => $workRelationsResponses->filter(fn($value) => $value >= 8 && $value < 11)->count(),
                'high' => $workRelationsResponses->filter(fn($value) => $value >= 11 && $value < 14)->count(),
                'very_high' => $workRelationsResponses->filter(fn($value) => $value >= 14)->count(),
            ],
            'violence'=>[
                'nombre' => 'Violencia en el trabajo',
                'total' => $violenceResponses->count(),
                'total_cali'=>$violenceResponses->avg(),
                'riesgo'=> $this->getDomainRiskLevel('violence',$violenceResponses->avg()),
                'null' => $violenceResponses->filter(fn($value) => $value < 7)->count(),
                'low' => $violenceResponses->filter(fn($value) => $value >= 7 && $value < 10)->count(),
                'medium' => $violenceResponses->filter(fn($value) => $value >= 10 && $value < 13)->count(),
                'high' => $violenceResponses->filter(fn($value) => $value >= 13 && $value < 16)->count(),
                'very_high' => $violenceResponses->filter(fn($value) => $value >= 16)->count(),
            ]
        ];



        // Ahora calculamos los resultados generales por categoría
        /*
        $this->generalResultsCategory['ambiente'] = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [1, 2, 3]);
            })
            ->sum('equivalence_response');
        $this->generalResultsCategory['actividad'] = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 18, 19,20,21,22,26,27,42,43,44]);
            })
            ->sum('equivalence_response');
        $this->generalResultsCategory['tiempo'] = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [14,15,16,17]);
            })
            ->sum('equivalence_response');
        $this->generalResultsCategory['liderazgo'] = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [23,24,25,28,29,30,31,32,33,34,35,36,37,38,39,40,46,47,48]);
            })
            ->sum('equivalence_response');

        //ahora se asigna el valor de acuerdo al resultado si es menor de 10 es nulo, de 10 a 20 es bajo, de 21 a 45 es medio, de 46 a 70 es alto y mayor a 70 es muy alto
        $this->generalResultsCategory['ambiente_level'] = match (true) {
            $this->generalResults['ambiente'] <= 10 => 'Nulo',
            $this->generalResults['ambiente'] > 10 && $this->generalResults['ambiente'] <= 20 => 'Bajo',
            $this->generalResults['ambiente'] > 20 && $this->generalResults['ambiente'] <= 45 => 'Medio',
            $this->generalResults['ambiente'] > 45 && $this->generalResults['ambiente'] <= 70 => 'Alto',
            $this->generalResults['ambiente'] > 70 => 'Muy Alto',
            default => 'N/A',
        };
        $this->generalResultsCategory['actividad_level'] = match (true) {
            $this->generalResults['actividad'] <= 10 => 'Nulo',
            $this->generalResults['actividad'] > 10 && $this->generalResults['actividad'] <= 20 => 'Bajo',
            $this->generalResults['actividad'] > 20 && $this->generalResults['actividad'] <= 45 => 'Medio',
            $this->generalResults['actividad'] > 45 && $this->generalResults['actividad'] <= 70 => 'Alto',
            $this->generalResults['actividad'] > 70 => 'Muy Alto',
            default => 'N/A',
        };
        $this->generalResultsCategory['tiempo_level'] = match (true) {
            $this->generalResults['tiempo'] <= 10 => 'Nulo',
            $this->generalResults['tiempo'] > 10 && $this->generalResults['tiempo'] <= 20 => 'Bajo',
            $this->generalResults['tiempo'] > 20 && $this->generalResults['tiempo'] <= 45 => 'Medio',
            $this->generalResults['tiempo'] > 45 && $this->generalResults['tiempo'] <= 70 => 'Alto',
            $this->generalResults['tiempo'] > 70 => 'Muy Alto',
            default => 'N/A',
        };
        $this->generalResultsCategory['liderazgo_level'] = match (true) {
            $this->generalResults['liderazgo'] <= 10 => 'Nulo',
            $this->generalResults['liderazgo'] > 10 && $this->generalResults['liderazgo'] <= 20 => 'Bajo',
            $this->generalResults['liderazgo'] > 20 && $this->generalResults['liderazgo'] <= 45 => 'Medio',
            $this->generalResults['liderazgo'] > 45 && $this->generalResults['liderazgo'] <= 70 => 'Alto',
            $this->generalResults['liderazgo'] > 70 => 'Muy Alto',
            default => 'N/A',
        };
        $categories = [
            'Ambiente de trabajo'
            'Factores propios de la actividad',
            'Organización del tiempo de trabajo',
            'Liderazgo y relaciones en el trabajo',
        ];

            */

        $this->dispatch('open-modal', id: 'modal-result');
    }

    //Abre el Modal de Resultados de la GUIA III
    public function resultsGuideIII()
    {
        $queryResG3=RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->norma->sede_id);

        $calificacion=$queryResG3->sum('equivalence_response');
        $countCollabs=$queryResG3->distinct('user_id')->count('user_id');
        $this->totalResponsesG3=$countCollabs;
        $this->calificacionG3 = $this->totalResponsesG3 > 0 ? $calificacion / $this->totalResponsesG3 : 0;
        $this->resultCuestionarioG3 = $this->getTotalRiskLevelG3($this->calificacionG3);
        /*

        if($calificacion>=140){
            $this->resultCuestionarioG3='Muy Alto';
        }elseif ($calificacion>=99 && $calificacion<140 ) {
            $this->resultCuestionarioG3='Alto';
        }elseif ($calificacion>=75 && $calificacion<99) {
            $this->resultCuestionarioG3='Medio';
        }elseif ($calificacion>=50 && $calificacion<75) {
            $this->resultCuestionarioG3='Bajo';
        }elseif ($calificacion<50) {
            $this->resultCuestionarioG3='Despreciable';
        }
        */
        //Se trabaja con los resultados generales de la guía III
        $this->generalResultsGuideIII = [];
        $responses= RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        /*
         * Asignación de los resultados generales de la guía III
         */
        $this->generalResultsGuideIII = [
            'total' => $responses->count(),
            'null' => $responses->filter(fn($value) => $value < 50)->count(),
            'low' => $responses->filter(fn($value) => $value >= 50 && $value < 75)->count(),
            'medium' => $responses->filter(fn($value) => $value >= 75 && $value < 99)->count(),
            'high' => $responses->filter(fn($value) => $value >= 99 && $value < 140)->count(),
            'very_high' => $responses->filter(fn($value) => $value >= 140)->count(),
        ];
        /*
         * Asignación de los resultados generales por categoría de la guía III
         */
        $ambienteQuery= RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [1, 2, 3,4,5]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverAmbientResponses = $ambienteQuery;
        $actividadQuery= RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [6,7,8,9,10,11,12,13,14,15,16,23,24,25,26,27,28,29,30,35,36,66,67,68,69]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverActivityResponses = $actividadQuery;
        $tiempoQuery= RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [17,18,19,20,21,22]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverTimeResponses = $tiempoQuery;
        $liderazgoQuery= RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [31,32,33,34,37,38,39,40,41,42,43,44,45,46,57,58,59,60,61,62,63,64,71,72,73,74]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverLeadershipResponses = $liderazgoQuery;
        $envirommentQuery= RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [47,48,49,50,51,52,53,54,55,56]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverEntornoResponses = $envirommentQuery;

        $this->generalResultsGuideIIICategory = [
                'ambiente' => [
                    'nombre' => 'Ambiente de trabajo',
                    'total' => $ambienteQuery->count(),
                    'total_cali'=>$ambienteQuery->avg(),
                    'riesgo'=> $this->getCategoryRiskLevelG3('ambiente',$ambienteQuery->avg()),
                    'null' => $ambienteQuery->filter(fn($value) => $value < 5)->count(),
                    'low' => $ambienteQuery->filter(fn($value) => $value >= 5 && $value < 9)->count(),
                    'medium' => $ambienteQuery->filter(fn($value) => $value >= 9 && $value < 11)->count(),
                    'high' => $ambienteQuery->filter(fn($value) => $value >= 11 && $value < 14)->count(),
                    'very_high' => $ambienteQuery->filter(fn($value) => $value >= 14)->count(),
                ],
                'actividad' => [
                    'nombre' => 'Factores propios de la actividad',
                    'total' => $actividadQuery->count(),
                    'total_cali'=>$actividadQuery->avg(),
                    'riesgo'=> $this->getCategoryRiskLevelG3('activity',$actividadQuery->avg()),
                    'null' => $actividadQuery->filter(fn($value) => $value < 15)->count(),
                    'low' => $actividadQuery->filter(fn($value) => $value >= 15 && $value < 30)->count(),
                    'medium' => $actividadQuery->filter(fn($value) => $value >= 30 && $value < 45)->count(),
                    'high' => $actividadQuery->filter(fn($value) => $value >= 45 && $value < 60)->count(),
                    'very_high' => $actividadQuery->filter(fn($value) => $value >= 60)->count(),
                ],
                'tiempo' => [
                    'nombre' => 'Organización del tiempo de trabajo',
                    'total' => $tiempoQuery->count(),
                    'total_cali'=>$tiempoQuery->avg(),
                    'riesgo'=> $this->getCategoryRiskLevelG3('time',$tiempoQuery->avg()),
                    'null' => $tiempoQuery->filter(fn($value) => $value < 5)->count(),
                    'low' => $tiempoQuery->filter(fn($value) => $value >= 5 && $value < 7)->count(),
                    'medium' => $tiempoQuery->filter(fn($value) => $value >= 7 && $value < 10)->count(),
                    'high' => $tiempoQuery->filter(fn($value) => $value >= 10 && $value < 13)->count(),
                    'very_high' => $tiempoQuery->filter(fn($value) => $value >= 13)->count(),
                ],
                'liderazgo' => [
                    'nombre' => 'Liderazgo y relaciones en el trabajo',
                    'total' => $liderazgoQuery->count(),
                    'total_cali'=>$liderazgoQuery->avg(),
                    'riesgo'=> $this->getCategoryRiskLevelG3('leadership',$liderazgoQuery->avg()),
                    'null' => $liderazgoQuery->filter(fn($value) => $value < 14)->count(),
                    'low' => $liderazgoQuery->filter(fn($value) => $value >= 14 && $value < 29)->count(),
                    'medium' => $liderazgoQuery->filter(fn($value) => $value >= 29 && $value < 42)->count(),
                    'high' => $liderazgoQuery->filter(fn($value) => $value >= 42 && $value < 58)->count(),
                    'very_high' => $liderazgoQuery->filter(fn($value) => $value >= 58)->count(),
                ],
                'enviromment' => [
                    'nombre' => 'Entorno Organizacional',
                    'total' => $envirommentQuery->count(),
                    'total_cali'=>$envirommentQuery->avg(),
                    'riesgo'=> $this->getCategoryRiskLevelG3('entorno',$envirommentQuery->avg()),
                    'null' => $envirommentQuery->filter(fn($value) => $value < 10)->count(),
                    'low' => $envirommentQuery->filter(fn($value) => $value >= 10 && $value < 14)->count(),
                    'medium' => $envirommentQuery->filter(fn($value) => $value >= 14 && $value < 18)->count(),
                    'high' => $envirommentQuery->filter(fn($value) => $value >= 18 && $value < 23)->count(),
                    'very_high' => $envirommentQuery->filter(fn($value) => $value >= 23)->count(),
                ]
        ];
        /*
         * Asignación de los resultados generales por Dominio de la guía III
         */

        $this->generalDomainResultsGuideIII = [];
        $conditionResponses = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [1, 2, 3, 4, 5]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverConditionResponses=$conditionResponses;
        $workActivityResponses = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 66, 67, 68, 69]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverWorkActivityResponses=$workActivityResponses;
        $workControlResponses = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [23,24,25, 26, 27, 28, 29, 30,35,36]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverWorkControlResponses=$workControlResponses;
        $workJourneyResponses = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [17, 18]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverWorkJourneyResponses=$workJourneyResponses;
        $wordAndFamilyResponses = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [19, 20, 21, 22]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverWordAndFamilyResponses=$wordAndFamilyResponses;
        $leadershipResponses= RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [31, 32, 33, 34, 37, 38, 39, 40, 41]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverDomainLeadershipResponses=$leadershipResponses;
        $workRelationsResponses = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [42, 43, 44, 45, 46, 71,72,73,74]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverWorkRelationsResponses=$workRelationsResponses;
        $violenceResponses = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [57,58,59,60,61,62,63,64]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverViolenceResponses=$violenceResponses;
        $performanceResponses = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [47,48,49,50,51,52]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverPerformanceResponses=$performanceResponses;
        $inestableResponses = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereHas('question', function($q) {
                $q->whereIn('order', [53,54,55,56]);
            })
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });
        $this->coverInestableResponses=$inestableResponses;
        // Asignar los resultados de dominio en un array
        $this->generalDomainResultsGuideIII = [
            'conditions'=>[
                'nombre' => 'Condiciones en el ambiente de trabajo',
                'total' => $conditionResponses->count(),
                'total_cali'=>$conditionResponses->avg(),
                'riesgo'=>$this->getDomainRiskLevelG3('conditions',$conditionResponses->avg()),
                'null' => $conditionResponses->filter(fn($value) => $value < 5)->count(),
                'low' => $conditionResponses->filter(fn($value) => $value >= 5 && $value < 9)->count(),
                'medium' => $conditionResponses->filter(fn($value) => $value >= 9 && $value < 11)->count(),
                'high' => $conditionResponses->filter(fn($value) => $value >= 11 && $value < 14)->count(),
                'very_high' => $conditionResponses->filter(fn($value) => $value >= 14)->count(),
            ],
            'work_activity'=>[
                'nombre' => 'Carga de Trabajo',
                'total' => $workActivityResponses->count(),
                'total_cali'=>$workActivityResponses->avg(),
                'riesgo'=>$this->getDomainRiskLevelG3('work_activity',$workActivityResponses->avg()),
                'null' => $workActivityResponses->filter(fn($value) => $value < 15)->count(),
                'low' => $workActivityResponses->filter(fn($value) => $value >= 15 && $value < 21)->count(),
                'medium' => $workActivityResponses->filter(fn($value) => $value >= 21 && $value < 27)->count(),
                'high' => $workActivityResponses->filter(fn($value) => $value >= 27 && $value < 37)->count(),
                'very_high' => $workActivityResponses->filter(fn($value) => $value >= 37)->count(),
            ],
            'work_control'=>[
                'nombre' => 'Falta de control sobre el trabajo',
                'total' => $workControlResponses->count(),
                'total_cali'=>$workControlResponses->avg(),
                'riesgo'=>$this->getDomainRiskLevelG3('work_control',$workControlResponses->avg()),
                'null' => $workControlResponses->filter(fn($value) => $value < 11)->count(),
                'low' => $workControlResponses->filter(fn($value) => $value >= 11 && $value < 16)->count(),
                'medium' => $workControlResponses->filter(fn($value) => $value >= 16 && $value < 21)->count(),
                'high' => $workControlResponses->filter(fn($value) => $value >= 21 && $value < 25)->count(),
                'very_high' => $workControlResponses->filter(fn($value) => $value >= 25)->count(),
            ],
            'work_journey'=>[
                'nombre' => 'Jornada de trabajo',
                'total' => $workJourneyResponses->count(),
                'total_cali'=>$workJourneyResponses->avg(),
                'riesgo'=>$this->getDomainRiskLevelG3('work_journey',$workJourneyResponses->avg()),
                'null' => $workJourneyResponses->filter(fn($value) => $value < 1)->count(),
                'low' => $workJourneyResponses->filter(fn($value) => $value >= 1 && $value < 2)->count(),
                'medium' => $workJourneyResponses->filter(fn($value) => $value >= 2 && $value < 4)->count(),
                'high' => $workJourneyResponses->filter(fn($value) => $value >= 4 && $value < 6)->count(),
                'very_high' => $workJourneyResponses->filter(fn($value) => $value >= 6)->count(),
            ],
            'work_family'=>[
                'nombre' => 'Interferencia en la relación trabajo-familia',
                'total' => $wordAndFamilyResponses->count(),
                'total_cali'=>$wordAndFamilyResponses->avg(),
                'riesgo'=>$this->getDomainRiskLevelG3('work_family',$wordAndFamilyResponses->avg()),
                'null' => $wordAndFamilyResponses->filter(fn($value) => $value < 4)->count(),
                'low' => $wordAndFamilyResponses->filter(fn($value) => $value >= 4 && $value < 6)->count(),
                'medium' => $wordAndFamilyResponses->filter(fn($value) => $value >= 6 && $value < 8)->count(),
                'high' => $wordAndFamilyResponses->filter(fn($value) => $value >= 8 && $value < 10)->count(),
                'very_high' => $wordAndFamilyResponses->filter(fn($value) => $value >= 10)->count(),
            ],
            'leadership'=>[
                'nombre' => 'Liderazgo',
                'total' => $leadershipResponses->count(),
                'total_cali'=>$leadershipResponses->avg(),
                'riesgo'=>$this->getDomainRiskLevelG3('leadership',$leadershipResponses->avg()),
                'null' => $leadershipResponses->filter(fn($value) => $value < 9)->count(),
                'low' => $leadershipResponses->filter(fn($value) => $value >= 9 && $value < 12)->count(),
                'medium' => $leadershipResponses->filter(fn($value) => $value >= 12 && $value < 16)->count(),
                'high' => $leadershipResponses->filter(fn($value) => $value >= 16 && $value < 20)->count(),
                'very_high' => $leadershipResponses->filter(fn($value) => $value >= 20)->count(),
            ],
            'work_relations'=>[
                'nombre' => 'Relaciones en el trabajo',
                'total' => $workRelationsResponses->count(),
                'total_cali'=>$workRelationsResponses->avg(),
                'riesgo'=>$this->getDomainRiskLevelG3('work_relations',$workRelationsResponses->avg()),
                'null' => $workRelationsResponses->filter(fn($value) => $value < 10)->count(),
                'low' => $workRelationsResponses->filter(fn($value) => $value >= 10 && $value < 13)->count(),
                'medium' => $workRelationsResponses->filter(fn($value) => $value >= 13 && $value < 17)->count(),
                'high' => $workRelationsResponses->filter(fn($value) => $value >= 17 && $value < 21)->count(),
                'very_high' => $workRelationsResponses->filter(fn($value) => $value >= 21)->count(),
            ],
            'violence'=>[
                'nombre' => 'Violencia',
                'total' => $violenceResponses->count(),
                'total_cali'=>$violenceResponses->avg(),
                'riesgo'=>$this->getDomainRiskLevelG3('violence',$violenceResponses->avg()),
                'null' => $violenceResponses->filter(fn($value) => $value < 7)->count(),
                'low' => $violenceResponses->filter(fn($value) => $value >= 7 && $value < 10)->count(),
                'medium' => $violenceResponses->filter(fn($value) => $value >= 10 && $value < 13)->count(),
                'high' => $violenceResponses->filter(fn($value) => $value >= 13 && $value < 16)->count(),
                'very_high' => $violenceResponses->filter(fn($value) => $value >= 16)->count(),
            ],
            'performance'=>[
                'nombre' => 'Reconocimiento del desempeño',
                'total' => $performanceResponses->count(),
                'total_cali'=>$performanceResponses->avg(),
                'riesgo'=>$this->getDomainRiskLevelG3('reconocimiento',$performanceResponses->avg()),
                'null' => $performanceResponses->filter(fn($value) => $value < 6)->count(),
                'low' => $performanceResponses->filter(fn($value) => $value >=6 && $value < 10)->count(),
                'medium' => $performanceResponses->filter(fn($value) => $value >= 10 && $value < 14)->count(),
                'high' => $performanceResponses->filter(fn($value) => $value >= 14 && $value < 16)->count(),
                'very_high' => $performanceResponses->filter(fn($value) => $value >= 16)->count(),
            ],
            'inestable'=>[
                'nombre' => 'Insuficiente sentido de pertenencia e inestabilidad',
                'total' => $inestableResponses->count(),
                'total_cali'=>$inestableResponses->avg(),
                'riesgo'=>$this->getDomainRiskLevelG3('pertenencia',$inestableResponses->avg()),
                'null' => $inestableResponses->filter(fn($value) => $value < 4)->count(),
                'low' => $inestableResponses->filter(fn($value) => $value >= 4 && $value < 6)->count(),
                'medium' => $inestableResponses->filter(fn($value) => $value >= 6 && $value < 8)->count(),
                'high' => $inestableResponses->filter(fn($value) => $value >= 8 && $value < 10)->count(),
                'very_high' => $inestableResponses->filter(fn($value) => $value >= 10)->count(),
            ],
        ];


        $this->dispatch('open-modal',id:'test-results-guia-iii');
    }

    public function closeTestResultsGuideIII()
    {
        $this->dispatch('close-modal', id: 'test-results-guia-iii');
    }

    // Metodo para cerrar el modal de identificación
    public function closeModalResult()
    {
        $this->dispatch('close-modal', id: 'modal-result');

    }

    // Reiniciar estado del modal
    private function resetIdentificationModal()
    {
        $this->selectedCollaborator = null;
        $this->selectedEventType = null;
        $this->eventDescription = '';
        $this->loadIdentifiedEvents();
        $this->updateAvailableColaborators();
    }

    // Cargar colaboradores ya identificados de la BD
    private function loadIdentifiedColaborators()
    {
        $identified = IdentifiedCollaborator::
        where('norma_id', $this->norma->id ?? null)
        ->where('sede_id', $this->getCurrentSedeId() ?? null)
            ->with('user')
            ->get();


        $this->identifiedColaborators = $identified->map(function ($item) {
            return [
                'id' => $item->collaborator_id,
                'name' => $item->collaborator->name . ' ' . $item->collaborator->first_name . ' ' . $item->collaborator->second_name
            ];
        })->toArray();

        $this->updateAvailableColaborators();
    }

    // Actualizar la lista de colaboradores disponibles
    private function updateAvailableColaborators()
    {
        $identifiedIds = collect($this->identifiedColaborators)->pluck('id')->toArray();
        $colabs = collect($this->colabs);
        if($identifiedIds){
            $this->availableColaborators = $colabs->whereNotIn('id', $identifiedIds);
        }else{
            $this->availableColaborators = $colabs;
        }

    }

    // Agregar colaborador a la lista de identificados
    public function addToIdentifiedList()
    {
        if (!$this->selectedCollaborator || !$this->selectedEventType) {
            return;
        }

        $collaborator = collect($this->colaborators)->firstWhere('id', $this->selectedCollaborator);
        if (!$collaborator) {
            return;
        }

        // Obtener la etiqueta del tipo de evento
        $eventTypeLabel = '';
        foreach ($this->eventTypesByCategory as $category => $types) {
            foreach ($types as $type) {
                if ($type->value === $this->selectedEventType) {
                    $eventTypeLabel = $type->label();
                    break 2;
                }
            }
        }

        $exists = collect($this->identifiedColaborators)->contains('id', $collaborator->id);
        if (!$exists) {
            $this->identifiedColaborators[] = [
                'id' => $collaborator->id,
                'name' => $collaborator->name . ' ' . $collaborator->first_name . ' ' . $collaborator->second_name,
                'event_type' => $this->selectedEventType,
                'event_type_label' => $eventTypeLabel,
                'description' => $this->eventDescription,
                'event_date' => $this->eventDate
            ];

            // Reiniciar campos después de agregar
            $this->selectedCollaborator = null;
            $this->selectedEventType = null;
            $this->eventDescription = '';
            $this->updateAvailableColaborators();
        }
    }

    // Eliminar colaborador de la lista de identificados
    public function removeFromIdentifiedList($index)
    {
        if (isset($this->identifiedColaborators[$index])) {
            unset($this->identifiedColaborators[$index]);
            $this->identifiedColaborators = array_values($this->identifiedColaborators);
            $this->updateAvailableColaborators();
        }
    }


    private function calculateSampleSize($population)
    {
        // Implementación del cálculo de muestra
        // Ejemplo básico
        return ceil(((.9604*$population))/(.0025 * ($population - 1) +.9604));
    }
    public function saveIdentifiedColaborators()
    {
        try {
            $normaId = $this->norma->id ?? null;

            if (!$normaId) {
                Notification::make()
                    ->title('Error al guardar')
                    ->body('No se encontró un proceso NOM-035 activo')
                    ->danger()
                    ->send();
                return;
            }

            // Primero procesamos cada elemento de la lista
            foreach ($this->identifiedColaborators as $identified) {
                $identifiedCollaborator = null;
                $traumaticEvent = null;

                if (!empty($identified['record_id'])) {
                    // Si existe un registro, encontramos el evento y su identificación
                    $traumaticEvent = TraumaticEvent::find($identified['record_id']);
                    if ($traumaticEvent) {
                        $identifiedCollaborator = $traumaticEvent->identifiedCollaborator;
                    }
                }

                // Si no existe la identificación, la creamos
                if (!$identifiedCollaborator) {
                    $identifiedCollaborator = IdentifiedCollaborator::create([
                        'sede_id' => $this->getCurrentSedeId(),
                        'user_id' => $identified['id'],
                        'norma_id' => $normaId,
                        'type_identification' => 'manual',
                        'identified_by' => auth()->id(),
                        'identified_at' => now(),
                    ]);
                }

                // Ahora creamos o actualizamos el evento traumático
                if ($traumaticEvent) {
                    // Actualizar evento existente
                    $traumaticEvent->update([
                        'event_type' => $identified['event_type'],
                        'description' => $identified['description'],
                        'date_occurred' => $identified['event_date'],
                        'updated_at' => now(),
                    ]);
                } else {
                    // Crear nuevo evento
                    TraumaticEvent::create([
                        'user_id' => $identified['id'],
                        'identified_id' => $identifiedCollaborator->id,
                        'event_type' => $identified['event_type'],
                        'description' => $identified['description'],
                        'date_occurred' => $identified['event_date'],
                    ]);
                }
            }

            // Eliminar registros que ya no están en la lista
            $currentIds = collect($this->identifiedColaborators)->pluck('id')->toArray();

            // Primero encontramos todas las identificaciones para esta norma y sede
            $existingIdentifications = IdentifiedCollaborator::where('norma_id', $normaId)
                ->where('sede_id', $this->getCurrentSedeId())
                ->get();

            // Luego eliminamos las que no están en la lista actual
            foreach ($existingIdentifications as $existingId) {
                if (!in_array($existingId->user_id, $currentIds)) {
                    // Eliminar el evento traumático primero (la restricción de clave foránea manejará el resto)
                    if ($existingId->traumaticEvent) {
                        $existingId->traumaticEvent->delete();
                    }

                    // Luego eliminamos la identificación
                    $existingId->delete();
                }
            }

            Notification::make()
                ->title('Colaboradores y eventos registrados correctamente')
                ->success()
                ->send();

            $this->closeIdentificationModal();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al guardar')
                ->body('Ha ocurrido un error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Cargar eventos traumáticos ya registrados
    private function loadIdentifiedEvents()
    {
        $normaId = $this->norma->id;

        if (!$normaId) {
            $this->identifiedColaborators = [];
            return;
        }else{
            $normaId=$this->norma->id??null;
        }

        // Obtenemos todas las identificaciones con sus eventos
        $identifications = IdentifiedCollaborator::where('norma_id', $normaId)
            ->where('sede_id', $this->getCurrentSedeId())
            ->with(['user', 'traumaticEvent'])
            ->get();

        $this->identifiedColaborators = [];

        foreach ($identifications as $identification) {
            $event = $identification->traumaticEvent;

            // Solo incluimos si tiene un evento traumático asociado
            if ($event) {
                $this->identifiedColaborators[] = [
                    'id' => $identification->user_id,
                    'name' => $identification->user->name . ' ' . $identification->user->first_name . ' ' . $identification->user->second_name,
                    'event_type' => $event->event_type->value,
                    'event_type_label' => $event->event_type->label(),
                    'description' => $event->description,
                    'event_date' => $event->date_occurred->format('Y-m-d'),
                    'record_id' => $event->id
                ];
            }
        }

        $this->updateAvailableColaborators();
    }

    public function openTestDialog(){

        $this->dispatch('open-modal', id: 'test-dialog');
    }
    public function closeTestDialog()
    {
        $this->dispatch('close-modal', id: 'test-dialog');
    }

    public function sendTest(){

        $someUsers= IdentifiedCollaborator::where('sede_id', $this->norma->sede_id)
            ->where('norma_id', $this->norma->id)
            ->where('type_identification','manual')
            ->exists();
        $evaluationId=EvaluationsTypes::select('id')
            ->where('name', 'like', 'Nom035: Guía I')
            ->first()->id ?? null;

        // Aquí puedes implementar la lógica para enviar el cuestionario
        $send= ActiveSurvey::create([
            'norma_id' => $this->norma->id,
            'evaluations_type_id' => $evaluationId,
            'some_users' => $someUsers,
        ]);
        $this->norma->status='en_progreso';
        $this->norma->save();

        Notification::make()
            ->title('Cuestionario enviado')
            ->body('Se ha enviado a ')
            ->success()
            ->send();

        $this->closeTestDialog();
        $this->redirect('/dashboard/nom035');
    }
    public function activeRiskFactorTest()
    {

        $evaluationId=EvaluationsTypes::select('id')
            ->where('name', 'like', 'Nom035: Guía II')
            ->first()->id ?? null;

        // Aquí puedes implementar la lógica para enviar el cuestionario
        $send= ActiveSurvey::create([
            'norma_id' => $this->norma->id,
            'evaluations_type_id' => $evaluationId,
            'some_users' => 0,
        ]);
        Notification::make()
            ->title('Guia II Activada')
            ->body('Se ha activado la Guia II correctamente')
            ->success()
            ->send();
        $this->redirect('/dashboard/nom035');

    }

    public function activeGuiaIII($value)
    {
        if($value) {
            //obetnemos la cantidad de colaboradores que seran asignados de manera aleatoria en esa sede $this->muestraGuideIII
            //Selecciona la cantidad de colaboradores a asignar el test $this->muestraGuideIII;

            $collaborators = IdentifiedCollaborator::where('sede_id', $this->norma->sede_id)
                ->where('norma_id', $this->norma->id)
                ->where('type_identification','manual')
                ->inRandomOrder()
                ->take($this->muestraGuideIII)
                ->pluck('user_id')
                ->toArray();


        }else{
            // Verificar si ya existe una encuesta activa para la Guía III
            $evaluationId=EvaluationsTypes::select('id')
                ->where('name', 'like', 'Nom035: Guía III')
                ->first()->id ?? null;

            // Aquí puedes implementar la lógica para enviar el cuestionario

            $send= ActiveSurvey::create([
                'norma_id' => $this->norma->id,
                'evaluations_type_id' => $evaluationId,
                'some_users' => 0,
            ]);

            //Hacer la alerta de que ha sido habilitada la guia III
            Notification::make()
                ->title('Guía III habilitada')
                ->body('Se habilitó la Guía III para su sede.')
                ->success()
                ->send();

            // Si no hay colaboradores seleccionados, no hacemos nada
            //Obtenemos todos los ids de los usuarios de esa sede para enviar las notificaciones de que se ha habilitado la GUIA III
            $collaborators=User::where('sede_id', $this->norma->sede_id)
                ->where('status', true)
                ->get();
            //Ahora enviamos las notificaciones a los usuarios
            foreach ($collaborators as $collaboratorId) {
                Notification::make()
                    ->title('Guía III habilitada')
                    ->info()
                    ->icon('heroicon-m-information-circle')
                    ->body('La Guía III ha sido habilitada para su sede. Por favor, complete el cuestionario.')
                    ->sendToDatabase($collaboratorId);
            }
            $this->closeTypeTest();
            $this->redirect('/dashboard/nom035');
            return;

        }

    }
    public function openTypeTest(){
        $this->muestraGuideIII= $this->calculateSampleSize(count($this->colabs));

        $this->dispatch('open-modal', id: 'type-test-modal');
    }
    public function closeTypeTest()
    {
        $this->dispatch('close-modal', id: 'type-test-modal');
    }

    // Metodo para descargar la plantilla de Word personalizada
    public function descargarWord()
    {

        $templatePath = storage_path('app/plantillas/Politica_de_riesgos_template.docx'); // Mueve el archivo ahí
        $sede = auth()->user()->sede->name;
        $nombreArchivoSalida = 'Politica_Riesgos_' . str_replace(' ', '_', $sede) . '.docx';
        //$sede = auth()->user()->sede->name;
        if ($this->getCurrentSedeId()===3) {
            $sede = 'ADMINISTRADORA DE CENTRALES Y TERMINALES';
        }else{
            $sede = auth()->user()->sede?->company_name ?? 'Sin Razón Social';
        }

        $mes = strtoupper(\Carbon\Carbon::now()->locale('es')->isoFormat('MMMM')); // mes en mayúsculas
        $anio = now()->format('Y'); // año a 4 dígitos
        $rutaTemporal = storage_path('app/livewire-tmp/' . $nombreArchivoSalida);
        $name=auth()->user()->name . ' ' . auth()->user()->first_name . ' ' . auth()->user()->last_name;
        //Traer el puesto de la persona que descarga el archivo
        $puesto = auth()->user()->position?->name ?? 'Sin Puesto';

        try {
            // 2. Cargar la plantilla usando TemplateProcessor
            // Esta clase es mágica: abre el zip del docx, cambia XML y lo cierra sin romper estilos.
            $templateProcessor = new TemplateProcessor($templatePath);

            // 3. Reemplazar las variables
            // La librería busca automáticamente el patrón ${variable}

            // Reemplaza ${sede} en encabezados, títulos y párrafos [cite: 148, 181]
            $templateProcessor->setValue('sede', $sede);

            // Reemplaza ${name} en la tabla de firmas [cite: 166]
            $templateProcessor->setValue('name', $name);
            $templateProcessor->setValue('position', $puesto);
            $templateProcessor->setValue('month', $mes);
            $templateProcessor->setValue('year', $anio);

            // Reemplaza ${fecha} en la sección de datos [cite: 151]
            $templateProcessor->setValue('fecha', now()->format('d/m/Y'));

            // 4. Guardar el documento modificado en una ruta temporal
            $templateProcessor->saveAs($rutaTemporal);

            //********    Se implementa la conversión a PDF con IlovePDF
            $api = new Ilovepdf('project_public_e77e6c5a886b8b19b9e83808f514bf44_uChwi13485de38f75fad79190646cb3fbacfe',
                'secret_key_5ad6baf9deee4bbf2dee704afa6502d5_Lau4ia401a9d22bc6a6715f4c39ec19fd6dd1');
            $task = $api->newTask('officepdf');
            $file = $task->addFile($rutaTemporal);
            $task->execute();

            // 1. Definimos explícitamente la carpeta de destino usando storage_path
            $outputDir = storage_path('app/livewire-tmp');

            // 2. Le decimos a IlovePDF que descargue el archivo en esa carpeta
            $task->download($outputDir);

            // 3. Construimos la ruta completa del nuevo PDF
            // IlovePDF guarda el archivo con el mismo nombre pero extensión .pdf
            $nombrePdf = str_replace('.docx', '.pdf', $nombreArchivoSalida);
            $rutaPdf = $outputDir . '/' . $nombrePdf;

            // 4. Verificamos y descargamos
            if (file_exists($rutaPdf)) {
                // Opcional: Borrar el DOCX temporal para no llenar el servidor
                if(file_exists($rutaTemporal)) { unlink($rutaTemporal); }

                return response()->download($rutaPdf)->deleteFileAfterSend(true);
            } else {
                throw new \Exception("El archivo PDF no se generó correctamente.");
            }
            //++++++++++++++++++++++++++++ Si no funciona o se terminan tokens quitar

            //*+++++++++++++  Esta Parte descarga el word   ++++++++++++++++*//
            /*
            // 5. Retornar la descarga al navegador
            // deleteFileAfterSend(true) es vital para no llenar tu servidor de basura
            return response()->download($rutaTemporal)->deleteFileAfterSend(true);
            //*+++++++++++++  Esta Parte descarga el word   ++++++++++++++++*/

        } catch (\Exception $e) {
            /*
                * En caso de error, por el vencimiento de tokens se hace un intento con otra apikey
                * Aqui la ejecución:
            */
            //********    Se implementa la conversión a PDF con IlovePDF
            try{
                $api = new Ilovepdf('project_public_42a3dc90117b0b0c878bf5dadef062ab_uETqR23adecde4907cf9f9b4c0e38b9ce8a01',
                    'secret_key_5e9597987ed39e06abf3239cef99f619_0A7IXb2567f52af9f60625139c59875993d28');
                $task = $api->newTask('officepdf');
                $file = $task->addFile($rutaTemporal);
                $task->execute();

                // 1. Definimos explícitamente la carpeta de destino usando storage_path
                $outputDir = storage_path('app/livewire-tmp');

                // 2. Le decimos a IlovePDF que descargue el archivo en esa carpeta
                $task->download($outputDir);

                // 3. Construimos la ruta completa del nuevo PDF
                // IlovePDF guarda el archivo con el mismo nombre pero extensión .pdf
                $nombrePdf = str_replace('.docx', '.pdf', $nombreArchivoSalida);
                $rutaPdf = $outputDir . '/' . $nombrePdf;

                // 4. Verificamos y descargamos
                if (file_exists($rutaPdf)) {
                    // Opcional: Borrar el DOCX temporal para no llenar el servidor
                    if(file_exists($rutaTemporal)) { unlink($rutaTemporal); }

                    return response()->download($rutaPdf)->deleteFileAfterSend(true);
                } else {
                    throw new \Exception("El archivo PDF no se generó correctamente.");
                }
            }catch (\Exception $e) {
                // Manejo de errores
                // Log::error($e->getMessage());
                Notification::make()
                    ->title('Error al generar PDF')
                    ->body('No se pudo generar el PDF: ' . $e->getMessage())
                    ->danger()
                    ->send();
            }


        }

        /*
        //$templatePath = storage_path('app/plantillas/politica_adc.pptx'); // Mueve el archivo ahí
        $sede = auth()->user()->sede->name;
        $name=auth()->user()->name . ' ' . auth()->user()->first_mame . ' ' . auth()->user()->last_name;
        $template = new TemplateProcessor($templatePath);
        $template->setValue('sede', $sede);
        $template->setValue('fecha', now()->format('d/m/Y'));
        $template->setValue('name', $name);

        $outputPath = storage_path('app/livewire-tmp/Política_de_riesgos_personalizada.docx');
        $template->saveAs($outputPath);

        return response()->download($outputPath)->deleteFileAfterSend();
        */
    }
    public function descargarExcel()
    {
        $path = storage_path('app/plantillas/plan_de_accion.xlsx');
        return response()->download($path, 'Plan_de_Accion_NOM035.xlsx');
    }

    public function descargarPowerPoint()
    {
        $templatePath = storage_path('app/plantillas/politica_adc.pptx');
        //$templatePath = storage_path('app/plantillas/Politica_de_riesgos.pptx');
        $sede = auth()->user()->sede->name;
        $name = auth()->user()->name . ' ' . auth()->user()->first_name . ' ' . auth()->user()->last_name;

        // Cargar la presentación existente
        $pptReader = IOFactory::createReader('PowerPoint2007');
        $presentation = $pptReader->load($templatePath);

        // Recorrer todas las diapositivas y sus shapes
        foreach ($presentation->getAllSlides() as $slide) {
            foreach ($slide->getShapeCollection() as $shape) {
                if ($shape instanceof \PhpOffice\PhpPresentation\Shape\RichText) {
                    $text = $shape->getPlainText();

                    // Reemplazos de placeholders
                    $text = str_replace('${sede}', $sede, $text);
                    $text = str_replace('${fecha}', now()->format('d/m/Y'), $text);
                    $text = str_replace('${name}', $name, $text);

                    // Actualizar el shape con el texto reemplazado
                    $shape->createTextRun($text);
                }
            }
        }

        // Guardar en temporal
        $outputPath = storage_path('app/livewire-tmp/Politica_de_riesgos_personalizada.pptx');
        $writer = IOFactory::createWriter($presentation, 'PowerPoint2007');
        $writer->save($outputPath);

        // Descargar
        return response()->download($outputPath)->deleteFileAfterSend();
    }

    // Metodo para exportar a PDF el reporte de Personal Identificado de html a pdf
    public function downloadPdfShift()
    {
        $identifiedEmployees = IdentifiedCollaborator::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->with(['user', 'traumaticEvent'])
            ->get();
        //Quiero traer los empleados que contestaron la encuesta de la Guía I
        $totalPersonsSurvey = TraumaticEventSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->get();

        // Verificar si hay empleados identificados
        $employees = $identifiedEmployees->map(function($identified) {
            return [
                'name' => $identified->user->name . ' ' . $identified->user->first_name . ' ' . $identified->user->second_name,
                'section1' => $identified->section1 ? 'ok' : 'false',
                'section2' => $identified->section2 ? 'ok' : 'false',
                'section3' => $identified->section3 ? 'ok' : 'false',
                'section4' => $identified->section4 ? 'ok' : 'false',
                'requires_clinical' => $identified->requires_clinical,
            ];
        })->toArray();

        // Verificar si hay empleados identificados
        $generalResults = [];
        $individualEmployees = [];
        $currentUserId = null;
        $section1 = 'No';
        $section2 = 0;
        $section3 = 0;
        $section4 = 0;

        foreach ($totalPersonsSurvey as $index => $item) {
            // Si cambiamos de usuario, guardamos los resultados del usuario anterior
            if ($currentUserId !== null && $currentUserId !== $item->user->id) {
                // Guardar resultados del usuario anterior
                $generalResults[] = [
                    'name' => $previousUserName,
                    'section1' => $section1,
                    'section2' => $section2 >= 1 ? 'Sí' : 'No',
                    'section3' => $section3 >= 3 ? 'Sí' : 'No',
                    'section4' => $section4 >= 2 ? 'Sí' : 'No',
                    'requires_clinical' => $section2 >= 1 || $section3 >= 3 || $section4 >= 2,
                ];
                $individualEmployees[]=[
                    'name'=> $previousUserName,
                    'position' => User::find($currentUserId)->position->name ?? 'No definido',
                    'requires_clinical' => $section2 >= 1 || $section3 >= 3 || $section4 >= 2,
                ];

                // Reiniciar variables para el nuevo usuario
                $section1 = 'No';
                $section2 = 0;
                $section3 = 0;
                $section4 = 0;
            }

            // Actualizar usuario actual
            $currentUserId = $item->user->id;
            $previousUserName = $item->user->name . ' ' . $item->user->first_name . ' ' . $item->user->last_name;

            // Procesar respuestas por sección
            if ($item->question->competence->name === 'Sección I') {
                $section1 = $item->response == 'si' ? 'Sí' : 'No';
            } elseif ($item->question->competence->name === 'Sección II') {
                if ($item->response == 'si') {
                    $section2++;
                }
            } elseif ($item->question->competence->name === 'Sección III') {
                if ($item->response == 'si') {
                    $section3++;
                }
            } elseif ($item->question->competence->name === 'Sección IV') {
                if ($item->response == 'si') {
                    $section4++;
                }
            }
        }

        // No olvides guardar el último usuario después del bucle
        if ($currentUserId !== null) {
            $generalResults[] = [
                'name' => $previousUserName,
                'section1' => $section1,
                'section2' => $section2 >= 1 ? 'Sí' : 'No',
                'section3' => $section3 >= 3 ? 'Sí' : 'No',
                'section4' => $section4 >= 2 ? 'Sí' : 'No',
                'requires_clinical' => $section2 >= 1 || $section3 >= 3 || $section4 >= 2,
            ];

            $individualEmployees[]=[
                'name'=> $previousUserName,
                'position' => User::find($currentUserId)->position->name ?? 'No definido',
                'requires_clinical' => $section2 >= 1 || $section3 >= 3 || $section4 >= 2,
            ];
        }
        $user=auth()->user();
        $sedeId=$user->sede_id;
        $campaign = Campaign::whereHas('sedes', function ($query) use ($sedeId) {
            $query->where('sedes.id', $sedeId);
        })
            ->whereHas('evaluations', function ($query) {
                $query->whereIn('evaluations_types.id', [6, 7, 8]);
            })
            ->latest()
            ->first();
        $startDate=$campaign->start_date??now();
        $endDate=$campaign->end_date??now();
        // Pasar las variables directamente, no como arreglo
        $html = view('filament.pages.nom35.identification_report', [
            'company' => auth()->user()->sede->company_name ?? 'No definido', //OK
            'reportDate' => \Carbon\Carbon::parse($startDate)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'period' => \Carbon\Carbon::parse($endDate)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'totalSurveys' => $this->colabResponsesG1,
            'noClinical' => $this->colabResponsesG1 - $identifiedEmployees->count(),
            'clinical' => $identifiedEmployees->count(),
            'noClinicalPercent' => $this->colabResponsesG1 > 0 ?
                number_format((($this->colabResponsesG1 - $identifiedEmployees->count()) / $this->colabResponsesG1 ) * 100, 1) : '0',
            'clinicalPercent' => $this->colabResponsesG1 > 0 ?
                number_format(($identifiedEmployees->count() / $this->colabResponsesG1 ) * 100, 1) : '0',
            'employees' => $employees,
            'individualEmployees' => $individualEmployees,
            'totalPersonsSurvey' => $generalResults,
        ])->render();


        // Forzar codificación UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        $payload = [
            'source'    => $html,
            'landscape' => false,
            'use_print' => false,
            'margin'    => [
                'top'    => 20,
                'bottom' => 20,
                'left'   => 15,
                'right'  => 15,
            ],
        ];


        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-Key'    => config('services.pdfshift.api_key'),
        ])
            ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
            ->post('https://api.pdfshift.io/v3/convert/pdf');


        if ($response->successful()) {

            $pdfContent = $response->body();
            // Forzar descarga en el navegador
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            },'Canalizaciones.pdf');


        }else{
            Notification::make()
                ->title('Error al generar PDF')
                ->body('No se pudo generar el PDF: ' . $response->body())
                ->danger()
                ->send();
            //return abort(500, 'Error al generar el PDF');

        }
    }

    public function reporteGeneralGIIIDownload(){
        $recomendaciones=[
            'Muy Alto' =>'Se requiere realizar el análisis de cada categoría y dominio para establecer las acciones de intervención apropiadas, mediante un Programa de intervención
            que deberá incluir evaluaciones específicas, y contemplar campañas de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para
            la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación
            y difusión.',
            'Alto' => 'Se requiere realizar un análisis de cada categoría y dominio, de manera que se puedan determinar las acciones de intervención apropiadas a través de un Programa
             de intervención, que podrá incluir una evaluación específica y deberá incluir una campaña de sensibilización, revisar la política de prevención de riesgos psicosociales
              y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como
              reforzar su aplicación y difusión.',
            'Medio' => 'Se requiere revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión, mediante un Programa de intervención.',
            'Bajo' => 'Es necesario una mayor difusión de la política de prevención de riesgos psicosociales y programas para: la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral. ',
            'Nulo' => 'El riesgo resulta despreciable por lo que no se requiere medidas adicionales.'
        ];
        //dd($this->generalResultsGuideIIICategory);
        $user=auth()->user();
        $sedeId=$user->sede_id;
        $campaign = Campaign::whereHas('sedes', function ($query) use ($sedeId) {
            $query->where('sedes.id', $sedeId);
        })
            ->whereHas('evaluations', function ($query) {
                $query->whereIn('evaluations_types.id', [6, 7, 8]);
            })
            ->latest()
            ->first();
        $startDate=$campaign->start_date??now();
        $endDate=$campaign->end_date??now();

        $html=view('filament.pages.nom35.risk_factor_report', [
            'company' => auth()->user()->sede->company_name ?? 'No definido', //OK
            'reportDate' => \Carbon\Carbon::parse($startDate)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'guia' => 'III',
            'period' => \Carbon\Carbon::parse($endDate)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'responsesTotalG2' => $this->totalResponsesG3,
            'generalResults' => $this->generalResultsGuideIII,
            'calificacionG2' =>  $this->calificacionG3,
            'resultCuestionario' => $this->resultCuestionarioG3,
            'categories'=>$this->generalResultsGuideIIICategory,
            'dominios'=> $this->generalDomainResultsGuideIII,
            'recommendations' =>$recomendaciones[$this->resultCuestionarioG3==='Despreciable'?'Nulo':$this->resultCuestionarioG3],
        ])->render();

        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        $payload = [
            'source'    => $html,
            'landscape' => false,
            'use_print' => false,
            'margin'    => [
                'top'    => 10,
                'bottom' => 10,
                'left'   => 10,
                'right'  => 10,
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-Key'    => config('services.pdfshift.api_key'),
        ])
            ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
            ->post('https://api.pdfshift.io/v3/convert/pdf');
        if ($response->successful()) {

            $pdfContent = $response->body();
            // Forzar descarga en el navegador
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            },'ReporteGeneralG3.pdf');


        }else{
            Notification::make()
                ->title('Error al generar PDF')
                ->body('No se pudo generar el PDF: ' . $response->body())
                ->danger()
                ->send();
            //return abort(500, 'Error al generar el PDF');

        }

    }


    public function reportGeneralGIIDownload()
    {

        $recomendaciones=[
            'Muy Alto' =>'Se requiere realizar el análisis de cada categoría y dominio para establecer las acciones de intervención apropiadas, mediante un Programa de intervención
            que deberá incluir evaluaciones específicas, y contemplar campañas de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para
            la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación
            y difusión.',
            'Alto' => 'Se requiere realizar un análisis de cada categoría y dominio, de manera que se puedan determinar las acciones de intervención apropiadas a través de un Programa
             de intervención, que podrá incluir una evaluación específica y deberá incluir una campaña de sensibilización, revisar la política de prevención de riesgos psicosociales
              y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como
              reforzar su aplicación y difusión.',
            'Medio' => 'Se requiere revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión, mediante un Programa de intervención.',
            'Bajo' => 'Es necesario una mayor difusión de la política de prevención de riesgos psicosociales y programas para: la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral. ',
            'Nulo' => 'El riesgo resulta despreciable por lo que no se requiere medidas adicionales.'
        ];

         $html=view('filament.pages.nom35.risk_factor_report', [
            'company' => auth()->user()->sede->company_name ?? 'No definido', //OK
            'reportDate' => \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM, YYYY'),
            'period' => $this->norma->start_date->locale('es')->isoFormat('D [de] MMMM, YYYY') . ' al ' . \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM, YYYY'),
             'guia' => 'II',
             'responsesTotalG2' => $this->responsesTotalG2,
            'generalResults' => $this->generalResults,
            'calificacionG2' => $this->calificacion,
            'resultCuestionario' => $this->resultCuestionario,
            'categories'=>$this->generalResultsCategory,
            'dominios'=> $this->domainResults,
            'recommendations' =>$recomendaciones[$this->resultCuestionario==='Despreciable'?'Nulo':$this->resultCuestionario],
            ])->render();


        // Forzar codificación UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        $payload = [
            'source'    => $html,
            'landscape' => false,
            'use_print' => false,
            'margin'    => [
                'top'    => 10,
                'bottom' => 10,
                'left'   => 10,
                'right'  => 10,
            ],
        ];


        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-Key'    => config('services.pdfshift.api_key'),
        ])
            ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
            ->post('https://api.pdfshift.io/v3/convert/pdf');
        if ($response->successful()) {

            $pdfContent = $response->body();
            // Forzar descarga en el navegador
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            },'ReporteGeneral.pdf');


        }else{
            Notification::make()
                ->title('Error al generar PDF')
                ->body('No se pudo generar el PDF: ' . $response->body())
                ->danger()
                ->send();
            //return abort(500, 'Error al generar el PDF');

        }


    }
   /* public function reportIndividualGIIDownload(){
        $domainNames = [
            'conditions' => 'Condiciones del ambiente de trabajo',
            'work_activity' => 'Carga de Trabajo',
            'work_control' => 'Falta de control sobre el trabajo',
            'work_journey' => 'Organización del tiempo de trabajo',
            'work_family' => 'Interferencia trabajo-familia',
            'leadership' => 'Liderazgo y relaciones en el trabajo',
            'work_relations' => 'Relaciones en el trabajo',
            'violence' => 'Violencia en el trabajo',
        ];

        $categoryNames = [
            'ambiente' => 'Ambiente de trabajo',
            'activity' => 'Factores propios de la actividad',
            'time' => 'Organización del tiempo de trabajo',
            'leadership' => 'Liderazgo y relaciones en el trabajo',
        ];

        //quiero traer todos los usuarios que respondieron la encuesta de la guia II
        $users = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(function ($items, $userId) {
                $user = $items->first()->user;
                $responses = $items->mapWithKeys(function ($item) {
                    return [$item->question->order => $item->equivalence_response];
                })->toArray();

                $dimensions = [
                    'condiciones_peligrosas' => array_sum(array_intersect_key($responses, array_flip([2]))),
                    'condiciones_deficientes' => array_sum(array_intersect_key($responses, array_flip([1]))),
                    'trabajos_peligrosos' => array_sum(array_intersect_key($responses, array_flip([3]))),
                    'cargas_cuantitativas' => array_sum(array_intersect_key($responses, array_flip([4,9]))),
                    'ritmos_acelerados' => array_sum(array_intersect_key($responses, array_flip([5,6]))),
                    'carga_mental' => array_sum(array_intersect_key($responses, array_flip([7,8]))),
                    'cargas_psicologicas' => array_sum(array_intersect_key($responses, array_flip([42,43,44]))),
                    'alta_responsabilidad' => array_sum(array_intersect_key($responses, array_flip([10,11]))),
                    'cargas_contradictorias' => array_sum(array_intersect_key($responses, array_flip([12,13]))),
                    'falta_control' => array_sum(array_intersect_key($responses, array_flip([20,21,22]))),
                    'posibilidad_desarrollo' => array_sum(array_intersect_key($responses, array_flip([18,19]))),
                    'capacitacion' => array_sum(array_intersect_key($responses, array_flip([26,27]))),
                    'jornadas_extensas' => array_sum(array_intersect_key($responses, array_flip([14,15]))),
                    'influencia_fuera_trabajo' => array_sum(array_intersect_key($responses, array_flip([16]))),
                    'responsabilidades_familiares' => array_sum(array_intersect_key($responses, array_flip([17]))),
                    'claridad_funciones' => array_sum(array_intersect_key($responses, array_flip([23,24,25]))),
                    'liderazgo' => array_sum(array_intersect_key($responses, array_flip([28,29]))),
                    'relaciones_sociales' => array_sum(array_intersect_key($responses, array_flip([30,31,32]))),
                    'relacion_colaboradores' => array_sum(array_intersect_key($responses, array_flip([46,47,48]))),
                    'violencia_laboral' => array_sum(array_intersect_key($responses, array_flip([33,34,35,36,37,38,39,40]))),
                ];


                // Calcular los dominios
                $domains = [
                    'conditions' => array_sum(array_intersect_key($responses, array_flip([1,2,3]))),
                    'work_activity' => array_sum(array_intersect_key($responses, array_flip([4,9,5,6,7,8,42,43,44,10,11,12,13]))),
                    'work_control' => array_sum(array_intersect_key($responses, array_flip([20,21,22,18,19,26,27]))),
                    'work_journey' => array_sum(array_intersect_key($responses, array_flip([14,15]))),
                    'work_family' => array_sum(array_intersect_key($responses, array_flip([16,17]))),
                    'leadership' => array_sum(array_intersect_key($responses, array_flip([23,24,25,28,29]))),
                    'work_relations' => array_sum(array_intersect_key($responses, array_flip([30,31,32,46,47,48]))),
                    'violence' => array_sum(array_intersect_key($responses, array_flip([33,34,35,36,37,38,39,40]))),
                ];

                // Calcular categories
                $categories = [
                    'ambiente' => array_sum(array_intersect_key($responses, array_flip([1,2,3]))),
                    'activity' => $domains['work_activity']+$domains['work_control'],
                    'time' => $domains['work_journey']+$domains['work_family'],
                    'leadership' =>$domains['leadership']+$domains['work_relations']+$domains['violence'],
                ];

                return [
                    'user' => $user,
                    'responses' => $responses,
                    'categories' => $categories,
                    'domains' => $domains,
                    'dimensions' => $dimensions,
                    'total_score' => array_sum($responses),
                ];
            })->toArray();
        dd($users);

    }*/
    public function reportIndividualGIIDownload(){
        $domainNames = [
            'conditions' => 'Condiciones del ambiente de trabajo',
            'work_activity' => 'Carga de Trabajo',
            'work_control' => 'Falta de control sobre el trabajo',
            'work_journey' => 'Jornada de trabajo',
            'work_family' => 'Interferencia en la relación trabajo-familia',
            'leadership' => 'Liderazgo',
            'work_relations' => 'Relaciones en el trabajo',
            'violence' => 'Violencia',
        ];

        $categoryNames = [
            'ambiente' => 'Ambiente de trabajo',
            'activity' => 'Factores propios de la actividad',
            'time' => 'Organización del tiempo de trabajo',
            'leadership' => 'Liderazgo y relaciones en el trabajo',
        ];

        $dimensionNames = [
            'condiciones_peligrosas' => 'Condiciones peligrosas e inseguras',
            'condiciones_deficientes' => 'Condiciones deficientes e insalubres',
            'trabajos_peligrosos' => 'Trabajos peligrosos',
            'cargas_cuantitativas' => 'Cargas cuantitativas',
            'ritmos_acelerados' => 'Ritmos de trabajo acelerado',
            'carga_mental' => 'Carga mental',
            'cargas_psicologicas' => 'Cargas psicológicas emocionales',
            'alta_responsabilidad' => 'Cargas de alta responsabilidad',
            'cargas_contradictorias' => 'Cargas contradictorias o inconsistentes',
            'falta_control' => 'Falta de control y autonomía sobre el trabajo',
            'posibilidad_desarrollo' => 'Limitada o nula posibilidad de desarrollo',
            'capacitacion' => 'Limitada o inexistente capacitación',
            'jornadas_extensas' => 'Jornadas de trabajo extensas',
            'influencia_fuera_trabajo' => 'Influencia del trabajo fuera del centro laboral',
            'responsabilidades_familiares' => 'Influencia de las responsabilidades familiares ',
            'claridad_funciones' => 'Escasa claridad de funciones',
            'liderazgo' => 'Características del liderazgo ',
            'relaciones_sociales' => 'Relaciones sociales en el trabajo',
            'relacion_colaboradores' => 'Deficiente relación con los colaboradores que supervisa',
            'violencia_laboral' => 'Violencia laboral',
        ];

        $recomendaciones=[
            'Muy Alto' =>'Se requiere realizar el análisis de cada categoría y dominio para establecer las acciones de intervención apropiadas, mediante un Programa de intervención que deberá incluir evaluaciones específicas1, y contemplar campañas de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Alto' => 'Se requiere realizar un análisis de cada categoría y dominio, de manera que se puedan determinar las acciones de intervención apropiadas a través de un Programa de intervención, que podrá incluir una evaluación específica y deberá incluir una campaña de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Medio' => 'Se requiere revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión, mediante un Programa de intervención.',
            'Bajo' => 'Es necesario una mayor difusión de la política de prevención de riesgos psicosociales y programas para: la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral. ',
            'Nulo' => 'El riesgo resulta despreciable por lo que no se requiere medidas adicionales.'
        ];

        // Traer todos los usuarios que respondieron la encuesta de la guía II
        $users = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(function ($items, $userId) use ($domainNames, $categoryNames, $dimensionNames,$recomendaciones) {
                $user = $items->first()->user;
                $responses = $items->mapWithKeys(function ($item) {
                    return [$item->question->order => $item->equivalence_response];
                })->toArray();

                // Calcular las dimensiones
                $dimensions = [
                    'condiciones_peligrosas' => array_sum(array_intersect_key($responses, array_flip([2]))),
                    'condiciones_deficientes' => array_sum(array_intersect_key($responses, array_flip([1]))),
                    'trabajos_peligrosos' => array_sum(array_intersect_key($responses, array_flip([3]))),
                    'cargas_cuantitativas' => array_sum(array_intersect_key($responses, array_flip([4,9]))),
                    'ritmos_acelerados' => array_sum(array_intersect_key($responses, array_flip([5,6]))),
                    'carga_mental' => array_sum(array_intersect_key($responses, array_flip([7,8]))),
                    'cargas_psicologicas' => array_sum(array_intersect_key($responses, array_flip([42,43,44]))),
                    'alta_responsabilidad' => array_sum(array_intersect_key($responses, array_flip([10,11]))),
                    'cargas_contradictorias' => array_sum(array_intersect_key($responses, array_flip([12,13]))),
                    'falta_control' => array_sum(array_intersect_key($responses, array_flip([20,21,22]))),
                    'posibilidad_desarrollo' => array_sum(array_intersect_key($responses, array_flip([18,19]))),
                    'capacitacion' => array_sum(array_intersect_key($responses, array_flip([26,27]))),
                    'jornadas_extensas' => array_sum(array_intersect_key($responses, array_flip([14,15]))),
                    'influencia_fuera_trabajo' => array_sum(array_intersect_key($responses, array_flip([16]))),
                    'responsabilidades_familiares' => array_sum(array_intersect_key($responses, array_flip([17]))),
                    'claridad_funciones' => array_sum(array_intersect_key($responses, array_flip([23,24,25]))),
                    'liderazgo' => array_sum(array_intersect_key($responses, array_flip([28,29]))),
                    'relaciones_sociales' => array_sum(array_intersect_key($responses, array_flip([30,31,32]))),
                    'relacion_colaboradores' => array_sum(array_intersect_key($responses, array_flip([46,47,48]))),
                    'violencia_laboral' => array_sum(array_intersect_key($responses, array_flip([33,34,35,36,37,38,39,40]))),
                ];

                // Calcular los dominios
                $domains = [
                    'conditions' => array_sum(array_intersect_key($responses, array_flip([1,2,3]))),
                    'work_activity' => array_sum(array_intersect_key($responses, array_flip([4,9,5,6,7,8,42,43,44,10,11,12,13]))),
                    'work_control' => array_sum(array_intersect_key($responses, array_flip([20,21,22,18,19,26,27]))),
                    'work_journey' => array_sum(array_intersect_key($responses, array_flip([14,15]))),
                    'work_family' => array_sum(array_intersect_key($responses, array_flip([16,17]))),
                    'leadership' => array_sum(array_intersect_key($responses, array_flip([23,24,25,28,29]))),
                    'work_relations' => array_sum(array_intersect_key($responses, array_flip([30,31,32,46,47,48]))),
                    'violence' => array_sum(array_intersect_key($responses, array_flip([33,34,35,36,37,38,39,40]))),
                ];

                // Calcular categorías
                $categories = [
                    'ambiente' => array_sum(array_intersect_key($responses, array_flip([1,2,3]))),
                    'activity' => $domains['work_activity']+$domains['work_control'],
                    'time' => $domains['work_journey']+$domains['work_family'],
                    'leadership' => $domains['leadership']+$domains['work_relations']+$domains['violence'],
                ];

                // Función para determinar nivel de riesgo
                $getRiskLevel = function($score, $thresholds) {
                    if ($score < $thresholds[0]) return 'Nulo o despreciable';
                    if ($score < $thresholds[1]) return 'Bajo';
                    if ($score < $thresholds[2]) return 'Medio';
                    if ($score < $thresholds[3]) return 'Alto';
                    return 'Muy alto';
                };

                // Agregar niveles de riesgo para cada dimensión
                $dimensionsWithLevels = [];
                foreach ($dimensions as $key => $value) {
                    $dimensionsWithLevels[$key] = [
                        'score' => $value,
                        'name' => $dimensionNames[$key],
                        'level' => $this->getDimensionRiskLevel($key, $value)
                    ];
                }
                $domainsWithLevels = [];
                foreach ($domains as $key => $value) {
                    $domainsWithLevels[$key] = [
                        'score' => $value,
                        'name' => $domainNames[$key],
                        'level' => $this->getDomainRiskLevel($key, $value)
                    ];
                }
                // Agregar niveles de riesgo para cada categoría
                $categoriesWithLevels = [];
                foreach ($categories as $key => $value) {
                    $categoriesWithLevels[$key] = [
                        'score' => $value,
                        'name' => $categoryNames[$key],
                        'level' => $this->getCategoryRiskLevel($key, $value)

                    ];
                }
                // Estructura jerárquica: Categorías -> Dominios -> Dimensiones
                $structure = [
                    'ambiente' => [
                        'name' => 'Ambiente de trabajo',
                        'score' => $dimensions['condiciones_peligrosas'] + $dimensions['condiciones_deficientes'] + $dimensions['trabajos_peligrosos'],
                        'level' => $this->getCategoryRiskLevel('ambiente', $dimensions['condiciones_peligrosas'] + $dimensions['condiciones_deficientes'] + $dimensions['trabajos_peligrosos']),
                        'domains' => [
                            'conditions' => [
                                'name' => 'Condiciones en el ambiente de trabajo',
                                'score' => $dimensions['condiciones_peligrosas'] + $dimensions['condiciones_deficientes'] + $dimensions['trabajos_peligrosos'],
                                'level' => $this->getDomainRiskLevel('conditions', $dimensions['condiciones_peligrosas'] + $dimensions['condiciones_deficientes'] + $dimensions['trabajos_peligrosos']),
                                'dimensions' => [
                                    [
                                        'name' => 'Condiciones peligrosas e inseguras',
                                        'score' => $dimensions['condiciones_peligrosas'],
                                        'level' => $this->getDimensionRiskLevel('condiciones_peligrosas', $dimensions['condiciones_peligrosas'])
                                    ],
                                    [
                                        'name' => 'Condiciones deficientes e insalubres',
                                        'score' => $dimensions['condiciones_deficientes'],
                                        'level' => $this->getDimensionRiskLevel('condiciones_deficientes', $dimensions['condiciones_deficientes'])
                                    ],
                                    [
                                        'name' => 'Trabajos peligrosos',
                                        'score' => $dimensions['trabajos_peligrosos'],
                                        'level' => $this->getDimensionRiskLevel('trabajos_peligrosos', $dimensions['trabajos_peligrosos'])
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'activity' => [
                        'name' => 'Factores propios de la actividad',
                        'score' => array_sum(array_slice($dimensions, 3, 9)), // Suma de todas las dimensiones de actividad
                        'level' => $this->getCategoryRiskLevel('activity', array_sum(array_slice($dimensions, 3, 9))),
                        'domains' => [
                            'work_activity' => [
                                'name' => 'Carga de trabajo',
                                'score' => $dimensions['cargas_cuantitativas'] + $dimensions['ritmos_acelerados'] + $dimensions['carga_mental'] + $dimensions['cargas_psicologicas'] + $dimensions['alta_responsabilidad'] + $dimensions['cargas_contradictorias'],
                                'level' => $this->getDomainRiskLevel('work_activity', $dimensions['cargas_cuantitativas'] + $dimensions['ritmos_acelerados'] + $dimensions['carga_mental'] + $dimensions['cargas_psicologicas'] + $dimensions['alta_responsabilidad'] + $dimensions['cargas_contradictorias']),
                                'dimensions' => [
                                    [
                                        'name' => 'Cargas cuantitativas',
                                        'score' => $dimensions['cargas_cuantitativas'],
                                        'level' => $this->getDimensionRiskLevel('cargas_cuantitativas', $dimensions['cargas_cuantitativas'])
                                    ],
                                    [
                                        'name' => 'Ritmos de trabajo acelerado',
                                        'score' => $dimensions['ritmos_acelerados'],
                                        'level' => $this->getDimensionRiskLevel('ritmos_acelerados', $dimensions['ritmos_acelerados'])
                                    ],
                                    [
                                        'name' => 'Carga mental',
                                        'score' => $dimensions['carga_mental'],
                                        'level' => $this->getDimensionRiskLevel('carga_mental', $dimensions['carga_mental'])
                                    ],
                                    [
                                        'name' => 'Cargas psicológicas emocionales',
                                        'score' => $dimensions['cargas_psicologicas'],
                                        'level' => $this->getDimensionRiskLevel('cargas_psicologicas', $dimensions['cargas_psicologicas'])
                                    ],
                                    [
                                        'name' => 'Cargas de alta responsabilidad',
                                        'score' => $dimensions['alta_responsabilidad'],
                                        'level' => $this->getDimensionRiskLevel('alta_responsabilidad', $dimensions['alta_responsabilidad'])
                                    ],
                                    [
                                        'name' => 'Cargas contradictorias o inconsistentes',
                                        'score' => $dimensions['cargas_contradictorias'],
                                        'level' => $this->getDimensionRiskLevel('cargas_contradictorias', $dimensions['cargas_contradictorias'])
                                    ]
                                ]
                            ],
                            'work_control' => [
                                'name' => 'Falta de control sobre el trabajo',
                                'score' => $dimensions['falta_control'] + $dimensions['posibilidad_desarrollo'] + $dimensions['capacitacion'],
                                'level' => $this->getDomainRiskLevel('work_control', $dimensions['falta_control'] + $dimensions['posibilidad_desarrollo'] + $dimensions['capacitacion']),
                                'dimensions' => [
                                    [
                                        'name' => 'Falta de control y autonomía sobre el trabajo',
                                        'score' => $dimensions['falta_control'],
                                        'level' => $this->getDimensionRiskLevel('falta_control', $dimensions['falta_control'])
                                    ],
                                    [
                                        'name' => 'Limitada o nula posibilidad de desarrollo',
                                        'score' => $dimensions['posibilidad_desarrollo'],
                                        'level' => $this->getDimensionRiskLevel('posibilidad_desarrollo', $dimensions['posibilidad_desarrollo'])
                                    ],
                                    [
                                        'name' => 'Limitada o inexistente capacitación',
                                        'score' => $dimensions['capacitacion'],
                                        'level' => $this->getDimensionRiskLevel('capacitacion', $dimensions['capacitacion'])
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'time' => [
                        'name' => 'Organización del tiempo de trabajo',
                        'score' => $dimensions['jornadas_extensas'] + $dimensions['influencia_fuera_trabajo'] + $dimensions['responsabilidades_familiares'],
                        'level' => $this->getCategoryRiskLevel('time', $dimensions['jornadas_extensas'] + $dimensions['influencia_fuera_trabajo'] + $dimensions['responsabilidades_familiares']),
                        'domains' => [
                            'work_journey' => [
                                'name' => 'Jornada de trabajo',
                                'score' => $dimensions['jornadas_extensas'],
                                'level' => $this->getDomainRiskLevel('work_journey', $dimensions['jornadas_extensas']),
                                'dimensions' => [
                                    [
                                        'name' => 'Jornadas de trabajo extensas',
                                        'score' => $dimensions['jornadas_extensas'],
                                        'level' => $this->getDimensionRiskLevel('jornadas_extensas', $dimensions['jornadas_extensas'])
                                    ]
                                ]
                            ],
                            'work_family' => [
                                'name' => 'Interferencia en la relación trabajo-familia',
                                'score' => $dimensions['influencia_fuera_trabajo'] + $dimensions['responsabilidades_familiares'],
                                'level' => $this->getDomainRiskLevel('work_family', $dimensions['influencia_fuera_trabajo'] + $dimensions['responsabilidades_familiares']),
                                'dimensions' => [
                                    [
                                        'name' => 'Influencia del trabajo fuera del centro laboral',
                                        'score' => $dimensions['influencia_fuera_trabajo'],
                                        'level' => $this->getDimensionRiskLevel('influencia_fuera_trabajo', $dimensions['influencia_fuera_trabajo'])
                                    ],
                                    [
                                        'name' => 'Influencia de las responsabilidades familiares',
                                        'score' => $dimensions['responsabilidades_familiares'],
                                        'level' => $this->getDimensionRiskLevel('responsabilidades_familiares', $dimensions['responsabilidades_familiares'])
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'leadership' => [
                        'name' => 'Liderazgo y relaciones en el trabajo',
                        'score' => $dimensions['claridad_funciones'] + $dimensions['liderazgo'] + $dimensions['relaciones_sociales'] + $dimensions['relacion_colaboradores'] + $dimensions['violencia_laboral'],
                        'level' => $this->getCategoryRiskLevel('leadership', $dimensions['claridad_funciones'] + $dimensions['liderazgo'] + $dimensions['relaciones_sociales'] + $dimensions['relacion_colaboradores'] + $dimensions['violencia_laboral']),
                        'domains' => [
                            'leadership' => [
                                'name' => 'Liderazgo',
                                'score' => $dimensions['claridad_funciones'] + $dimensions['liderazgo'],
                                'level' => $this->getDomainRiskLevel('leadership', $dimensions['claridad_funciones'] + $dimensions['liderazgo']),
                                'dimensions' => [
                                    [
                                        'name' => 'Escasa claridad de funciones',
                                        'score' => $dimensions['claridad_funciones'],
                                        'level' => $this->getDimensionRiskLevel('claridad_funciones', $dimensions['claridad_funciones'])
                                    ],
                                    [
                                        'name' => 'Características del liderazgo',
                                        'score' => $dimensions['liderazgo'],
                                        'level' => $this->getDimensionRiskLevel('liderazgo', $dimensions['liderazgo'])
                                    ]
                                ]
                            ],
                            'work_relations' => [
                                'name' => 'Relaciones en el trabajo',
                                'score' => $dimensions['relaciones_sociales'] + $dimensions['relacion_colaboradores'],
                                'level' => $this->getDomainRiskLevel('work_relations', $dimensions['relaciones_sociales'] + $dimensions['relacion_colaboradores']),
                                'dimensions' => [
                                    [
                                        'name' => 'Relaciones sociales en el trabajo',
                                        'score' => $dimensions['relaciones_sociales'],
                                        'level' => $this->getDimensionRiskLevel('relaciones_sociales', $dimensions['relaciones_sociales'])
                                    ],
                                    [
                                        'name' => 'Deficiente relación con los colaboradores que supervisa',
                                        'score' => $dimensions['relacion_colaboradores'],
                                        'level' => $this->getDimensionRiskLevel('relacion_colaboradores', $dimensions['relacion_colaboradores'])
                                    ]
                                ]
                            ],
                            'violence' => [
                                'name' => 'Violencia',
                                'score' => $dimensions['violencia_laboral'],
                                'level' => $this->getDomainRiskLevel('violence', $dimensions['violencia_laboral']),
                                'dimensions' => [
                                    [
                                        'name' => 'Violencia laboral',
                                        'score' => $dimensions['violencia_laboral'],
                                        'level' => $this->getDimensionRiskLevel('violencia_laboral', $dimensions['violencia_laboral'])
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                return [
                    'users' => $user,
                    'user_id' => $userId,
                    'empresa' => auth()->user()->sede->company_name??'No definido',
                    'nombre' => $user->name . ' ' . $user->first_name . ' ' . $user->last_name,
                    'fecha' => \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM, YYYY'),
                    'fecha_aplicacion' => $items->first()->created_at->locale('es')->isoFormat('D [de] MMMM, YYYY'),
                    'puesto' => $user->position->name ?? 'No definido',
                    'responses' => $responses,
                    'categories' => $categoriesWithLevels,
                    'domains' => $domainsWithLevels,
                    'dimensions' => $dimensionsWithLevels,
                    'total_score' => array_sum($responses),
                    'risk_level' => $this->getTotalRiskLevel(array_sum($responses)), // Placeholder, puedes calcularlo si es necesario
                    'recommendation' => $recomendaciones[$this->getTotalRiskLevel(array_sum($responses))] ?? 'No se encontró recomendación para este nivel de riesgo.',
                    'structure' => $structure,
                ];
            })->toArray();
        // Renderizar la vista con los datos

        $html = view('filament.pages.nom35.risk_factor_individual_report', [
            'users' => $users,
            'guia'=>'II',
            'guiaName'=>'IDENTIFICACIÓN DE LOS FACTORES DE RIESGO PSICOSOCIAL EN LOS CENTROS DE TRABAJO'
        ])->render();


        // Forzar codificación UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        $payload = [
            'source'    => $html,
            'landscape' => false,
            'use_print' => false,
            'margin'    => [
                'top'    => 10,
                'bottom' => 10,
                'left'   => 10,
                'right'  => 10,
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-Key'    => config('services.pdfshift.api_key'),
        ])
            ->timeout(120)
            ->retry(3, 1000)
            ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
            ->post('https://api.pdfshift.io/v3/convert/pdf');

        if ($response->successful()) {
            $pdfContent = $response->body();
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            },'ReporteIndividual.pdf');
        } else {
            Notification::make()
                ->title('Error al generar PDF')
                ->body('No se pudo generar el PDF: ' . $response->body())
                ->danger()
                ->send();
        }
    }

    public function reportIndividualGIIIDownload(){
        $domainNames = [
            'conditions' => 'Condiciones del ambiente de trabajo',
            'work_activity' => 'Carga de Trabajo',
            'work_control' => 'Falta de control sobre el trabajo',
            'work_journey' => 'Jornada de trabajo',
            'work_family' => 'Interferencia en la relación trabajo-familia',
            'leadership' => 'Liderazgo',
            'work_relations' => 'Relaciones en el trabajo',
            'violence' => 'Violencia',
            'reconocimiento' => 'Reconocimiento del desempeño',
            'pertenencia'=>'Insuficiente sentido de pertenencia e, inestabilidad'
        ];

        $categoryNames = [
            'ambiente' => 'Ambiente de trabajo',
            'activity' => 'Factores propios de la actividad',
            'time' => 'Organización del tiempo de trabajo',
            'leadership' => 'Liderazgo y relaciones en el trabajo',
            'entorno'=> 'Entorno organizacional ',
        ];

        $dimensionNames = [
            'condiciones_peligrosas' => 'Condiciones peligrosas e inseguras',
            'condiciones_deficientes' => 'Condiciones deficientes e insalubres',
            'trabajos_peligrosos' => 'Trabajos peligrosos',
            'cargas_cuantitativas' => 'Cargas cuantitativas',
            'ritmos_acelerados' => 'Ritmos de trabajo acelerado',
            'carga_mental' => 'Carga mental',
            'cargas_psicologicas' => 'Cargas psicológicas emocionales',
            'alta_responsabilidad' => 'Cargas de alta responsabilidad',
            'cargas_contradictorias' => 'Cargas contradictorias o inconsistentes',
            'falta_control' => 'Falta de control y autonomía sobre el trabajo',
            'posibilidad_desarrollo' => 'Limitada o nula posibilidad de desarrollo',
            'cambio'=>'Insuficiente participación y manejo del cambio',
            'capacitacion' => 'Limitada o inexistente capacitación',
            'jornadas_extensas' => 'Jornadas de trabajo extensas',
            'influencia_fuera_trabajo' => 'Influencia del trabajo fuera del centro laboral',
            'responsabilidades_familiares' => 'Influencia de las responsabilidades familiares ',
            'claridad_funciones' => 'Escasa claridad de funciones',
            'liderazgo' => 'Características del liderazgo ',
            'relaciones_sociales' => 'Relaciones sociales en el trabajo',
            'relacion_colaboradores' => 'Deficiente relación con los colaboradores que supervisa',
            'violencia_laboral' => 'Violencia laboral',
            'retroalimentacion' => 'Escasa o nula retroalimentación del desempeño ',
            'compensacion' => 'Escaso o nulo reconocimiento y compensación',
            'pertenencia' => 'Limitado sentido de pertenencia',
            'inestabilidad' => 'Inestabilidad laboral',
        ];

        $recomendaciones=[
            'Muy Alto' =>'Se requiere realizar el análisis de cada categoría y dominio para establecer las acciones de intervención apropiadas, mediante un Programa de intervención que deberá incluir evaluaciones específicas1, y contemplar campañas de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Alto' => 'Se requiere realizar un análisis de cada categoría y dominio, de manera que se puedan determinar las acciones de intervención apropiadas a través de un Programa de intervención, que podrá incluir una evaluación específica y deberá incluir una campaña de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Medio' => 'Se requiere revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión, mediante un Programa de intervención.',
            'Bajo' => 'Es necesario una mayor difusión de la política de prevención de riesgos psicosociales y programas para: la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral. ',
            'Nulo' => 'El riesgo resulta despreciable por lo que no se requiere medidas adicionales.'
        ];

        // Traer todos los usuarios que respondieron la encuesta de la guía II
        $users = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(function ($items, $userId) use ($domainNames, $categoryNames, $dimensionNames,$recomendaciones) {
                $user = $items->first()->user;
                $responses = $items->mapWithKeys(function ($item) {
                    return [$item->question->order => $item->equivalence_response];
                })->toArray();

                // Calcular las dimensiones
                $dimensions = [
                    'condiciones_peligrosas' => array_sum(array_intersect_key($responses, array_flip([1,3]))),
                    'condiciones_deficientes' => array_sum(array_intersect_key($responses, array_flip([2,4]))),
                    'trabajos_peligrosos' => array_sum(array_intersect_key($responses, array_flip([5]))),
                    'cargas_cuantitativas' => array_sum(array_intersect_key($responses, array_flip([6,12]))),
                    'ritmos_acelerados' => array_sum(array_intersect_key($responses, array_flip([7,8]))),
                    'carga_mental' => array_sum(array_intersect_key($responses, array_flip([9,10,11]))),
                    'cargas_psicologicas' => array_sum(array_intersect_key($responses, array_flip([66,67,68,69]))),
                    'alta_responsabilidad' => array_sum(array_intersect_key($responses, array_flip([13,14]))),
                    'cargas_contradictorias' => array_sum(array_intersect_key($responses, array_flip([15,16]))),
                    'falta_control' => array_sum(array_intersect_key($responses, array_flip([25,26,27,28]))),
                    'posibilidad_desarrollo' => array_sum(array_intersect_key($responses, array_flip([23,24]))),
                    'cambio'=>array_sum(array_intersect_key($responses, array_flip([29,30]))),
                    'capacitacion' => array_sum(array_intersect_key($responses, array_flip([35,36]))),
                    'jornadas_extensas' => array_sum(array_intersect_key($responses, array_flip([17,18]))),
                    'influencia_fuera_trabajo' => array_sum(array_intersect_key($responses, array_flip([19,20]))),
                    'responsabilidades_familiares' => array_sum(array_intersect_key($responses, array_flip([21,22]))),
                    'claridad_funciones' => array_sum(array_intersect_key($responses, array_flip([31,32,33,34]))),
                    'liderazgo' => array_sum(array_intersect_key($responses, array_flip([37,38,39,40,41]))),
                    'relaciones_sociales' => array_sum(array_intersect_key($responses, array_flip([42,43,44,45,46]))),
                    'relacion_colaboradores' => array_sum(array_intersect_key($responses, array_flip([71,72,73,74]))),
                    'violencia_laboral' => array_sum(array_intersect_key($responses, array_flip([57,58,59,60,61,62,63,64]))),
                    'retroalimentacion' => array_sum(array_intersect_key($responses, array_flip([47,48]))),
                    'compensacion' => array_sum(array_intersect_key($responses, array_flip([49,50,51,52]))),
                    'pertenencia' => array_sum(array_intersect_key($responses, array_flip([55,56]))),
                    'inestabilidad' => array_sum(array_intersect_key($responses, array_flip([53,54]))),
                ];

                // Calcular los dominios
                $domains = [
                    'conditions' => array_sum(array_intersect_key($responses, array_flip([1,2,3,4,5]))),
                    'work_activity' => array_sum(array_intersect_key($responses, array_flip([6,12,7,8,9,10,11,66,67,68,69,13,14,15,16]))),
                    'work_control' => array_sum(array_intersect_key($responses, array_flip([25,26,27,28,23,24,29,30,35,36]))),
                    'work_journey' => array_sum(array_intersect_key($responses, array_flip([17,18]))),
                    'work_family' => array_sum(array_intersect_key($responses, array_flip([19,20,21,22]))),
                    'leadership' => array_sum(array_intersect_key($responses, array_flip([31,32,33,34,37,38,39,40,41]))),
                    'work_relations' => array_sum(array_intersect_key($responses, array_flip([42,43,44,45,46,71,72,73,74]))),
                    'violence' => array_sum(array_intersect_key($responses, array_flip([57,58,59,60,61,62,63,64]))),
                    'reconocimiento' => array_sum(array_intersect_key($responses, array_flip([47,48,49,50,51,52]))),
                    'pertenencia'=>array_sum(array_intersect_key($responses, array_flip([55,56,53,54]))),
                ];

                // Calcular categorías
                $categories = [
                    'ambiente' => array_sum(array_intersect_key($responses, array_flip([1,2,3,4,5]))),
                    'activity' => $domains['work_activity']+$domains['work_control'],
                    'time' => $domains['work_journey']+$domains['work_family'],
                    'leadership' => $domains['leadership']+$domains['work_relations']+$domains['violence'],
                    'entorno'=> $domains['reconocimiento']+$domains['pertenencia']
                ];

                // Función para determinar nivel de riesgo
                $getRiskLevel = function($score, $thresholds) {
                    if ($score < $thresholds[0]) return 'Nulo o despreciable';
                    if ($score < $thresholds[1]) return 'Bajo';
                    if ($score < $thresholds[2]) return 'Medio';
                    if ($score < $thresholds[3]) return 'Alto';
                    return 'Muy alto';
                };

                // Agregar niveles de riesgo para cada dimensión
                $dimensionsWithLevels = [];
                foreach ($dimensions as $key => $value) {
                    $dimensionsWithLevels[$key] = [
                        'score' => $value,
                        'name' => $dimensionNames[$key],
                        'level' => $this->getDimensionRiskLevelG3($key, $value)
                    ];
                }
                $domainsWithLevels = [];
                foreach ($domains as $key => $value) {
                    $domainsWithLevels[$key] = [
                        'score' => $value,
                        'name' => $domainNames[$key],
                        'level' => $this->getDomainRiskLevelG3($key, $value)
                    ];
                }
                // Agregar niveles de riesgo para cada categoría
                $categoriesWithLevels = [];
                foreach ($categories as $key => $value) {
                    $categoriesWithLevels[$key] = [
                        'score' => $value,
                        'name' => $categoryNames[$key],
                        'level' => $this->getCategoryRiskLevelG3($key, $value)

                    ];
                }
                // Estructura jerárquica: Categorías -> Dominios -> Dimensiones
                $structure = [
                    'ambiente' => [
                        'name' => 'Ambiente de trabajo',
                        'score' => $dimensions['condiciones_peligrosas'] + $dimensions['condiciones_deficientes'] + $dimensions['trabajos_peligrosos'],
                        'level' => $this->getCategoryRiskLevelG3('ambiente', $dimensions['condiciones_peligrosas'] + $dimensions['condiciones_deficientes'] + $dimensions['trabajos_peligrosos']),
                        'domains' => [
                            'conditions' => [
                                'name' => 'Condiciones en el ambiente de trabajo',
                                'score' => $dimensions['condiciones_peligrosas'] + $dimensions['condiciones_deficientes'] + $dimensions['trabajos_peligrosos'],
                                'level' => $this->getDomainRiskLevelG3('conditions', $dimensions['condiciones_peligrosas'] + $dimensions['condiciones_deficientes'] + $dimensions['trabajos_peligrosos']),
                                'dimensions' => [
                                    [
                                        'name' => 'Condiciones peligrosas e inseguras',
                                        'score' => $dimensions['condiciones_peligrosas'],
                                        'level' => $this->getDimensionRiskLevelG3('condiciones_peligrosas', $dimensions['condiciones_peligrosas'])
                                    ],
                                    [
                                        'name' => 'Condiciones deficientes e insalubres',
                                        'score' => $dimensions['condiciones_deficientes'],
                                        'level' => $this->getDimensionRiskLevelG3('condiciones_deficientes', $dimensions['condiciones_deficientes'])
                                    ],
                                    [
                                        'name' => 'Trabajos peligrosos',
                                        'score' => $dimensions['trabajos_peligrosos'],
                                        'level' => $this->getDimensionRiskLevelG3('trabajos_peligrosos', $dimensions['trabajos_peligrosos'])
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'activity' => [
                        'name' => 'Factores propios de la actividad',
                        'score' => array_sum(array_slice($dimensions, 3, 9)), // Suma de todas las dimensiones de actividad
                        'level' => $this->getCategoryRiskLevelG3('activity', array_sum(array_slice($dimensions, 3, 9))),
                        'domains' => [
                            'work_activity' => [
                                'name' => 'Carga de trabajo',
                                'score' => $dimensions['cargas_cuantitativas'] + $dimensions['ritmos_acelerados'] + $dimensions['carga_mental'] + $dimensions['cargas_psicologicas'] + $dimensions['alta_responsabilidad'] + $dimensions['cargas_contradictorias'],
                                'level' => $this->getDomainRiskLevelG3('work_activity', $dimensions['cargas_cuantitativas'] + $dimensions['ritmos_acelerados'] + $dimensions['carga_mental'] + $dimensions['cargas_psicologicas'] + $dimensions['alta_responsabilidad'] + $dimensions['cargas_contradictorias']),
                                'dimensions' => [
                                    [
                                        'name' => 'Cargas cuantitativas',
                                        'score' => $dimensions['cargas_cuantitativas'],
                                        'level' => $this->getDimensionRiskLevelG3('cargas_cuantitativas', $dimensions['cargas_cuantitativas'])
                                    ],
                                    [
                                        'name' => 'Ritmos de trabajo acelerado',
                                        'score' => $dimensions['ritmos_acelerados'],
                                        'level' => $this->getDimensionRiskLevelG3('ritmos_acelerados', $dimensions['ritmos_acelerados'])
                                    ],
                                    [
                                        'name' => 'Carga mental',
                                        'score' => $dimensions['carga_mental'],
                                        'level' => $this->getDimensionRiskLevelG3('carga_mental', $dimensions['carga_mental'])
                                    ],
                                    [
                                        'name' => 'Cargas psicológicas emocionales',
                                        'score' => $dimensions['cargas_psicologicas'],
                                        'level' => $this->getDimensionRiskLevelG3('cargas_psicologicas', $dimensions['cargas_psicologicas'])
                                    ],
                                    [
                                        'name' => 'Cargas de alta responsabilidad',
                                        'score' => $dimensions['alta_responsabilidad'],
                                        'level' => $this->getDimensionRiskLevelG3('alta_responsabilidad', $dimensions['alta_responsabilidad'])
                                    ],
                                    [
                                        'name' => 'Cargas contradictorias o inconsistentes',
                                        'score' => $dimensions['cargas_contradictorias'],
                                        'level' => $this->getDimensionRiskLevelG3('cargas_contradictorias', $dimensions['cargas_contradictorias'])
                                    ]
                                ]
                            ],
                            'work_control' => [
                                'name' => 'Falta de control sobre el trabajo',
                                'score' => $dimensions['falta_control'] + $dimensions['posibilidad_desarrollo']+ $dimensions['cambio'] + $dimensions['capacitacion'],
                                'level' => $this->getDomainRiskLevelG3('work_control', $dimensions['falta_control'] + $dimensions['posibilidad_desarrollo']+ $dimensions['cambio'] + $dimensions['capacitacion']),
                                'dimensions' => [
                                    [
                                        'name' => 'Falta de control y autonomía sobre el trabajo',
                                        'score' => $dimensions['falta_control'],
                                        'level' => $this->getDimensionRiskLevelG3('falta_control', $dimensions['falta_control'])
                                    ],
                                    [
                                        'name' => 'Limitada o nula posibilidad de desarrollo',
                                        'score' => $dimensions['posibilidad_desarrollo'],
                                        'level' => $this->getDimensionRiskLevelG3('posibilidad_desarrollo', $dimensions['posibilidad_desarrollo'])
                                    ],
                                    [
                                        'name'=>'Insuficiente participación y manejo del cambio ',
                                        'score' => $dimensions['cambio'],
                                        'level' => $this->getDimensionRiskLevelG3('cambio', $dimensions['cambio'])
                                    ],
                                    [
                                        'name' => 'Limitada o inexistente capacitación',
                                        'score' => $dimensions['capacitacion'],
                                        'level' => $this->getDimensionRiskLevelG3('capacitacion', $dimensions['capacitacion'])
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'time' => [
                        'name' => 'Organización del tiempo de trabajo',
                        'score' => $dimensions['jornadas_extensas'] + $dimensions['influencia_fuera_trabajo'] + $dimensions['responsabilidades_familiares'],
                        'level' => $this->getCategoryRiskLevelG3('time', $dimensions['jornadas_extensas'] + $dimensions['influencia_fuera_trabajo'] + $dimensions['responsabilidades_familiares']),
                        'domains' => [
                            'work_journey' => [
                                'name' => 'Jornada de trabajo',
                                'score' => $dimensions['jornadas_extensas'],
                                'level' => $this->getDomainRiskLevelG3('work_journey', $dimensions['jornadas_extensas']),
                                'dimensions' => [
                                    [
                                        'name' => 'Jornadas de trabajo extensas',
                                        'score' => $dimensions['jornadas_extensas'],
                                        'level' => $this->getDimensionRiskLevelG3('jornadas_extensas', $dimensions['jornadas_extensas'])
                                    ]
                                ]
                            ],
                            'work_family' => [
                                'name' => 'Interferencia en la relación trabajo-familia',
                                'score' => $dimensions['influencia_fuera_trabajo'] + $dimensions['responsabilidades_familiares'],
                                'level' => $this->getDomainRiskLevelG3('work_family', $dimensions['influencia_fuera_trabajo'] + $dimensions['responsabilidades_familiares']),
                                'dimensions' => [
                                    [
                                        'name' => 'Influencia del trabajo fuera del centro laboral',
                                        'score' => $dimensions['influencia_fuera_trabajo'],
                                        'level' => $this->getDimensionRiskLevelG3('influencia_fuera_trabajo', $dimensions['influencia_fuera_trabajo'])
                                    ],
                                    [
                                        'name' => 'Influencia de las responsabilidades familiares',
                                        'score' => $dimensions['responsabilidades_familiares'],
                                        'level' => $this->getDimensionRiskLevelG3('responsabilidades_familiares', $dimensions['responsabilidades_familiares'])
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'leadership' => [
                        'name' => 'Liderazgo y relaciones en el trabajo',
                        'score' => $dimensions['claridad_funciones'] + $dimensions['liderazgo'] + $dimensions['relaciones_sociales'] + $dimensions['relacion_colaboradores'] + $dimensions['violencia_laboral'],
                        'level' => $this->getCategoryRiskLevelG3('leadership', $dimensions['claridad_funciones'] + $dimensions['liderazgo'] + $dimensions['relaciones_sociales'] + $dimensions['relacion_colaboradores'] + $dimensions['violencia_laboral']),
                        'domains' => [
                            'leadership' => [
                                'name' => 'Liderazgo',
                                'score' => $dimensions['claridad_funciones'] + $dimensions['liderazgo'],
                                'level' => $this->getDomainRiskLevelG3('leadership', $dimensions['claridad_funciones'] + $dimensions['liderazgo']),
                                'dimensions' => [
                                    [
                                        'name' => 'Escasa claridad de funciones',
                                        'score' => $dimensions['claridad_funciones'],
                                        'level' => $this->getDimensionRiskLevelG3('claridad_funciones', $dimensions['claridad_funciones'])
                                    ],
                                    [
                                        'name' => 'Características del liderazgo',
                                        'score' => $dimensions['liderazgo'],
                                        'level' => $this->getDimensionRiskLevelG3('liderazgo', $dimensions['liderazgo'])
                                    ]
                                ]
                            ],
                            'work_relations' => [
                                'name' => 'Relaciones en el trabajo',
                                'score' => $dimensions['relaciones_sociales'] + $dimensions['relacion_colaboradores'],
                                'level' => $this->getDomainRiskLevelG3('work_relations', $dimensions['relaciones_sociales'] + $dimensions['relacion_colaboradores']),
                                'dimensions' => [
                                    [
                                        'name' => 'Relaciones sociales en el trabajo',
                                        'score' => $dimensions['relaciones_sociales'],
                                        'level' => $this->getDimensionRiskLevelG3('relaciones_sociales', $dimensions['relaciones_sociales'])
                                    ],
                                    [
                                        'name' => 'Deficiente relación con los colaboradores que supervisa',
                                        'score' => $dimensions['relacion_colaboradores'],
                                        'level' => $this->getDimensionRiskLevelG3('relacion_colaboradores', $dimensions['relacion_colaboradores'])
                                    ]
                                ]
                            ],
                            'violence' => [
                                'name' => 'Violencia',
                                'score' => $dimensions['violencia_laboral'],
                                'level' => $this->getDomainRiskLevelG3('violence', $dimensions['violencia_laboral']),
                                'dimensions' => [
                                    [
                                        'name' => 'Violencia laboral',
                                        'score' => $dimensions['violencia_laboral'],
                                        'level' => $this->getDimensionRiskLevelG3('violencia_laboral', $dimensions['violencia_laboral'])
                                    ]
                                ]
                            ]
                        ],
                    ],
                    'entorno'=> [
                        'name' => 'Entorno Organizacional',
                        'score' => $dimensions['retroalimentacion']+ $dimensions['compensacion']+$dimensions['pertenencia']+ $dimensions['inestabilidad'],
                        'level' => $this->getCategoryRiskLevelG3('entorno', $dimensions['retroalimentacion']+ $dimensions['compensacion']+$dimensions['pertenencia']+ $dimensions['inestabilidad']),
                        'domains' => [
                            'reconocimiento' => [
                                'name' => 'Reconocimiento del desempeño',
                                'score' => $dimensions['retroalimentacion']+ $dimensions['compensacion'],
                                'level' => $this->getDomainRiskLevelG3('reconocimiento', $dimensions['retroalimentacion']+ $dimensions['compensacion']),
                                'dimensions' => [
                                    [
                                        'name'=>'Escasa o nula retroalimentación del desempeño ',
                                        'score' => $dimensions['retroalimentacion'],
                                        'level' => $this->getDimensionRiskLevelG3('retroalimentacion', $dimensions['retroalimentacion'])
                                    ],
                                    [
                                        'name'=>'Escaso o nulo reconocimiento y compensación',
                                        'score' => $dimensions['compensacion'],
                                        'level' => $this->getDimensionRiskLevelG3('compensacion', $dimensions['compensacion'])
                                    ]
                                ],
                            ],
                            'pertenencia'=>[
                                'name' => 'Insuficiente sentido de pertenencia e, inestabilidad',
                                'score' => $dimensions['pertenencia']+ $dimensions['inestabilidad'],
                                'level' => $this->getDomainRiskLevelG3('pertenencia', $dimensions['pertenencia']+ $dimensions['inestabilidad']),
                                'dimensions' => [
                                    [
                                        'name'=>'Limitado sentido de pertenencia',
                                        'score' => $dimensions['pertenencia'],
                                        'level' => $this->getDimensionRiskLevelG3('pertenencia', $dimensions['pertenencia'])
                                    ],
                                    [
                                        'name'=>'Inestabilidad laboral',
                                        'score' => $dimensions['inestabilidad'],
                                        'level' => $this->getDimensionRiskLevelG3('inestabilidad', $dimensions['inestabilidad'])
                                    ]
                                ]
                            ]
                        ]
                   ]
                ];
                //$user=auth()->user();
                $sedeId=$user->sede_id;
                $campaign = Campaign::whereHas('sedes', function ($query) use ($sedeId) {
                    $query->where('sedes.id', $sedeId);
                })
                    ->whereHas('evaluations', function ($query) {
                        $query->whereIn('evaluations_types.id', [6, 7, 8]);
                    })
                    ->latest()
                    ->first();
                $startDate=$campaign->start_date??now();
                $endDate=$campaign->end_date??now();
                return [
                    'users' => $user,
                    'user_id' => $userId,
                    'empresa' => auth()->user()->sede->company_name??'No definido',
                    'nombre' => $user->name . ' ' . $user->first_name . ' ' . $user->last_name,
                    'fecha' => \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM, YYYY'),
                    'fecha_aplicacion' => \Carbon\Carbon::parse($startDate)->locale('es')->isoFormat('D [de] MMMM, YYYY').' al '.\Carbon\Carbon::parse($endDate)->locale('es')->isoFormat('D [de] MMMM, YYYY'),
                    'puesto' => $user->position->name ?? 'No definido',
                    'responses' => $responses,
                    'categories' => $categoriesWithLevels,
                    'domains' => $domainsWithLevels,
                    'dimensions' => $dimensionsWithLevels,
                    'total_score' => array_sum($responses),
                    'risk_level' => $this->getTotalRiskLevelG3(array_sum($responses)), // Placeholder, puedes calcularlo si es necesario
                    'recommendation' => $recomendaciones[$this->getTotalRiskLevelG3(array_sum($responses))] ?? 'No se encontró recomendación para este nivel de riesgo.',
                    'structure' => $structure,
                ];

            })->toArray();

        // Renderizar la vista con los datos

        $html = view('filament.pages.nom35.risk_factor_individual_report', [
            'users' => $users,
            'guia'=>'III',
            'guiaName'=>'IDENTIFICACIÓN Y ANÁLISIS DE LOS FACTORES DE RIESGO PSICOSOCIAL Y EVALUACIÓN DEL ENTORNO ORGANIZACIONAL EN LOS CENTROS DE TRABAJO'
        ])->render();


        // Forzar codificación UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        $payload = [
            'source'    => $html,
            'landscape' => false,
            'use_print' => false,
            'margin'    => [
                'top'    => 10,
                'bottom' => 10,
                'left'   => 10,
                'right'  => 10,
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-Key'    => config('services.pdfshift.api_key'),
        ])
            ->timeout(120)
            ->retry(3, 1000)
            ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
            ->post('https://api.pdfshift.io/v3/convert/pdf');

        if ($response->successful()) {
            $pdfContent = $response->body();
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            },'ReporteIndividualG3.pdf');
        } else {
            Notification::make()
                ->title('Error al generar PDF')
                ->body('No se pudo generar el PDF: ' . $response->body())
                ->danger()
                ->send();
        }
    }

// Método auxiliar para determinar el nivel de riesgo de las dimensiones
    private function getDimensionRiskLevel($dimension, $score)
    {
        $thresholds = [
            'condiciones_peligrosas' => [0, 2, 4, 6],
            'condiciones_deficientes' => [0, 2, 4, 6],
            'trabajos_peligrosos' => [0, 1, 2, 3],
            'cargas_cuantitativas' => [0, 2, 4, 6],
            'ritmos_acelerados' => [0, 2, 4, 6],
            'carga_mental' => [0, 3, 6, 9],
            'cargas_psicologicas' => [0, 3, 6, 9],
            'alta_responsabilidad' => [0, 2, 4, 6],
            'cargas_contradictorias' => [0, 2, 4, 6],
            'falta_control' => [0, 3, 6, 9],
            'posibilidad_desarrollo' => [0, 2, 4, 6],
            'capacitacion' => [0, 2, 4, 6],
            'jornadas_extensas' => [0, 1, 2, 3],
            'influencia_fuera_trabajo' => [0, 1, 2, 3],
            'responsabilidades_familiares' => [0, 1, 2, 3],
            'claridad_funciones' => [0, 3, 6, 9],
            'liderazgo' => [0, 2, 4, 6],
            'relaciones_sociales' => [0, 3, 6, 9],
            'relacion_colaboradores' => [0, 3, 6, 9],
            'violencia_laboral' => [0, 7, 14, 21],
        ];

        $levels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy alto'];
        $dimensionThresholds = $thresholds[$dimension] ?? [0, 1, 2, 3];

        // Verificar cada nivel en orden
        if ($score < $dimensionThresholds[0]) return $levels[0];  //Nulo
        if ($score >= $dimensionThresholds[0] && $score < $dimensionThresholds[1]) return $levels[1]; //Bajo
        if ($score >= $dimensionThresholds[1] && $score < $dimensionThresholds[2]) return $levels[2]; //Medio
        if ($score >= $dimensionThresholds[2] && $score < $dimensionThresholds[3]) return $levels[3]; //Alto

        return $levels[4]; //Muy Alto
    }
    private function getDimensionRiskLevelG3 ($dimension, $score)
    {
  // [0, 3, 6, 9] 3 items
  //[0, 1, 2, 3] 1 item
        $thresholds = [
            'condiciones_peligrosas' => [0, 2, 4, 6],
            'condiciones_deficientes' => [0, 2, 4, 6],
            'trabajos_peligrosos' => [0, 1, 2, 3],
            'cargas_cuantitativas' => [0, 2, 4, 6],
            'ritmos_acelerados' => [0, 2, 4, 6],
            'carga_mental' => [0, 3, 6, 9],
            'cargas_psicologicas' => [0, 4, 8, 12], //4 items max 16 *.25 *.50 *.75
            'alta_responsabilidad' => [0, 2, 4, 6],
            'cargas_contradictorias' => [0, 2, 4, 6],
            'falta_control' => [0, 4, 8, 12],
            'posibilidad_desarrollo' => [0, 2, 4, 6],
            'cambio' => [0, 2, 4, 6],
            'capacitacion' => [0, 2, 4, 6],
            'jornadas_extensas' => [0, 2, 4, 6],
            'influencia_fuera_trabajo' => [0, 2, 4, 6],
            'responsabilidades_familiares' => [0, 2, 4, 6],
            'claridad_funciones' => [0, 4, 8, 12],
            'liderazgo' => [0,5,10,15],
            'relaciones_sociales' => [0,5,10,15],
            'relacion_colaboradores' => [0, 4, 8, 12],
            'violencia_laboral' => [0, 8, 16, 24],
            'retroalimentacion' => [0, 2, 4, 6],
            'compensacion' => [0, 4, 8, 12],
            'pertenencia' => [0, 2, 4, 6],
            'inestabilidad' => [0, 2, 4, 6]
        ];

        $levels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy alto'];
        $dimensionThresholds = $thresholds[$dimension] ?? [0, 1, 2, 3];

        // Verificar cada nivel en orden
        if ($score < $dimensionThresholds[0]) return $levels[0];  //Nulo
        if ($score >= $dimensionThresholds[0] && $score <$dimensionThresholds[1]) return $levels[1]; //Bajo
        if ($score >= $dimensionThresholds[1] && $score < $dimensionThresholds[2]) return $levels[2]; //Medio
        if ($score >= $dimensionThresholds[2] && $score < $dimensionThresholds[3]) return $levels[3]; //Alto

        return $levels[4]; //Muy Alto
    }
    private function getDomainRiskLevel($domain, $score)
    {
        $thresholds = [
            'conditions' => [3, 5, 7, 9],
            'work_activity' => [12, 16, 20, 24],
            'work_control' => [5, 8, 11, 14],
            'work_journey' => [1, 2, 4, 6],
            'work_family' => [1, 2, 4, 6],
            'leadership' => [3, 5, 8, 11],
            'work_relations' => [5, 8, 11, 14],
            'violence' => [7, 10, 13, 16],
        ];

        $levels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy alto'];
        $domainThresholds = $thresholds[$domain] ?? [1, 2, 3, 4];

        if ($score < $domainThresholds[0]) return $levels[0];
        if ($score >= $domainThresholds[0] && $score < $domainThresholds[1]) return $levels[1];
        if ($score >= $domainThresholds[1] && $score < $domainThresholds[2]) return $levels[2];
        if ($score >= $domainThresholds[2] && $score < $domainThresholds[3]) return $levels[3];

        return $levels[4];
    }

    private function getDomainRiskLevelG3($domain, $score)
    {
        $thresholds = [
            'conditions' => [5, 9, 11, 14],
            'work_activity' => [15, 21, 27, 37],
            'work_control' => [11, 16, 21, 25],
            'work_journey' => [1, 2, 4, 6],
            'work_family' => [4, 6, 8, 10],
            'leadership' => [9, 12, 16, 20],
            'work_relations' => [10, 13, 17, 21],
            'violence' => [7, 10, 13, 16],
            'reconocimiento'=>[6,10,14,18],
            'pertenencia'=>[4,6,8,10]
        ];

        $levels = ['Nulo', 'Bajo', 'Medio', 'Alto', 'Muy alto'];
        $domainThresholds = $thresholds[$domain] ?? [1, 2, 3, 4];

        if ($score < $domainThresholds[0]) return $levels[0];
        if ($score >= $domainThresholds[0] && $score < $domainThresholds[1]) return $levels[1];
        if ($score >= $domainThresholds[1] && $score < $domainThresholds[2]) return $levels[2];
        if ($score >= $domainThresholds[2] && $score < $domainThresholds[3]) return $levels[3];

        return $levels[4];
    }
    private function getCategoryRiskLevel($category, $score, bool $inFlag=false)
    {

        if ($inFlag){
            $thresholds = [
                'ambiente' => [3, 5, 7, 9],
                'actividad' => [10, 20, 30, 40],
                'tiempo' => [4, 6, 9, 12],
                'liderazgo' => [10, 18, 28, 38],
            ];
        }else{
            $thresholds = [
                'ambiente' => [3, 5, 7, 9],
                'activity' => [10, 20, 30, 40],
                'time' => [4, 6, 9, 12],
                'leadership' => [10, 18, 28, 38],
            ];
        }

        $levels = ['Nulo o despreciable', 'Bajo', 'Medio', 'Alto', 'Muy alto'];
        $categoryThresholds = $thresholds[$category] ?? [1, 2, 3, 4];

        if ($score < $categoryThresholds[0]) return $levels[0];
        if ($score >= $categoryThresholds[0] && $score < $categoryThresholds[1]) return $levels[1];
        if ($score >= $categoryThresholds[1] && $score < $categoryThresholds[2]) return $levels[2];
        if ($score >= $categoryThresholds[2] && $score < $categoryThresholds[3]) return $levels[3];

        return $levels[4];
    }
    private function getCategoryRiskLevelG3($category, $score)
    {

        $thresholds = [
            'ambiente' => [5,9,11,14],
            'activity' => [15,30,45,60],
            'time' => [5,7,10,13],
            'leadership' => [14,29,42,58],
            'entorno'=>[10,14,18,23]
        ];

        $levels = ['Nulo o despreciable', 'Bajo', 'Medio', 'Alto', 'Muy alto'];
        $categoryThresholds = $thresholds[$category] ?? [1, 2, 3, 4];

        if ($score < $categoryThresholds[0]) return $levels[0];
        if ($score >= $categoryThresholds[0] && $score < $categoryThresholds[1]) return $levels[1];
        if ($score >= $categoryThresholds[1] && $score < $categoryThresholds[2]) return $levels[2];
        if ($score >= $categoryThresholds[2] && $score < $categoryThresholds[3]) return $levels[3];

        return $levels[4];
    }
    private function getTotalRiskLevel($score)
    {
        if ($score < 20) {
            return 'Nulo';
        } elseif ($score >= 20 && $score < 45) {
            return 'Bajo';
        } elseif ($score >= 45 && $score < 70) {
            return 'Medio';
        } elseif ($score >= 70 && $score < 90) {
            return 'Alto';
        } elseif ($score >= 90) {
            return 'Muy Alto';
        }

        return 'N/A';
    }
    private function getTotalRiskLevelG3($score)
    {
        if ($score < 50) {
            return 'Nulo';
        } elseif ($score >= 50 && $score < 75) {
            return 'Bajo';
        } elseif ($score >= 75 && $score < 99) {
            return 'Medio';
        } elseif ($score >= 99 && $score < 140) {
            return 'Alto';
        } elseif ($score >= 140) {
            return 'Muy Alto';
        }

        return 'N/A';
    }

    public function reportCoverGII()
    {
        $recomendaciones = [
            'Muy Alto' =>'Se requiere realizar el análisis de cada categoría y dominio para establecer las acciones de intervención apropiadas, mediante un Programa de intervención que deberá incluir evaluaciones específicas1, y contemplar campañas de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Alto' => 'Se requiere realizar un análisis de cada categoría y dominio, de manera que se puedan determinar las acciones de intervención apropiadas a través de un Programa de intervención, que podrá incluir una evaluación específica y deberá incluir una campaña de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Medio' => 'Se requiere revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión, mediante un Programa de intervención.',
            'Bajo' => 'Es necesario una mayor difusión de la política de prevención de riesgos psicosociales y programas para: la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral. ',
            'Nulo' => 'El riesgo resulta despreciable por lo que no se requiere medidas adicionales.'
        ];

        //Quiero obtener el promedio de las respuestas de la guía II por categoría
        $categories = [
            'ambiente' => [
                'name' => 'Ambiente de trabajo',
                'result' => $this->coverAmbientResponses->avg(),
                'risk_level' => $this->getCategoryRiskLevel('ambiente', $this->coverAmbientResponses->avg()),
            ],
            'activity' => [
                'name' => 'Factores propios de la actividad',
                'result' => $this->coverActivityResponses->avg(),
                'risk_level' => $this->getCategoryRiskLevel('activity', $this->coverActivityResponses->avg()),
            ],
            'time' => [
                'name' => 'Organización del tiempo de trabajo',
                'result' => $this->coverTimeResponses->avg(),
                'risk_level' => $this->getCategoryRiskLevel('time', $this->coverTimeResponses->avg()),
            ],
            'leadership' => [
                'name' => 'Liderazgo y relaciones en el trabajo',
                'result' => $this->coverLeadershipResponses->avg(),
                'risk_level' => $this->getCategoryRiskLevel('leadership', $this->coverLeadershipResponses->avg()),
            ],
        ];

        $domains = [
            'conditions' => [
                'name' => 'Condiciones en el ambiente de trabajo',
                'result' => $this->coverConditionResponses->avg(),
                'risk_level' => $this->getDomainRiskLevel('conditions', $this->coverConditionResponses->avg()),
            ],
            'work_activity' => [
                'name' => 'Carga de trabajo',
                'result' => $this->coverWorkActivityResponses->avg(),
                'risk_level' => $this->getDomainRiskLevel('work_activity', $this->coverWorkActivityResponses->avg()),
            ],
            'work_control' => [
                'name' => 'Falta de control sobre el trabajo',
                'result' => $this->coverWorkControlResponses->avg(),
                'risk_level' => $this->getDomainRiskLevel('work_control', $this->coverWorkControlResponses->avg()),
            ],
            'work_journey' => [
                'name' => 'Jornada de trabajo',
                'result' => $this->coverWorkJourneyResponses->avg(),
                'risk_level' => $this->getDomainRiskLevel('work_journey', $this->coverWorkJourneyResponses->avg()),
            ],
            'work_family' => [
                'name' => 'Interferencia en la relación trabajo-familia',
                'result' => $this->coverWordAndFamilyResponses->avg(),
                'risk_level' => $this->getDomainRiskLevel('work_family', $this->coverWordAndFamilyResponses->avg()),
            ],
            'leadership' => [
                'name' => 'Liderazgo',
                'result' => $this->coverDomainLeadershipResponses->avg(),
                'risk_level' => $this->getDomainRiskLevel('leadership', $this->coverDomainLeadershipResponses->avg()),
            ],
            'work_relations' => [
                'name' => 'Relaciones en el trabajo',
                'result' => $this->coverWorkRelationsResponses->avg(),
                'risk_level' => $this->getDomainRiskLevel('work_relations', $this->coverWorkRelationsResponses->avg()),
            ],
            'violence' => [
                'name' => 'Violencia laboral',
                'result' => $this->coverViolenceResponses->avg(),
                'risk_level' => $this->getDomainRiskLevel('violence', $this->coverViolenceResponses->avg()),
            ],
        ];

        $user=auth()->user();
        $sedeId=$user->sede_id;
        $campaign = Campaign::whereHas('sedes', function ($query) use ($sedeId) {
            $query->where('sedes.id', $sedeId);
        })
            ->whereHas('evaluations', function ($query) {
                $query->whereIn('evaluations_types.id', [6, 7, 8]);
            })
            ->latest()
            ->first();
        $periodo=$campaign->end_date??now();

        $html=view('filament.pages.nom35.risk_factor_report_cover', [
            'company' => auth()->user()->sede->company_name ?? 'No definido', //OK
            'reportDate' => \Carbon\Carbon::parse($periodo)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'period' => $this->norma->start_date->locale('es')->isoFormat('D [de] MMMM, YYYY') . ' al ' . \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM, YYYY'),
            'responsesTotalG2' => $this->responsesTotalG2,
            'generalResults' => $this->generalResults,
            'calificacionG2' => $this->calificacion,
            'resultCuestionario' => $this->resultCuestionario,
            'categories'=>$categories,
            'domains'=> $domains,
            'guia'=>'II',
            'complement' => ''
            //'recommendations' =>$recomendaciones[$this->resultCuestionario==='Muy Alto'?'Muy-Alto':$this->resultCuestionario],
        ])->render();
        // Forzar codificación UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        $payload = [
            'source'    => $html,
            'landscape' => false,
            'use_print' => false,
            'margin'    => [
                'top'    => 10,
                'bottom' => 10,
                'left'   => 10,
                'right'  => 10,
            ],
        ];
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-Key'    => config('services.pdfshift.api_key'),
        ])
            ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
            ->post('https://api.pdfshift.io/v3/convert/pdf');
        if ($response->successful()) {
            $pdfContent = $response->body();
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            },'Caratula.pdf');
        } else {
            Notification::make()
                ->title('Error al generar PDF')
                ->body('No se pudo generar el PDF: ' . $response->body())
                ->danger()
                ->send();
        }
    }

    public function reportCoverGIII()
    {
        $recomendaciones = [
            'Muy Alto' =>'Se requiere realizar el análisis de cada categoría y dominio para establecer las acciones de intervención apropiadas, mediante un Programa de intervención que deberá incluir evaluaciones específicas1, y contemplar campañas de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Alto' => 'Se requiere realizar un análisis de cada categoría y dominio, de manera que se puedan determinar las acciones de intervención apropiadas a través de un Programa de intervención, que podrá incluir una evaluación específica y deberá incluir una campaña de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Medio' => 'Se requiere revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión, mediante un Programa de intervención.',
            'Bajo' => 'Es necesario una mayor difusión de la política de prevención de riesgos psicosociales y programas para: la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral. ',
            'Nulo' => 'El riesgo resulta despreciable por lo que no se requiere medidas adicionales.'
        ];

        //Quiero obtener el promedio de las respuestas de la guía II por categoría
        $categories = [
            'ambiente' => [
                'name' => 'Ambiente de trabajo',
                'result' => $this->coverAmbientResponses->avg(),
                'risk_level' => $this->getCategoryRiskLevelG3('ambiente', $this->coverAmbientResponses->avg()),
            ],
            'activity' => [
                'name' => 'Factores propios de la actividad',
                'result' => $this->coverActivityResponses->avg(),
                'risk_level' => $this->getCategoryRiskLevelG3('activity', $this->coverActivityResponses->avg()),
            ],
            'time' => [
                'name' => 'Organización del tiempo de trabajo',
                'result' => $this->coverTimeResponses->avg(),
                'risk_level' => $this->getCategoryRiskLevelG3('time', $this->coverTimeResponses->avg()),
            ],
            'leadership' => [
                'name' => 'Liderazgo y relaciones en el trabajo',
                'result' => $this->coverLeadershipResponses->avg(),
                'risk_level' => $this->getCategoryRiskLevelG3('leadership', $this->coverLeadershipResponses->avg()),
            ],
            'entorno'=>[
                'name' => 'Entorno organizacional',
                'result'=>$this->coverEntornoResponses->avg(),
                'risk_level'=>$this->getCategoryRiskLevelG3('entorno',$this->coverEntornoResponses->avg()),
            ]
        ];
        $domains = [
            'conditions' => [
                'name' => 'Condiciones en el ambiente de trabajo',
                'result' => $this->coverConditionResponses->avg(),
                'risk_level' => $this->getDomainRiskLevelG3('conditions', $this->coverConditionResponses->avg()),
            ],
            'work_activity' => [
                'name' => 'Carga de trabajo',
                'result' => $this->coverWorkActivityResponses->avg(),
                'risk_level' => $this->getDomainRiskLevelG3('work_activity', $this->coverWorkActivityResponses->avg()),
            ],
            'work_control' => [
                'name' => 'Falta de control sobre el trabajo',
                'result' => $this->coverWorkControlResponses->avg(),
                'risk_level' => $this->getDomainRiskLevelG3('work_control', $this->coverWorkControlResponses->avg()),
            ],
            'work_journey' => [
                'name' => 'Jornada de trabajo',
                'result' => $this->coverWorkJourneyResponses->avg(),
                'risk_level' => $this->getDomainRiskLevelG3('work_journey', $this->coverWorkJourneyResponses->avg()),
            ],
            'work_family' => [
                'name' => 'Interferencia en la relación trabajo-familia',
                'result' => $this->coverWordAndFamilyResponses->avg(),
                'risk_level' => $this->getDomainRiskLevelG3('work_family', $this->coverWordAndFamilyResponses->avg()),
            ],
            'leadership' => [
                'name' => 'Liderazgo',
                'result' => $this->coverDomainLeadershipResponses->avg(),
                'risk_level' => $this->getDomainRiskLevelG3('leadership', $this->coverDomainLeadershipResponses->avg()),
            ],
            'work_relations' => [
                'name' => 'Relaciones en el trabajo',
                'result' => $this->coverWorkRelationsResponses->avg(),
                'risk_level' => $this->getDomainRiskLevelG3('work_relations', $this->coverWorkRelationsResponses->avg()),
            ],
            'violence' => [
                'name' => 'Violencia laboral',
                'result' => $this->coverViolenceResponses->avg(),
                'risk_level' => $this->getDomainRiskLevelG3('violence', $this->coverViolenceResponses->avg()),
            ],
            'performance' =>[
                'name' => 'Reconocimiento del Desempeño',
                'result'=>$this->coverPerformanceResponses->avg(),
                'risk_level'=>$this->getDomainRiskLevelG3('reconocimiento',$this->coverPerformanceResponses->avg()),
            ],
            'inestable'=> [
                'name' => 'Insuficiente sentido de pertenencia e, inestabilidad ',
                'result'=>$this->coverInestableResponses->avg(),
                'risk_level'=>$this->getDomainRiskLevelG3('pertenencia',$this->coverInestableResponses->avg()),
            ]
        ];
        $user=auth()->user();
        $sedeId=$user->sede_id;
        $campaign = Campaign::whereHas('sedes', function ($query) use ($sedeId) {
            $query->where('sedes.id', $sedeId);
        })
            ->whereHas('evaluations', function ($query) {
                $query->whereIn('evaluations_types.id', [6, 7, 8]);
            })
            ->latest()
            ->first();
        $startDate=$campaign->start_date??now();
        $endDate=$campaign->end_date??now();

        $html=view('filament.pages.nom35.risk_factor_report_cover', [
            'company' => auth()->user()->sede->company_name ?? 'No definido', //OK
            'reportDate' => \Carbon\Carbon::parse($endDate)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'period' => \Carbon\Carbon::parse($startDate)->locale('es')->isoFormat('D [de] MMMM, YYYY'). ' al ' . \Carbon\Carbon::parse($endDate)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'responsesTotalG2' => $this->totalResponsesG3,
            'generalResults' => $this->generalResultsGuideIII,
            'calificacionG2' => $this->calificacionG3,
            'resultCuestionario' => $this->resultCuestionarioG3,
            'categories'=>$categories,
            'domains'=> $domains,
            'guia'=>'III',
            'complement' => 'y Entorno Organizacional'
            //'recommendations' =>$recomendaciones[$this->resultCuestionario==='Muy Alto'?'Muy-Alto':$this->resultCuestionario],
        ])->render();
        // Forzar codificación UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        $payload = [
            'source'    => $html,
            'landscape' => false,
            'use_print' => false,
            'margin'    => [
                'top'    => 10,
                'bottom' => 10,
                'left'   => 10,
                'right'  => 10,
            ],
        ];
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-Key'    => config('services.pdfshift.api_key'),
        ])
            ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
            ->post('https://api.pdfshift.io/v3/convert/pdf');
        if ($response->successful()) {
            $pdfContent = $response->body();
            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            },'CaratulaGuiaIII.pdf');
        } else {
            Notification::make()
                ->title('Error al generar PDF')
                ->body('No se pudo generar el PDF: ' . $response->body())
                ->danger()
                ->send();
        }
    }
    public function downloadNorma(){
        return response()->download(storage_path('app/documents/NORMA_Oficial_Mexicana_NOM-035-STPS-2018.pdf'),'NORMA_Oficial_Mexicana_NOM-035-STPS-2018.pdf');
    }
    public function downloadGuia(){
        return response()->download(storage_path('app/documents/Guia-NOM035.v2026.pdf'),'Guia_NOM035_STPS_2018.pdf');
    }

    public function sumaryResults(){
        try {
            $normaId = $this->norma->id;
            $sedeId = $this->getCurrentSedeId();


            if ($this->level === 2) {
                $userIds = RiskFactorSurvey::where('norma_id', $normaId)
                    ->where('sede_id', $sedeId)
                    ->with('user')
                    ->distinct()
                    ->pluck('user_id');

                $colaboradores = \App\Models\User::whereIn('id', $userIds)->get();
            } elseif ($this->level === 3) {
                $userIds = RiskFactorSurveyOrganizational::where('norma_id', $normaId)
                    ->where('sede_id', $sedeId)
                    ->distinct()
                    ->pluck('user_id');

                $colaboradores = \App\Models\User::whereIn('id', $userIds)->get();
            }else{
                $colaboradores = collect();
            }


            // Construir datos para el Excel
            $data = [];
            foreach ($colaboradores as $colab) {
                $score = 0;
                $determinacion = '';

                // Según el nivel, sumar respuestas
                if ($this->level === 2) { //Guía II
                    $riskSurvey = RiskFactorSurvey::where('norma_id', $normaId)
                        ->where('user_id', $colab->id)
                        ->sum('equivalence_response');

                    if ($riskSurvey) {
                        $score = $riskSurvey;
                        $determinacion = $this->getTotalRiskLevel($score);
                    }
                } elseif ($this->level === 3) { //Guía III
                    // Sumar ambas encuestas
                    $riskSurvey = RiskFactorSurveyOrganizational::where('norma_id', $normaId)
                        ->where('user_id', $colab->id)
                        ->sum('equivalence_response');

                    $score = $riskSurvey;
                    $determinacion = $this->getTotalRiskLevelG3($score);
                }

                $data[] = [
                    'nombre' => $colab->name . ' ' . $colab->first_name . ' ' . $colab->last_name,
                    'score' => $score,
                    'determinacion' => $determinacion
                ];
            }

            // Crear Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Headers
            $sheet->setCellValue('A1', 'Nombre');
            $sheet->setCellValue('B1', 'Score');
            $sheet->setCellValue('C1', 'Determinación');

            // Estilos para header
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
            ];
            $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);
            // Mapa de colores según determinación
            $colorMap = [
                'Muy Alto' => 'dc2626',          // Rojo
                'Alto' => 'ea580c',              // Naranja
                'Medio' => 'facc15',             // Amarillo
                'Bajo' => '22c55e',              // Verde
                'Nulo' => '3b82f6' // Azul
            ];

            // Datos
            $row = 2;
            foreach ($data as $item) {
                $sheet->setCellValue('A' . $row, $item['nombre']);
                $sheet->setCellValue('B' . $row, $item['score']);
                $sheet->setCellValue('C' . $row, $item['determinacion']);

                // Aplicar color a la celda de determinación
                $determinacion = $item['determinacion'];
                $color = $colorMap[$determinacion] ?? '3b82f6';
                $textColor = in_array($determinacion, ['Medio']) ? '000000' : 'FFFFFF';
                $cellStyle = [
                    'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => $color]],
                    'font' => ['bold' => true, 'color' => ['rgb' => $textColor]],
                    'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
                ];
                $sheet->getStyle('C' . $row)->applyFromArray($cellStyle);
                $row++;
            }

            // Ajustar ancho de columnas
            $sheet->getColumnDimension('A')->setWidth(30);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(20);

            // Guardar archivo
            $fileName = 'Resumen_Resultados_' . date('Y-m-d_His') . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $path = storage_path('app/exports/' . $fileName);

            if (!is_dir(storage_path('app/exports'))) {
                mkdir(storage_path('app/exports'), 0755, true);
            }

            $writer->save($path);

            return response()->download($path)->deleteFileAfterSend();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('No se pudo generar el Excel: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    public function generarInformeGuiaII()
    {
        try {
            // Cargar datos si no están disponibles
            if (empty($this->generalResults)) {
                $this->openModalResults();
            }

            $templatePath = storage_path('app/plantillas/informe_resultados_guia2.docx');

            if (!file_exists($templatePath)) {
                throw new \Exception('No se encontró la plantilla de Guía II');
            }

            $templateProcessor = new TemplateProcessor($templatePath);

            // 1. Variables simples
            $this->fillSimpleVariablesG2($templateProcessor);

            // 2. Contadores de riesgo general
            $this->fillRiskCountersG2($templateProcessor);

            // 3. Tablas dinámicas (categorías, dominios, colaboradores)
            $this->fillDynamicTablesG2($templateProcessor);

            // 4. Gráficas
            $this->fillChartsG2($templateProcessor);

            // 5. Top 3 dominios
            $this->fillTop3DomainsG2($templateProcessor);

            // Guardar Word temporal
            $nombreArchivoSalida='informe_GII_' . time() . '.docx';
            $tempWordPath = storage_path('app/livewire-tmp/'.$nombreArchivoSalida);
            $templateProcessor->saveAs($tempWordPath);

            // Convertir a PDF
            $pdfPath = $this->convertToPdf($tempWordPath,$nombreArchivoSalida);

            // Limpiar temporales
            //$this->cleanTempFiles($tempWordPath);

            return response()->download($pdfPath)->deleteFileAfterSend();

        } catch (\Exception $e) {
            try {
                // Intentar con la segunda cuenta de IlovePDF
                $pdfPath = $this->convertToPdfV2($tempWordPath, $nombreArchivoSalida);
                return response()->download($pdfPath)->deleteFileAfterSend();

            } catch (\Exception $e2) {
                \Log::error('Error generando informe GII: ' . $e2->getMessage());
                Notification::make()
                    ->title('Error al generar informe')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
            Notification::make()
                ->title('Error al generar informe')
                ->body($e->getMessage())
                ->danger()
                ->send();
            Log::error($e);
        }
    }
    private function fillSimpleVariablesG2(TemplateProcessor $template)
    {
        $user = auth()->user();
        // 1. Obtener todos los IDs (incluyendo el del usuario actual si es necesario)
        $ids_a_consultar = $this->getSedesHijas($user->sede_id);

        // Asegurarnos que el ID del usuario también esté incluido si la lógica lo requiere
        // Si tu arreglo $centrales ya incluye al padre, omite esta línea:
        if (!in_array($user->sede_id, $ids_a_consultar) && $user->sede_id!==3) { //En el caso de ADC no se debe incluir en las Razones
            array_unshift($ids_a_consultar, $user->sede_id);
        }

        // 2. Consulta a la BD (Una sola consulta optimizada)
        $sedes = \App\Models\Sede::whereIn('id', $ids_a_consultar)->get();
                // 3. Preparamos los datos para el "cloneBlock"
        $replacements = [];

        // Texto fijo de actividad
        $texto_actividad = 'Servicios de administración de centrales camioneras, comercio de alimentos básicos con alta densidad calórica.';

        foreach ($sedes as $sede) {
            $direccion_completa = ($sede->address ?? 'N/A') . ', ' .
                ($sede->cp ?? 'N/A') . ', ' .
                ($sede->city ?? 'N/A') . ', ' .
                ($sede->state ?? 'N/A');

            $replacements[] = [
                'company_name' => $sede->company_name ?? 'N/A',
                'direccion'    => $direccion_completa,
                'actividad'    => $texto_actividad
            ];
        }
        $sedeId=$user->sede_id;
        $campaign = Campaign::whereHas('sedes', function ($query) use ($sedeId) {
            $query->where('sedes.id', $sedeId);
        })
            ->whereHas('evaluations', function ($query) {
                $query->whereIn('evaluations_types.id', [6, 7, 8]);
            })
            ->latest()
            ->first();
        $periodo=$campaign->end_date??now();

        // 4. EJECUTAMOS EL CLONADO DE BLOQUE
        // 'bloque_sedes' debe coincidir con las etiquetas en tu Word
        $template->cloneBlock('bloque_sedes', 0, true, false, $replacements);


       // $colaboradores = User::where('sede_id', $user->sede_id)->where('status','=',1)->where('created_at','<=',$periodo)->count();
        $colaboradores=count($this->colabs);
        $recomendaciones = [
            'Muy Alto' =>'Se requiere realizar el análisis de cada categoría y dominio para establecer las acciones de intervención apropiadas, mediante un Programa de intervención que deberá incluir evaluaciones específicas1, y contemplar campañas de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Alto' => 'Se requiere realizar un análisis de cada categoría y dominio, de manera que se puedan determinar las acciones de intervención apropiadas a través de un Programa de intervención, que podrá incluir una evaluación específica y deberá incluir una campaña de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Medio' => 'Se requiere revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión, mediante un Programa de intervención.',
            'Bajo' => 'Es necesario una mayor difusión de la política de prevención de riesgos psicosociales y programas para: la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral. ',
            'Despreciable' => 'El riesgo resulta despreciable por lo que no se requiere medidas adicionales.'
        ];
        $template->setValue('no_guia', 'II');
        $template->setValue('guia_name', 'Identificación y análisis de factores de riesgo psicosocial');
        $template->setValue('guia_numeral', 'II.3');
        $template->setValue('fecha', now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY'));
        $template->setValue('sede_name', $user->sede?->name ?? 'N/A');
        $template->setValue('responsable',$user->sede?->responsible ?? 'Claudia Leticia Esparza Araujo');
        $template->setValue('cedula', $user->sede?->card_id ?? '8128209');

        $template->setValue('count_colab', $this->responsesTotalG2??'N/A');
        $template->setValue('count_eva', $this->responsesTotalG2??'N/A');
        $template->setValue('cali', number_format($this->calificacion, 2));
        $template->setValue('riesgo', $this->resultCuestionario);
        $template->setValue('determinacion', $recomendaciones[$this->resultCuestionario]);

    }
    private function fillRiskCountersG2(TemplateProcessor $template)
    {
        $counters = [
            'very_high' => 0,
            'high' => 0,
            'medium' => 0,
            'down' => 0,
            'so_down' => 0
        ];

        // Obtener todas las calificaciones individuales
        $userScores = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });

        foreach ($userScores as $score) {
            $level = $this->getTotalRiskLevel($score);

            match($level) {
                'Muy Alto' => $counters['very_high']++,
                'Alto' => $counters['high']++,
                'Medio' => $counters['medium']++,
                'Bajo' => $counters['down']++,
                'Nulo' => $counters['so_down']++,
                default => null
            };
        }
        //Quiero añadir $counters + calificacion=>number_format($this->calificacion, 2) a dataGeneral

        $this->dataGeneral = array_merge($counters, [
            'calificacion' => number_format($this->calificacion, 2)
        ]);

        $template->setValue('cal_very_high', $counters['very_high']);
        $template->setValue('cal_high', $counters['high']);
        $template->setValue('cal_medium', $counters['medium']);
        $template->setValue('cal_down', $counters['down']);
        $template->setValue('cal_so_down', $counters['so_down']);
    }
//Cambio para obtener la calificación por categoría
    private function fillDynamicTablesG2(TemplateProcessor $template)
    {
        // Mapeo de categorías a sus question_ids
        $categoryQuestions = [
            'ambiente' => $this->getCategoryQuestionIds('ambiente'),
            'actividad' => $this->getCategoryQuestionIds('activity'),
            'tiempo' => $this->getCategoryQuestionIds('time'),
            'liderazgo' => $this->getCategoryQuestionIds('leadership')
        ];
        $evaluationId=EvaluationsTypes::where('name','like','%Guía II')->get()->first()->id;

        // Calcular calificaciones por categoría
        $categoryScores = [];
        foreach ($categoryQuestions as $categoryKey => $questionIds) {
            // Obtener los IDs reales de las preguntas basándose en el order
            $realQuestionIds = \App\Models\Question::whereIn('order', $questionIds)
                ->where('evaluations_type_id',$evaluationId)
                ->pluck('id')->toArray();


            $totalScore = RiskFactorSurvey::where('norma_id', $this->norma->id)
                ->where('sede_id', $this->getCurrentSedeId())
                ->whereIn('question_id', $realQuestionIds)
                ->get()
                ->groupBy('user_id')
                ->map(function ($items) {
                    return $items->sum('equivalence_response');
                })
                ->avg();

            $userCount = RiskFactorSurvey::where('norma_id', $this->norma->id)
                ->where('sede_id', $this->getCurrentSedeId())
                ->whereIn('question_id', $realQuestionIds)
                ->distinct('user_id')
                ->count('user_id');

            $categoryScores[$categoryKey] = $totalScore ?? 0;
        }

        // --- INICIALIZAR VARIABLES DE RASTREO (para determinar Cat mayor) ---
        $maxScore = -1; // Iniciamos en -1 para que cualquier calificación lo supere
        $catMasAltaNombre = 'No identificada';
        $catMasAltaNivel = '';

        // CATEGORÍAS - Clonar filas
        $template->cloneRow('cat_name', count($this->generalResultsCategory));
        $iflag=true;
        $i = 1;
        foreach ($this->generalResultsCategory as $key => $category) {
            // Usar la calificación calculada

            $score = $categoryScores[$key] ?? $category['score'];
            $level = $this->getCategoryRiskLevel($key, $score,$iflag);

            // --- 2. COMPARATIVA "REY DE LA COLINA" (NUEVO) ---
            // Si el score actual es mayor al máximo registrado hasta ahora...
            if ($score > $maxScore) {
                $maxScore = $score; // Actualizamos el récord
                $catMasAltaNombre = $category['nombre'];
                $catMasAltaNivel = $level;
            }

            $template->setValue("cat_name#$i", $category['nombre']);
            $template->setValue("cat_cal#$i", number_format($score, 2));
            $template->setValue("cat_nivel#$i", $level);
        /*
            //Ahora por insertar valores al template por cantidad de colaboradores
            $template->setValue("cat_list_name#$i",$category['nombre']);
            dd($category);
            $template->setValue("count_colab_cat_vh#$i",$category['very_high']);
            $template->setValue("count_colab_cat_h#$i",$category['high']);
        */
            // Contadores por nivel en esta categoría
            $this->fillCategoryCounters($template, $key, $i);
            $i++;
        }
        // --- 3. INSERTAR EL GANADOR FUERA DEL CICLO (NUEVO) ---
        // Aquí construyes el string final. Puedes poner solo el nombre o nombre + nivel.
        // Ejemplo: "Ambiente de Trabajo - Muy Alto"
        $textoFinal = $catMasAltaNombre;


        // O si en tu Word la variable espera solo el nombre:
        // $template->setValue('det_cat_mas_alta', $catMasAltaNombre);

        $template->setValue('det_cat_mas_alta', $textoFinal);
        $template->setValue('level_cat_mas_alta', $catMasAltaNivel);

        $template->cloneRow('cat_list_name', count($this->generalResultsCategory));
        $i = 1;
        foreach ($this->generalResultsCategory as $key => $category) {
            //Ahora por insertar valores al template por cantidad de colaboradores
            $template->setValue("cat_list_name#$i",$category['nombre']);
            $template->setValue("count_colab_cat_vh#$i",$category['very_high']);
            $template->setValue("count_colab_cat_h#$i",$category['high']);
            $template->setValue("count_colab_cat_m#$i",$category['medium']);
            $template->setValue("count_colab_cat_l#$i",$category['low']);
            $template->setValue("count_colab_cat_vl#$i",$category['null']);
            $i++;
        }

        $iflag=false;



        // DOMINIOS

        $template->cloneRow('dom_name', count($this->domainResults));

        $i = 1;
        foreach ($this->domainResults as $key => $domain) {
            $template->setValue("dom_name#$i", $domain['nombre']);
            $template->setValue("dom_cal#$i", number_format($domain['total_cali'], 2));
            $template->setValue("dom_nivel#$i", $domain['riesgo']);

           // $this->fillDomainCounters($template, $key, $i);
            $i++;
        }

        $template->cloneRow('dom_list_name', count($this->domainResults));
        $i=1;
        foreach ($this->domainResults as $key => $domain) {
            $template->setValue("dom_list_name#$i",$domain['nombre']);
            $template->setValue("count_colab_dom_vh#$i",$domain['very_high']);
            $template->setValue("count_colab_dom_h#$i",$domain['high']);
            $template->setValue("count_colab_dom_m#$i",$domain['medium']);
            $template->setValue("count_colab_dom_l#$i",$domain['low']);
            $template->setValue("count_colab_dom_vl#$i",$domain['null']);
            $i++;
        }

        // COLABORADORES

        $this->fillCollaboratorsList($template);
    }


    private function fillCategoryCounters(TemplateProcessor $template, string $categoryKey, int $index)
    {
        $questionIds = $this->getCategoryQuestionIds($categoryKey);

        $counters = ['vh' => 0, 'h' => 0, 'm' => 0, 'b' => 0, 'n' => 0];

        $userScores = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->whereIn('question_id', $questionIds)
            ->get()
            ->groupBy('user_id')
            ->map(function ($items) {
                return $items->sum('equivalence_response');
            });

        foreach ($userScores as $score) {
            $level = $this->getCategoryRiskLevel($categoryKey, $score);

            match($level) {
                'Muy alto' => $counters['vh']++,
                'Alto' => $counters['h']++,
                'Medio' => $counters['m']++,
                'Bajo' => $counters['b']++,
                default => $counters['n']++
            };
        }

        $template->setValue("cat_vh#$index", $counters['vh']);
        $template->setValue("cat_h#$index", $counters['h']);
        $template->setValue("cat_m#$index", $counters['m']);
        $template->setValue("cat_b#$index", $counters['b']);
        $template->setValue("cat_n#$index", $counters['n']);
    }

    private function getCategoryQuestionIds(string $category): array
    {
        $map = [
            'ambiente' => [2, 1, 3],
            'activity' => [4, 9, 5, 6, 7, 8, 41, 42, 43, 10, 11, 12, 13, 20, 21, 22, 18, 19, 26, 27],
            'time' => [14, 15, 16, 17],
            'leadership' => [23, 24, 25, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 44, 45, 46]
        ];

        return $map[$category] ?? [];
    }

    private function fillCollaboratorsList(TemplateProcessor $template)
    {
        $userScores = RiskFactorSurvey::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(function ($items, $userId) {
                $user = $items->first()->user;
                $score = $items->sum('equivalence_response');

                return [
                    'name' => trim("{$user->name} {$user->first_name} {$user->last_name}"),
                    'score' => $score,
                    'level' => $this->getTotalRiskLevel($score)
                ];
            })
            ->sortBy('name')
            ->values();


        $template->cloneRow('colab_name', $userScores->count());

        foreach ($userScores as $index => $colab) {
            $i = $index + 1;
            $template->setValue("colab_name#$i", $colab['name']);
            $template->setValue("colab_score#$i", number_format($colab['score'], 2));
            $template->setValue("colab_level#$i", $colab['level']);

        }

    }
    private function fillChartsG2(TemplateProcessor $template)
    {
        //Grafica General de puntuación por nivel de riesgo
        $generalChart= $this->generateGeneralChart();
        $generalImagePath = storage_path('app/livewire-tmp/chart_gen_' . time() . '.png');
        file_put_contents($generalImagePath, file_get_contents($generalChart));
        $template->setImageValue('chart_gen',[
            'path' => $generalImagePath,
            'width' => 650,
            'height' => 400
            ]
        );


        // Gráfica de Categorías
        $categoryChart = $this->generateCategoryChart();
        $categoryImagePath = storage_path('app/livewire-tmp/chart_cat_' . time() . '.png');
        file_put_contents($categoryImagePath, file_get_contents($categoryChart));

        $template->setImageValue('grafica_categorias', [
            'path' => $categoryImagePath,
            'width' => 400,
            'height' => 300
        ]);

        // Gráfica de Dominios
        $domainChart = $this->generateDomainChart();
        $domainImagePath = storage_path('app/livewire-tmp/chart_dom_' . time() . '.png');
        file_put_contents($domainImagePath, file_get_contents($domainChart));

        $template->setImageValue('grafica_dominios', [
            'path' => $domainImagePath,
            'width' => 400,
            'height' => 300
        ]);

    }
    private function generateGeneralChart(): string
    {
        $labels = ['Clasificación'];
        $dataVH = [];
        $dataH = [];
        $dataM = [];
        $dataB = [];
        $dataN = [];

        // Aquí debes calcular los contadores por categoría

            $dataVH[] = $this->dataGeneral['very_high'];
            $dataH[] = $this->dataGeneral['high'];
            $dataM[] = $this->dataGeneral['medium'];
            $dataB[] = $this->dataGeneral['down'];
            $dataN[] = $this->dataGeneral['so_down'];


        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Muy Alto', 'data' => $dataVH, 'backgroundColor' => '#dc2626'],
                    ['label' => 'Alto', 'data' => $dataH, 'backgroundColor' => '#e69d10'],
                    ['label' => 'Medio', 'data' => $dataM, 'backgroundColor' => '#facc15'],
                    ['label' => 'Bajo', 'data' => $dataB, 'backgroundColor' => '#22c55e'],
                    ['label' => 'Nulo', 'data' => $dataN, 'backgroundColor' => '#3b82f6']
                ]
            ],
            'options' => [
                'plugins' => ['title' => ['display' => true, 'text' => 'Resultados por Categoría']],
                'scales' => ['y' => ['beginAtZero' => true]]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
    }

    private function generateCategoryChart(): string
    {
        $labels = [];
        $dataVH = [];
        $dataH = [];
        $dataM = [];
        $dataB = [];
        $dataN = [];

        foreach ($this->generalResultsCategory as $category) {
            $labels[] = $category['nombre'];

            // Aquí debes calcular los contadores por categoría
            $dataVH[] = $category['very_high'];
            $dataH[] = $category['high'];
            $dataM[] = $category['medium'];
            $dataB[] = $category['low'];
            $dataN[] = $category['null'];
        }

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Muy Alto', 'data' => $dataVH, 'backgroundColor' => '#dc2626'],
                    ['label' => 'Alto', 'data' => $dataH, 'backgroundColor' => '#e69d10'],
                    ['label' => 'Medio', 'data' => $dataM, 'backgroundColor' => '#facc15'],
                    ['label' => 'Bajo', 'data' => $dataB, 'backgroundColor' => '#22c55e'],
                    ['label' => 'Nulo', 'data' => $dataN, 'backgroundColor' => '#3b82f6']
                ]
            ],
            'options' => [
                'plugins' => ['title' => ['display' => true, 'text' => 'Resultados por Categoría']],
                'scales' => ['y' => ['beginAtZero' => true]]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
    }

    private function generateDomainChart(): string{
        $labels = [];
        $dataVH = [];
        $dataH = [];
        $dataM = [];
        $dataB = [];
        $dataN = [];

        foreach ($this->domainResults as $domain) {
            $labels[] = $domain['nombre'];
            $dataVH[] = $domain['very_high'];
            $dataH[] = $domain['high'];
            $dataM[] = $domain['medium'];
            $dataB[] = $domain['low'];
            $dataN[] = $domain['null'];
        }
        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Muy Alto', 'data' => $dataVH, 'backgroundColor' => '#dc2626'],
                    ['label' => 'Alto', 'data' => $dataH, 'backgroundColor' => '#e69d10'],
                    ['label' => 'Medio', 'data' => $dataM, 'backgroundColor' => '#facc15'],
                    ['label' => 'Bajo', 'data' => $dataB, 'backgroundColor' => '#22c55e'],
                    ['label' => 'Nulo', 'data' => $dataN, 'backgroundColor' => '#3b82f6']
                ]
            ],
            'options' => [
                'plugins' => ['title' => ['display' => true, 'text' => 'Resultados por Categoría']],
                'scales' => ['y' => ['beginAtZero' => true]]
            ]
        ];
        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
    }
    private function fillTop3DomainsG2(TemplateProcessor $template)
    {
        // 1. Descripciones (copiadas de tu código original)
        $descriptions = [
            'conditions' => 'Se refieren a las condiciones peligrosas e inseguras o deficientes e insalubres; es decir, a las condiciones del lugar de trabajo que bajo ciertas circunstancias exigen del trabajador un esfuerzo adicional de adaptación.',
            'work_activity' => 'Se refieren a las exigencias que el trabajo impone al trabajador y que exceden su capacidad, pueden ser de diversa naturaleza, como cuantitativas, cognitivas o mentales, emocionales, de responsabilidad, así como cargas contradictorias o inconsistentes.',
            'work_control' => 'El control sobre el trabajo es la posibilidad que tiene el trabajador para influir y tomar decisiones en la realización de sus actividades. La iniciativa y autonomía, el uso y desarrollo de habilidades y conocimientos, la participación y manejo del cambio, así como la capacitación son aspectos que dan al trabajador la posibilidad de influir sobre su trabajo.',
            'work_journey' => 'Representan una exigencia de tiempo laboral que se hace al trabajador en términos de la duración y el horario de la jornada, se convierten en factor de riesgo psicosocial cuando se trabaja con extensas jornadas, con frecuente rotación de turnos o turnos nocturnos, sin pausas y descansos periódicos claramente establecidos y ni medidas de prevención y protección del trabajador para detectar afectación de salud, de manera temprana.',
            'work_family' => 'Surge cuando existe conflicto entre las actividades familiares o personales y las responsabilidades laborales; es decir, cuando de manera constante se tienen que atender responsabilidades laborales durante el tiempo dedicado a la vida familiar y personal, o se tiene que laborar fuera del horario de trabajo.',
            'leadership' => 'El liderazgo negativo en el trabajo hace referencia al tipo de relación que se establece entre el patrón o sus representantes y los trabajadores, cuyas características influyen en la forma de trabajar.',
            'work_relations' => 'Se refiere a la interacción que se establece en el contexto laboral y abarca aspectos como la imposibilidad de interactuar con los compañeros de trabajo para la solución de problemas relacionados con el trabajo, y características desfavorables de estas interacciones en aspectos funcionales como deficiente o nulo trabajo en equipo y apoyo social.',
            'violence' => 'Aquellos actos de hostigamiento, acoso o malos tratos en contra del trabajador, que pueden dañar su integridad o salud, atentando contra su dignidad y creando un entorno intimidatorio, degradante, humillante u ofensivo.',
            'reconocimiento' => 'Se refiere a la escasa o nula retroalimentación sobre el desempeño, la ausencia de recompensas y la falta de valoración del esfuerzo realizado por el trabajador, lo cual impide el sentido de logro y desarrollo personal.',
            'pertenencia' => 'Se refiere a la falta de sentimiento de orgullo y compromiso con el trabajo y la organización, así como a la incertidumbre sobre la continuidad laboral o inestabilidad en la contratación.'
        ];

        // 2. Orden de riesgo para el sort
        $riesgoOrden = [
            'Muy Alto' => 5,
            'Alto' => 4,
            'Medio' => 3,
            'Bajo' => 2,
            'Nulo' => 1,
            'Despreciable' => 0,
        ];

        // 3. Ordenar el arreglo principal (conserva las keys, esto es importante)
        uasort($this->domainResults, function ($a, $b) use ($riesgoOrden) {
            $riesgoA = $riesgoOrden[$a['riesgo']] ?? 0;
            $riesgoB = $riesgoOrden[$b['riesgo']] ?? 0;

            if ($riesgoA !== $riesgoB) {
                return $riesgoB <=> $riesgoA; // Orden descendente
            }
            return $b['total_cali'] <=> $a['total_cali'];
        });

        // 4. FILTRAR: Solo conservar Muy Alto, Alto y Medio
        // array_filter preserva las keys (ej: 'work_journey'), lo cual necesitamos para las descripciones
        $filteredDomains = array_filter($this->domainResults, function ($domain) {
            return in_array($domain['riesgo'], ['Muy Alto', 'Alto', 'Medio']);
        });

        // 5. Preparar variables para el Template Processor

        // Si no hay riesgos medios o altos, creamos un registro dummy para informar
        if (empty($filteredDomains)) {
            $count = 1;
            // Simulamos un dominio vacío para imprimir un mensaje
            $loopData = [
                'none' => [
                    'nombre' => 'No se detectaron dominios con riesgo Medio, Alto o Muy Alto.',
                    'riesgo' => '-',
                    'desc'   => ''
                ]
            ];
        } else {
            $count = count($filteredDomains);
            $loopData = $filteredDomains;
        }

        // 6. CLONAR FILAS
        // Esto buscará ${dom_top_name} en el Word y repetirá la fila $count veces
        $template->cloneRow('dom_top_name', $count);

        $i = 1;
        foreach ($loopData as $key => $domain) {

            $nombre = $domain['nombre'];
            $nivel  = $domain['riesgo'];
            // Si es el dummy (key=none), usamos string vacío, si no, buscamos en el array de descripciones
            $desc   = ($key === 'none') ? '' : ($descriptions[$key] ?? 'Descripción no disponible');

            $template->setValue("dom_top_name#$i", $nombre);
            $template->setValue("dom_top_level#$i", $nivel);
            $template->setValue("dom_top_desc#$i", $desc);

            $i++;
        }
    }
    private function convertToPdf(string $wordPath,string $nombreArchivoSalida): string
    {

        $ilovepdf = new Ilovepdf('project_public_e77e6c5a886b8b19b9e83808f514bf44_uChwi13485de38f75fad79190646cb3fbacfe',
            'secret_key_5ad6baf9deee4bbf2dee704afa6502d5_Lau4ia401a9d22bc6a6715f4c39ec19fd6dd1');

        $task = $ilovepdf->newTask('officepdf');
        $file = $task->addFile($wordPath);
        $task->execute();


        // 1. Definimos explícitamente la carpeta de destino usando storage_path
        $outputDir = storage_path('app/livewire-tmp');

        // 2. Le decimos a IlovePDF que descargue el archivo en esa carpeta
        $task->download($outputDir);

        // 3. Construimos la ruta completa del nuevo PDF
        // IlovePDF guarda el archivo con el mismo nombre pero extensión .pdf
        $nombrePdf = str_replace('.docx', '.pdf', $nombreArchivoSalida);
        $rutaPdf = $outputDir . '/' . $nombrePdf;
        // Limpiar el DOCX temporal
        if (file_exists($wordPath)) {
            @unlink($wordPath);
        }

        // Verificar que el PDF existe
        if (!file_exists($rutaPdf)) {
            throw new \Exception("El archivo PDF no se generó correctamente.");
        }

        return $rutaPdf; // ✅ Solo devuelve la ruta

    }
    private function convertToPdfV2(string $wordPath,string $nombreArchivoSalida): string
    {

        $ilovepdf = new Ilovepdf('project_public_42a3dc90117b0b0c878bf5dadef062ab_uETqR23adecde4907cf9f9b4c0e38b9ce8a01',
            'secret_key_5e9597987ed39e06abf3239cef99f619_0A7IXb2567f52af9f60625139c59875993d28');

        $task = $ilovepdf->newTask('officepdf');
        $file = $task->addFile($wordPath);
        $task->execute();


        // 1. Definimos explícitamente la carpeta de destino usando storage_path
        $outputDir = storage_path('app/livewire-tmp');

        // 2. Le decimos a IlovePDF que descargue el archivo en esa carpeta
        $task->download($outputDir);

        // 3. Construimos la ruta completa del nuevo PDF
        // IlovePDF guarda el archivo con el mismo nombre pero extensión .pdf
        $nombrePdf = str_replace('.docx', '.pdf', $nombreArchivoSalida);
        $rutaPdf = $outputDir . '/' . $nombrePdf;
        // Limpiar el DOCX temporal
        if (file_exists($wordPath)) {
            @unlink($wordPath);
        }

        // Verificar que el PDF existe
        if (!file_exists($rutaPdf)) {
            throw new \Exception("El archivo PDF no se generó correctamente.");
        }

        return $rutaPdf; // ✅ Solo devuelve la ruta

    }
    // INFORME DE RESULTADOS PARA
    //DOMINIOS
    // Enero 2026

    public function generarInformeGuiaIII()
    {
        try {
            $templatePath = storage_path('app/plantillas/informe_guiaIII_template.docx');

            if (!file_exists($templatePath)) {
                throw new \Exception('Plantilla no encontrada');
            }

            $template = new TemplateProcessor($templatePath);

            // Llenar secciones del documento
            $this->fillSimpleVariablesG3($template);
            $this->fillRiskCountersG3($template);
            $this->fillDynamicTablesG3($template);
            $this->fillChartsG3($template);
            $this->fillTop3DomainsG3($template);

            // Guardar Word temporal
            $nombreArchivoSalida = 'informe_G3_' . time() . '.docx';
            $rutaTemporal = storage_path('app/livewire-tmp/' . $nombreArchivoSalida);

            if (!is_dir(storage_path('app/livewire-tmp'))) {
                mkdir(storage_path('app/livewire-tmp'), 0755, true);
            }

            $template->saveAs($rutaTemporal);

            // Convertir a PDF
            $pdfPath = $this->convertToPdf($rutaTemporal, $nombreArchivoSalida);

            // Descargar y limpiar
            return response()->download($pdfPath)->deleteFileAfterSend();

        } catch (\Exception $e) {

            try {
                // Intentar con la segunda cuenta de IlovePDF
                $pdfPath = $this->convertToPdfV2($rutaTemporal, $nombreArchivoSalida);
                return response()->download($pdfPath)->deleteFileAfterSend();

            } catch (\Exception $e2) {
                \Log::error('Error generando informe GIII: ' . $e2->getMessage());
                Notification::make()
                    ->title('Error al generar informe')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }

            \Log::error('Error generando informe GIII: ' . $e->getMessage());
            Notification::make()
                ->title('Error al generar informe')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function fillSimpleVariablesG3(TemplateProcessor $template)
    {
        $user = auth()->user();
        // 1. Obtener todos los IDs (incluyendo el del usuario actual si es necesario)
        $ids_a_consultar = $this->getSedesHijas($user->sede_id);

        // Asegurarnos que el ID del usuario también esté incluido si la lógica lo requiere
        // Si tu arreglo $centrales ya incluye al padre, omite esta línea:
        if (!in_array($user->sede_id, $ids_a_consultar) && $user->sede_id!==3) { //En el caso de ADC no se debe incluir en las Razones
            array_unshift($ids_a_consultar, $user->sede_id);
        }

        // 2. Consulta a la BD (Una sola consulta optimizada)
        $sedes = \App\Models\Sede::whereIn('id', $ids_a_consultar)->get();
        // 3. Preparamos los datos para el "cloneBlock"
        $replacements = [];

        // Texto fijo de actividad
        $texto_actividad = 'Servicios de administración de centrales camioneras, comercio de alimentos básicos con alta densidad calórica.';

        foreach ($sedes as $sede) {
            $direccion_completa = ($sede->address ?? 'N/A') . ', ' .
                ($sede->cp ?? 'N/A') . ', ' .
                ($sede->city ?? 'N/A') . ', ' .
                ($sede->state ?? 'N/A');

            $replacements[] = [
                'company_name' => $sede->company_name ?? 'N/A',
                'direccion'    => $direccion_completa,
                'actividad'    => $texto_actividad
            ];
        }
        /*
        if (empty($replacements)) {
           \Log::warning('Nom035 G3 bloque_sedes sin replacements', [
                'user_id' => $user->id ?? null,
                'sede_id' => $user->sede_id ?? null,
                'norma_id' => $this->norma->id ?? null,
                'ids_a_consultar' => $ids_a_consultar,
                'sedes_count' => $sedes->count(),
            ]);

        }
        */
        /*
        \Log::info('Nom035 G3 bloque_sedes antes de cloneBlock', [
            'user_id' => $user->id ?? null,
            'sede_id' => $user->sede_id ?? null,
            'norma_id' => $this->norma->id ?? null,
            'ids_a_consultar' => $ids_a_consultar,
            'sedes_count' => $sedes->count(),
            'replacements_count' => count($replacements),
            'first_replacement' => $replacements[0] ?? null,
        ]);
        */
        // 4. EJECUTAMOS EL CLONADO DE BLOQUE
        // 'bloque_sedes' debe coincidir con las etiquetas en tu Word

        try {
            $template->cloneRowAndSetValues('company_name', $replacements);
            /*
            \Log::info('Nom035 G3 bloque_sedes despues de cloneBlock', [
                'user_id' => $user->id ?? null,
                'sede_id' => $user->sede_id ?? null,
                'norma_id' => $this->norma->id ?? null,
                'replacements_count' => count($replacements),
                'replacement_keys' => array_keys($replacements[0] ?? []),
                'company_names' => array_column($replacements, 'company_name'),
            ]); */
        } catch (\Throwable $e) {
          /*  \Log::error('Nom035 G3 error en cloneBlock bloque_sedes', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => $user->id ?? null,
                'sede_id' => $user->sede_id ?? null,
                'norma_id' => $this->norma->id ?? null,
                'replacements_count' => count($replacements),
                'first_replacement' => $replacements[0] ?? null,
            ]);
            */
            throw $e;
        }
        $sedeId=$user->sede_id;
        $campaig = Campaign::whereHas('sedes', function ($query) use ($sedeId) {
            $query->where('sedes.id', $sedeId);
        })
            ->whereHas('evaluations', function ($query) {
                $query->whereIn('evaluations_types.id', [6, 7, 8]);
            })
            ->latest()
            ->first();
        $periodo=$campaig->end_date??now();
        $colaboradores=count($this->colabs);
        //$colaboradores = User::where('sede_id', $user->sede_id)->where('status','=',1)->where('created_at','<=',$periodo)->count();
        $total_colab=Nom035Process::where('id',$this->norma->id)->where('sede_id',$user->sede_id)->first()?->total_employees??0;
        /*
         *
        $hombres=$colaboradores>0?User::where('sede_id', $user->sede_id)->where('status','=',1)->where('sex','=','Masculino')->count():0;
        $mujeres=$colaboradores>0?User::where('sede_id', $user->sede_id)->where('status','=',1)->where('sex','=','Femenino')->count():0;
         */


        $recomendaciones = [
            'Muy Alto' =>'Se requiere realizar el análisis de cada categoría y dominio para establecer las acciones de intervención apropiadas, mediante un Programa de intervención que deberá incluir evaluaciones específicas1, y contemplar campañas de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Alto' => 'Se requiere realizar un análisis de cada categoría y dominio, de manera que se puedan determinar las acciones de intervención apropiadas a través de un Programa de intervención, que podrá incluir una evaluación específica y deberá incluir una campaña de sensibilización, revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión.',
            'Medio' => 'Se requiere revisar la política de prevención de riesgos psicosociales y programas para la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral, así como reforzar su aplicación y difusión, mediante un Programa de intervención.',
            'Bajo' => 'Es necesario una mayor difusión de la política de prevención de riesgos psicosociales y programas para: la prevención de los factores de riesgo psicosocial, la promoción de un entorno organizacional favorable y la prevención de la violencia laboral. ',
            'Despreciable' => 'El riesgo resulta despreciable por lo que no se requiere medidas adicionales.'
        ];

        $template->setValue('no_guia', 'III');
        $template->setValue('guia_name', 'Identificación y Análisis de los Factores de Riesgo Psicosocial y Evaluación del Entorno Organizacional en Centros de Trabajo');
        $template->setValue('guia_numeral', 'III.3');
        $template->setValue('fecha', now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY'));
        $template->setValue('sede_name', $user->sede?->company_name ?? 'N/A');
        $template->setValue('responsable',$user->sede?->responsible ?? 'Claudia Leticia Esparza Araujo');
        $template->setValue('cedula', $user->sede?->card_id ?? '8128209');

        $template->setValue('periodo',
            \Carbon\Carbon::parse($periodo)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'));
        $template->setValue('total_colab', $total_colab);
        //$template->setValue('hombres', $hombres);
        //$template->setValue('mujeres', $mujeres);
       $template->setValue('count_colab', $this->totalResponsesG3);
        $template->setValue('count_eva', $this->totalResponsesG3);
        $template->setValue('cali', number_format($this->calificacionG3, 2));
        $template->setValue('riesgo', $this->resultCuestionarioG3);
        $template->setValue('determinacion', $recomendaciones[$this->resultCuestionarioG3]);

    }
    private function fillRiskCountersG3(TemplateProcessor $template)
    {
        // Totales por empleado y riesgo

        $template->setValue('total_vh', $this->generalResultsGuideIII['very_high'] ?? 0);
        $template->setValue('total_h', $this->generalResultsGuideIII['high'] ?? 0);
        $template->setValue('total_m', $this->generalResultsGuideIII['medium'] ?? 0);
        $template->setValue('total_b', $this->generalResultsGuideIII['low'] ?? 0);
        $template->setValue('total_n', $this->generalResultsGuideIII['null'] ?? 0);

        // Porcentajes
        $total = $this->generalResultsGuideIII['total'] ?? 1;
        /*
        $template->setValue('porc_vh', number_format(($this->generalResultsGuideIII['very_high'] / $total) * 100, 1));
        $template->setValue('porc_h', number_format(($this->generalResultsGuideIII['high'] / $total) * 100, 1));
        $template->setValue('porc_m', number_format(($this->generalResultsGuideIII['medium'] / $total) * 100, 1));
        $template->setValue('porc_b', number_format(($this->generalResultsGuideIII['low'] / $total) * 100, 1));
        $template->setValue('porc_n', number_format(($this->generalResultsGuideIII['null'] / $total) * 100, 1));
        */
    }
    private function fillDynamicTablesG3(TemplateProcessor $template)
    {
        // Variables de rastreo para categoría más alta
        $maxScore = -1;
        $catMasAltaNombre = 'No identificada';
        $catMasAltaNivel = '';

        // =====================
        // CATEGORÍAS
        // =====================
        $template->cloneRow('cat_name', count($this->generalResultsGuideIIICategory));

        $i = 1;
        foreach ($this->generalResultsGuideIIICategory as $key => $category) {

            $score = $category['total_cali'] ?? 0;
            $level = $category['riesgo'] ?? 'N/A';

            $template->setValue("cat_name#$i", $category['nombre']);
            $template->setValue("cat_cal#$i", number_format($score, 2));
            $template->setValue("cat_nivel#$i", $level);

            // Categoría con mayor riesgo
            if ($score > $maxScore) {
                $maxScore = $score;
                $catMasAltaNombre = $category['nombre'];
                $catMasAltaNivel = $level;
            }

            // Contadores (null, low, medium, high, very_high)


            $i++;
        }

        // Insertar categoría más alta
        $template->setValue('det_cat_mas_alta', $catMasAltaNombre);
        $template->setValue('level_cat_mas_alta', $catMasAltaNivel);

        // =====================
        // LISTA DE CATEGORÍAS
        // =====================
        $template->cloneRow('cat_list_name', count($this->generalResultsGuideIIICategory));

        $i = 1;
        foreach ($this->generalResultsGuideIIICategory as $category) {
            $template->setValue("cat_list_name#$i", $category['nombre']);
            $template->setValue("cat_null#$i", $category['null']);
            $template->setValue("cat_low#$i", $category['low']);
            $template->setValue("cat_medium#$i", $category['medium']);
            $template->setValue("cat_high#$i", $category['high']);
            $template->setValue("cat_very_high#$i", $category['very_high']);
            $i++;
        }

        // =====================
        // DOMINIOS
        // =====================
        $template->cloneRow('dom_name', count($this->generalDomainResultsGuideIII));

        $i = 1;
        foreach ($this->generalDomainResultsGuideIII as $key => $domain) {

            $template->setValue("dom_name#$i", $domain['nombre']);
            $template->setValue("dom_cal#$i", number_format($domain['total_cali'] ?? 0, 2));
            $template->setValue("dom_nivel#$i", $domain['riesgo'] ?? 'N/A');

           // $this->fillDomainCountersG3($template, $key, $i);
            $i++;
        }

        // Lista de dominios
        $template->cloneRow('dom_list_name', count($this->generalDomainResultsGuideIII));

        $i = 1;
        foreach ($this->generalDomainResultsGuideIII as $domain) {
            $template->setValue("dom_list_name#$i", $domain['nombre']);
            $template->setValue("dom_null#$i", $domain['null']);
            $template->setValue("dom_low#$i", $domain['low']);
            $template->setValue("dom_medium#$i", $domain['medium']);
            $template->setValue("dom_high#$i", $domain['high']);
            $template->setValue("dom_very_high#$i", $domain['very_high']);
            $i++;
        }

        // =====================
        // COLABORADORES
        // =====================
        $this->fillCollaboratorsListG3($template);
    }

    private function fillCategoryCountersG3(TemplateProcessor $template, string $categoryKey, int $index)
    {
        $questionIds = $this->getCategoryQuestionIdsG3($categoryKey);

        $counters = ['vh' => 0, 'h' => 0, 'm' => 0, 'b' => 0, 'n' => 0];

        $userScores = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereIn('question_id', $questionIds)
            ->get()
            ->groupBy('user_id')
            ->map(fn($items) => $items->sum('equivalence_response'));

        foreach ($userScores as $score) {
            $level = $this->getCategoryRiskLevelG3($categoryKey, $score);

            match($level) {
                'Muy alto' => $counters['vh']++,
                'Alto' => $counters['h']++,
                'Medio' => $counters['m']++,
                'Bajo' => $counters['b']++,
                default => $counters['n']++
            };
        }

        $template->setValue("cat_vh#$index", $counters['vh']);
        $template->setValue("cat_h#$index", $counters['h']);
        $template->setValue("cat_m#$index", $counters['m']);
        $template->setValue("cat_b#$index", $counters['b']);
        $template->setValue("cat_n#$index", $counters['n']);
    }

    private function getCategoryQuestionIdsG3(string $category): array
    {
        $map = [
            'ambiente' => [1, 2, 3],
            'actividad' => [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 48, 49, 50],
            'tiempo' => [26, 27, 28, 29],
            'liderazgo' => [30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 51, 52, 53, 54, 55, 56, 57, 58],
            'entorno' => [46, 47, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72]
        ];

        return $map[$category] ?? [];
    }
    private function fillDomainCountersG3(TemplateProcessor $template, string $domainKey, int $index)
    {
        $questionIds = $this->getDomainQuestionIdsG3($domainKey);

        $counters = ['vh' => 0, 'h' => 0, 'm' => 0, 'b' => 0, 'n' => 0];

        $userScores = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->whereIn('question_id', $questionIds)
            ->get()
            ->groupBy('user_id')
            ->map(fn($items) => $items->sum('equivalence_response'));

        foreach ($userScores as $score) {
            $level = $this->getDomainRiskLevelG3($domainKey, $score);

            match($level) {
                'Muy alto' => $counters['vh']++,
                'Alto' => $counters['h']++,
                'Medio' => $counters['m']++,
                'Bajo' => $counters['b']++,
                default => $counters['n']++
            };
        }

        $template->setValue("dom_vh#$index", $counters['vh']);
        $template->setValue("dom_h#$index", $counters['h']);
        $template->setValue("dom_m#$index", $counters['m']);
        $template->setValue("dom_b#$index", $counters['b']);
        $template->setValue("dom_n#$index", $counters['n']);
    }

    private function getDomainQuestionIdsG3(string $domain): array
    {
        $map = [
            'conditions' => [1, 2, 3],
            'work_activity' => [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 48, 49, 50],
            'work_control' => [16, 17, 18, 19, 20, 21, 22, 23, 24, 25],
            'work_journey' => [26, 27],
            'work_family' => [28, 29],
            'leadership' => [30, 31, 32, 33, 34],
            'work_relations' => [35, 36, 37, 51, 52, 53, 54, 55, 56],
            'violence' => [38, 39, 40, 41, 42, 43, 44, 45],
            'performance' => [46, 47, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69],
            'inestable' => [70, 71, 72]
        ];

        return $map[$domain] ?? [];
    }
    private function fillCollaboratorsListG3(TemplateProcessor $template)
    {
        $userScores = RiskFactorSurveyOrganizational::where('norma_id', $this->norma->id)
            ->where('sede_id', $this->getCurrentSedeId())
            ->with('user')
            ->get()
            ->groupBy('user_id')
            ->map(function ($items, $userId) {
                $user = $items->first()->user;
                $score = $items->sum('equivalence_response');

                return [
                    'name' => trim("{$user->name} {$user->first_name} {$user->last_name}"),
                    'score' => $score,
                    'level' => $this->getTotalRiskLevel($score)
                ];
            })
            ->sortBy('name')
            ->values();


        $template->cloneRow('colab_name', $userScores->count());

        foreach ($userScores as $index => $colab) {
            $i = $index + 1;
            $template->setValue("colab_name#$i", $colab['name']);
            $template->setValue("colab_score#$i", number_format($colab['score'], 2));
            $template->setValue("colab_level#$i", $colab['level']);

        }
    }
    private function fillChartsG3(TemplateProcessor $template)
    {
        // Gráfica General
        $generalChart = $this->generateGeneralChartG3();
        $generalImagePath = storage_path('app/livewire-tmp/chart_gen_g3_' . time() . '.png');
        file_put_contents($generalImagePath, file_get_contents($generalChart));

        $template->setImageValue('chart_gen', [
            'path' => $generalImagePath,
            'width' => 400,
            'height' => 300,
            'ratio' => false
        ]);

        // Gráfica de Categorías
        $categoryChart = $this->generateCategoryChartG3();
        $categoryImagePath = storage_path('app/livewire-tmp/chart_cat_g3_' . time() . '.png');
        file_put_contents($categoryImagePath, file_get_contents($categoryChart));

        $template->setImageValue('grafica_categorias', [
            'path' => $categoryImagePath,
            'width' => 500,
            'height' => 350,
            'ratio' => false
        ]);

        // Gráfica de Dominios
        $domainChart = $this->generateDomainChartG3();
        $domainImagePath = storage_path('app/livewire-tmp/chart_dom_g3_' . time() . '.png');
        file_put_contents($domainImagePath, file_get_contents($domainChart));

        $template->setImageValue('grafica_dominios', [
            'path' => $domainImagePath,
            'width' => 500,
            'height' => 350,
            'ratio' => false
        ]);
    }

    private function generateGeneralChartG3(): string
    {
        $data = $this->generalResultsGuideIII;

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => ['Clasificación General'],
                'datasets' => [
                    [
                        'label' => 'Muy Alto',
                        'data' => [$data['very_high'] ?? 0],
                        'backgroundColor' => '#DC2626'
                    ],
                    [
                        'label' => 'Alto',
                        'data' => [$data['high'] ?? 0],
                        'backgroundColor' => '#F59E0B'
                    ],
                    [
                        'label' => 'Medio',
                        'data' => [$data['medium'] ?? 0],
                        'backgroundColor' => '#FCD34D'
                    ],
                    [
                        'label' => 'Bajo',
                        'data' => [$data['low'] ?? 0],
                        'backgroundColor' => '#10B981'
                    ],
                    [
                        'label' => 'Nulo',
                        'data' => [$data['null'] ?? 0],
                        'backgroundColor' => '#6B7280'
                    ]
                ]
            ],
            'options' => [
                'scales' => [
                    'y' => ['beginAtZero' => true]
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
    }

    private function generateCategoryChartG3(): string
    {
        $labels = [];
        $dataVH = [];
        $dataH = [];
        $dataM = [];
        $dataB = [];
        $dataN = [];

        foreach ($this->generalResultsGuideIIICategory as $category) {
            $labels[] = $category['nombre'];
            $dataVH[] = $category['very_high'] ?? 0;
            $dataH[] = $category['high'] ?? 0;
            $dataM[] = $category['medium'] ?? 0;
            $dataB[] = $category['low'] ?? 0;
            $dataN[] = $category['null'] ?? 0;
        }

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Muy Alto', 'data' => $dataVH, 'backgroundColor' => '#DC2626'],
                    ['label' => 'Alto', 'data' => $dataH, 'backgroundColor' => '#F59E0B'],
                    ['label' => 'Medio', 'data' => $dataM, 'backgroundColor' => '#FCD34D'],
                    ['label' => 'Bajo', 'data' => $dataB, 'backgroundColor' => '#10B981'],
                    ['label' => 'Nulo', 'data' => $dataN, 'backgroundColor' => '#6B7280']
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
    }

    private function generateDomainChartG3(): string
    {
        $labels = [];
        $dataVH = [];
        $dataH = [];
        $dataM = [];
        $dataB = [];
        $dataN = [];

        foreach ($this->generalDomainResultsGuideIII as $domain) {
            $labels[] = $domain['nombre'];
            $dataVH[] = $domain['very_high'] ?? 0;
            $dataH[] = $domain['high'] ?? 0;
            $dataM[] = $domain['medium'] ?? 0;
            $dataB[] = $domain['low'] ?? 0;
            $dataN[] = $domain['null'] ?? 0;
        }

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    ['label' => 'Muy Alto', 'data' => $dataVH, 'backgroundColor' => '#DC2626'],
                    ['label' => 'Alto', 'data' => $dataH, 'backgroundColor' => '#F59E0B'],
                    ['label' => 'Medio', 'data' => $dataM, 'backgroundColor' => '#FCD34D'],
                    ['label' => 'Bajo', 'data' => $dataB, 'backgroundColor' => '#10B981'],
                    ['label' => 'Nulo', 'data' => $dataN, 'backgroundColor' => '#6B7280']
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
    }
    private function fillTop3DomainsG3(TemplateProcessor $template)
    {
        // 1. Descripciones (Incluye los dominios extra de la Guía III)
        $descriptions = [
            'conditions' => 'Se refieren a las condiciones peligrosas e inseguras o deficientes e insalubres; es decir, a las condiciones del lugar de trabajo que bajo ciertas circunstancias exigen del trabajador un esfuerzo adicional de adaptación.',
            'work_activity' => 'Se refieren a las exigencias que el trabajo impone al trabajador y que exceden su capacidad, pueden ser de diversa naturaleza, como cuantitativas, cognitivas o mentales, emocionales, de responsabilidad, así como cargas contradictorias o inconsistentes.',
            'work_control' => 'El control sobre el trabajo es la posibilidad que tiene el trabajador para influir y tomar decisiones en la realización de sus actividades. La iniciativa y autonomía, el uso y desarrollo de habilidades y conocimientos, la participación y manejo del cambio, así como la capacitación son aspectos que dan al trabajador la posibilidad de influir sobre su trabajo.',
            'work_journey' => 'Representan una exigencia de tiempo laboral que se hace al trabajador en términos de la duración y el horario de la jornada, se convierten en factor de riesgo psicosocial cuando se trabaja con extensas jornadas, con frecuente rotación de turnos o turnos nocturnos, sin pausas y descansos periódicos claramente establecidos y ni medidas de prevención y protección del trabajador para detectar afectación de salud, de manera temprana.',
            'work_family' => 'Surge cuando existe conflicto entre las actividades familiares o personales y las responsabilidades laborales; es decir, cuando de manera constante se tienen que atender responsabilidades laborales durante el tiempo dedicado a la vida familiar y personal, o se tiene que laborar fuera del horario de trabajo.',
            'leadership' => 'El liderazgo negativo en el trabajo hace referencia al tipo de relación que se establece entre el patrón o sus representantes y los trabajadores, cuyas características influyen en la forma de trabajar.',
            'work_relations' => 'Se refiere a la interacción que se establece en el contexto laboral y abarca aspectos como la imposibilidad de interactuar con los compañeros de trabajo para la solución de problemas relacionados con el trabajo, y características desfavorables de estas interacciones en aspectos funcionales como deficiente o nulo trabajo en equipo y apoyo social.',
            'violence' => 'Aquellos actos de hostigamiento, acoso o malos tratos en contra del trabajador, que pueden dañar su integridad o salud, atentando contra su dignidad y creando un entorno intimidatorio, degradante, humillante u ofensivo.',
            'performance' => 'Se refiere a la escasa o nula retroalimentación sobre el desempeño, la ausencia de recompensas y la falta de valoración del esfuerzo realizado por el trabajador, lo cual impide el sentido de logro y desarrollo personal.',
            'inestable' => 'Se refiere a la falta de sentimiento de orgullo y compromiso con el trabajo y la organización, así como a la incertidumbre sobre la continuidad laboral o inestabilidad en la contratación.'
        ];

        // 2. Definir valores numéricos para el ordenamiento
        $riesgoOrden = [
            'Muy alto' => 5,
            'Alto' => 4,
            'Medio' => 3,
            'Bajo' => 2,
            'Nulo' => 1,
            'Despreciable' => 0,
        ];

        // 3. Ordenar el arreglo principal (conserva las keys)
        uasort($this->generalDomainResultsGuideIII, function ($a, $b) use ($riesgoOrden) {
            $riesgoA = $riesgoOrden[$a['riesgo']] ?? 0;
            $riesgoB = $riesgoOrden[$b['riesgo']] ?? 0;

            if ($riesgoA !== $riesgoB) {
                return $riesgoB <=> $riesgoA; // Mayor riesgo primero
            }
            return $b['total_cali'] <=> $a['total_cali']; // Desempate por puntaje
        });

        // 4. FILTRAR: Solo conservar Muy Alto, Alto y Medio
        $filteredDomains = array_filter($this->generalDomainResultsGuideIII, function ($domain) {
            $r = $domain['riesgo'];
            return $r === 'Muy alto' || $r === 'Alto' || $r === 'Medio';
        });

        // 5. Preparar variables para el Template Processor
        if (empty($filteredDomains)) {
            // Si no hay riesgos, creamos un registro dummy
            $loopData = [
                ['nombre' => 'No se detectaron dominios con riesgo Medio, Alto o Muy Alto', 'riesgo' => '', 'description' => '']
            ];
            $count = 1;
        } else {
            $loopData = $filteredDomains;
            $count = count($filteredDomains);
        }

        // 6. CLONAR FILAS (Usando cloneRow en lugar de setValues fijos)
        // Asegúrate de que en tu Word exista una fila con ${dom_top_name}
        $template->cloneRow('dom_top_name', $count);

        $i = 1;
        foreach ($loopData as $key => $domain) {
            // Si es el dummy array, no tiene key válida para description, manejamos eso:
            $descText = (isset($descriptions[$key])) ? $descriptions[$key] : ($domain['description'] ?? '');

            $template->setValue("dom_top_name#$i", $domain['nombre']);
            $template->setValue("dom_top_level#$i", $domain['riesgo']);
            $template->setValue("dom_top_desc#$i", $descText);

            $i++;
        }
    }


    private function cleanTempFiles(string $wordPath)
    {
        $directory = storage_path('app/livewire-tmp');
        $files = glob($directory . '/*');

        foreach ($files as $file) {
            if ((is_file($file) && str_contains($file, 'chart_')) || str_contains($file, 'informe_')) {
                @unlink($file);
            }
        }
    }

    function getSedesHijas($sede_id) {
        // 'static' evita que el arreglo se recree en cada llamada. ¡Súper rápido!
        static $centrales = [
            3  => [7, 13, 5, 1, 2, 4, 6],
            7  => [7, 13],
            1  => [1, 13],
            5  => [5, 13],
            2  => [2, 13],
            4  => [4, 13],
            6  => [6, 13],
            11 => [11],
            10 => [10],
            8  => [8, 13],
            9  => [9],
            24 => [24],
            12 => [12],
            27 => [27],
            28 => [28],
            13 => [13],
            20 => [20],
            31 => [31],
            25 => [25],
            16 => [16],
            14 => [14],
            29 => [29],
            17 => [17],
            15 => [15],
            18 => [18, 6],
            19 => [19],
            22 => [22],
            21 => [21],
            23 => [23],
            26 => [26],
            30 => [30, 11]
        ];

        // Retorna los hijos si existen, o un array vacío si no (seguridad)
        return $centrales[$sede_id] ?? [$sede_id];
    }

    public function openIdentificacion()
    {
        $this->dispatch('open-modal', id: 'modalIden');
    }
    public function closeIdentificacion(){
        $this->dispatch('close-modal', id: 'modalIden');
    }

    public function openModalProfile (){
        $this->dispatch('open-modal', id: 'modalProfile');
    }
    public function closeModalProfile (){
        $this->dispatch('close-modal', id: 'modalProfile');
    }

    /**
     * Generar reporte de Perfil Sociodemográfico según Guía V NOM-035
     * Incluye análisis segmentado de resultados de riesgo psicosocial para niveles 2 y 3
     */
    public function downloadProfileReport()
    {
        try {
            $sedeId = $this->getCurrentSedeId();
            $normaId = $this->norma->id ?? null;

            // Obtener todos los colaboradores activos de la sede
            $colaboradores = User::where('sede_id', $sedeId)
                ->where('status', true)
                ->whereNotNull('department_id')
                ->whereNotNull('position_id')
                ->with(['department', 'position', 'sede'])
                ->get();

            if ($colaboradores->isEmpty()) {
                Notification::make()
                    ->title('Sin datos disponibles')
                    ->body('No hay colaboradores registrados para generar el reporte.')
                    ->warning()
                    ->send();
                return;
            }

            // Datos sociodemográficos agregados
            $profileData = $this->generateSociodemographicProfile($colaboradores);

            // Si es nivel 2 o 3, incluir análisis de riesgo psicosocial segmentado
            $riskAnalysis = null;
            $categoryAnalysis = null;
            $chartPaths = [];
            $chartImages = []; // Almacenar imágenes en base64 para PDF


            if (($this->level === 2 || $this->level === 3) && $normaId) {
                $riskAnalysis = $this->generateSegmentedRiskAnalysis($colaboradores, $normaId, $sedeId);
                $categoryAnalysis = $this->generateCategoryRiskAnalysis($colaboradores, $normaId, $sedeId);

                // Generar gráficas como imágenes usando QuickChart
                if ($categoryAnalysis) {
                    // Asegurar que el directorio existe
                    $tmpDir = storage_path('app/livewire-tmp');
                    if (!is_dir($tmpDir)) {
                        mkdir($tmpDir, 0755, true);
                    }

                    $timestamp = time();
                    Log::info('📊 Iniciando generación de gráficas', [
                        'tmpDir' => $tmpDir,
                        'timestamp' => $timestamp
                    ]);

                    // Gráfica por Sexo
                    if (isset($categoryAnalysis['by_sex']) && !empty($categoryAnalysis['by_sex'])) {
                        Log::info('🔄 Generando gráfica por Sexo...', [
                            'data' => $categoryAnalysis['by_sex']
                        ]);

                        $sexChartUrl = $this->generateCategoryBySexChart($categoryAnalysis);
                        Log::info('📍 URL de gráfica Sexo generada', [
                            'url' => $sexChartUrl,
                            'length' => strlen($sexChartUrl)
                        ]);

                        if ($sexChartUrl) {
                            $sexChartPath = $tmpDir . '/chart_sex_' . $timestamp . '.png';
                            $imageContent = @file_get_contents($sexChartUrl);

                            Log::info('🖼️ Descargando imagen Sexo', [
                                'path' => $sexChartPath,
                                'contentSize' => $imageContent ? strlen($imageContent) : 0
                            ]);

                            if ($imageContent) {
                                file_put_contents($sexChartPath, $imageContent);
                                $chartPaths['sex'] = $sexChartPath;

                                // Convertir a base64 para mejor compatibilidad con PDFShift
                                $base64Image = base64_encode($imageContent);
                                $chartImages['sex'] = 'data:image/png;base64,' . $base64Image;

                                Log::info('✅ Imagen Sexo guardada correctamente', [
                                    'path' => $sexChartPath,
                                    'exists' => file_exists($sexChartPath),
                                    'size' => file_exists($sexChartPath) ? filesize($sexChartPath) : 0,
                                    'base64Length' => strlen($base64Image)
                                ]);
                            } else {
                                Log::warning('⚠️ No se pudo descargar la imagen de Sexo');
                            }
                        }
                    } else {
                        Log::warning('⚠️ No hay datos de categoryAnalysis[by_sex]');
                    }

                    // Gráfica por Edad
                    if (isset($categoryAnalysis['by_age']) && !empty($categoryAnalysis['by_age'])) {
                        Log::info('🔄 Generando gráfica por Edad...', [
                            'data' => $categoryAnalysis['by_age']
                        ]);

                        $ageChartUrl = $this->generateCategoryByAgeChart($categoryAnalysis);
                        Log::info('📍 URL de gráfica Edad generada', [
                            'url' => $ageChartUrl,
                            'length' => strlen($ageChartUrl)
                        ]);

                        if ($ageChartUrl) {
                            $ageChartPath = $tmpDir . '/chart_age_' . $timestamp . '.png';
                            $imageContent = @file_get_contents($ageChartUrl);

                            Log::info('🖼️ Descargando imagen Edad', [
                                'path' => $ageChartPath,
                                'contentSize' => $imageContent ? strlen($imageContent) : 0
                            ]);

                            if ($imageContent) {
                                file_put_contents($ageChartPath, $imageContent);
                                $chartPaths['age'] = $ageChartPath;

                                // Convertir a base64 para mejor compatibilidad con PDFShift
                                $base64Image = base64_encode($imageContent);
                                $chartImages['age'] = 'data:image/png;base64,' . $base64Image;

                                Log::info('✅ Imagen Edad guardada correctamente', [
                                    'path' => $ageChartPath,
                                    'exists' => file_exists($ageChartPath),
                                    'size' => file_exists($ageChartPath) ? filesize($ageChartPath) : 0,
                                    'base64Length' => strlen($base64Image)
                                ]);
                            } else {
                                Log::warning('⚠️ No se pudo descargar la imagen de Edad');
                            }
                        }
                    } else {
                        Log::warning('⚠️ No hay datos de categoryAnalysis[by_age]');
                    }

                    // Gráfica por Contratación
                    if (isset($categoryAnalysis['by_contract']) && !empty($categoryAnalysis['by_contract'])) {
                        Log::info('🔄 Generando gráfica por Contratación...', [
                            'data' => $categoryAnalysis['by_contract']
                        ]);

                        $contractChartUrl = $this->generateCategoryByContractChart($categoryAnalysis);
                        Log::info('📍 URL de gráfica Contratación generada', [
                            'url' => $contractChartUrl,
                            'length' => strlen($contractChartUrl)
                        ]);

                        if ($contractChartUrl) {
                            $contractChartPath = $tmpDir . '/chart_contract_' . $timestamp . '.png';
                            $imageContent = @file_get_contents($contractChartUrl);

                            Log::info('🖼️ Descargando imagen Contratación', [
                                'path' => $contractChartPath,
                                'contentSize' => $imageContent ? strlen($imageContent) : 0
                            ]);

                            if ($imageContent) {
                                file_put_contents($contractChartPath, $imageContent);
                                $chartPaths['contract'] = $contractChartPath;

                                // Convertir a base64 para mejor compatibilidad con PDFShift
                                $base64Image = base64_encode($imageContent);
                                $chartImages['contract'] = 'data:image/png;base64,' . $base64Image;

                                Log::info('✅ Imagen Contratación guardada correctamente', [
                                    'path' => $contractChartPath,
                                    'exists' => file_exists($contractChartPath),
                                    'size' => file_exists($contractChartPath) ? filesize($contractChartPath) : 0,
                                    'base64Length' => strlen($base64Image)
                                ]);
                            } else {
                                Log::warning('⚠️ No se pudo descargar la imagen de Contratación');
                            }
                        }
                    } else {
                        Log::warning('⚠️ No hay datos de categoryAnalysis[by_contract]');
                    }

                    Log::info('📊 Resumen de gráficas generadas', [
                        'chartPaths' => $chartPaths,
                        'chartImagesCount' => count($chartImages),
                        'chartImagesKeys' => array_keys($chartImages)
                    ]);
                } else {
                    Log::warning('⚠️ categoryAnalysis está vacío o es null');
                }
            }

            // Generar HTML para el PDF
            $guiaType = 'I';
            if ($this->level === 3) {
                $guiaType = 'III';
            } elseif ($this->level === 2) {
                $guiaType = 'II';
            }

            Log::info('🎨 Preparando datos para la vista', [
                'level' => $this->level,
                'guiaType' => $guiaType,
                'totalCollaborators' => $colaboradores->count(),
                'hasRiskAnalysis' => !empty($riskAnalysis),
                'hasCategoryAnalysis' => !empty($categoryAnalysis),
                'chartPathsCount' => count($chartPaths),
                'chartImagesCount' => count($chartImages),
                'chartPaths' => $chartPaths
            ]);

            $html = view('filament.pages.nom35.sociodemographic_profile', [
                'company' => auth()->user()->sede->name ?? 'No definido',
                'reportDate' => \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM, YYYY'),
                'period' => $this->norma->start_date->locale('es')->isoFormat('D [de] MMMM, YYYY') . ' al ' . \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM, YYYY'),
                'totalCollaborators' => $colaboradores->count(),
                'profileData' => $profileData,
                'level' => $this->level,
                'riskAnalysis' => $riskAnalysis,
                'categoryAnalysis' => $categoryAnalysis,
                'chartPaths' => $chartPaths, // Mantener para compatibilidad
                'chartImages' => $chartImages, // Usar base64 para el PDF
                'guiaType' => $guiaType,
            ])->render();

            Log::info('✅ HTML generado, longitud: ' . strlen($html) . ' caracteres');

            // Configurar payload para PDFShift
            $payload = [
                'source' => $html,
                'landscape' => false,
                'use_print' => false,
                'margin' => [
                    'top' => 15,
                    'bottom' => 15,
                    'left' => 15,
                    'right' => 15,
                ],
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-Key' => config('services.pdfshift.api_key'),
            ])
                ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
                ->post('https://api.pdfshift.io/v3/convert/pdf');

            if ($response->successful()) {
                $pdfContent = $response->body();

                // Limpiar imágenes temporales
                foreach ($chartPaths as $path) {
                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }

                return response()->streamDownload(function () use ($pdfContent) {
                    echo $pdfContent;
                }, 'Perfil_Sociodemografico_' . date('Y-m-d') . '.pdf');
            } else {
                // Limpiar imágenes temporales incluso en caso de error
                foreach ($chartPaths as $path) {
                    if (file_exists($path)) {
                        @unlink($path);
                    }
                }

                Notification::make()
                    ->title('Error al generar PDF')
                    ->body('No se pudo generar el PDF: ' . $response->body())
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Error generating sociodemographic profile: ' . $e->getMessage());
            Notification::make()
                ->title('Error')
                ->body('Ocurrió un error al generar el reporte: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Generar perfil sociodemográfico agregado
     */
    private function generateSociodemographicProfile($colaboradores)
    {
        $data = [];

        // 1. SEXO (el más importante según requisitos)
        $data['sex'] = [
            'Masculino' => $colaboradores->where('sex', 'Masculino')->count(),
            'Femenino' => $colaboradores->where('sex', 'Femenino')->count(),
            'Otro' => $colaboradores->whereNotIn('sex', ['Masculino', 'Femenino'])->count(),
        ];

        // 2. EDAD (rangos)
        $ageRanges = [
            '18-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-55' => 0,
            '56-65' => 0,
            '65+' => 0,
        ];

        foreach ($colaboradores as $colab) {
            if ($colab->birthdate) {
                $age = \Carbon\Carbon::parse($colab->birthdate)->age;
                if ($age >= 18 && $age <= 25) $ageRanges['18-25']++;
                elseif ($age >= 26 && $age <= 35) $ageRanges['26-35']++;
                elseif ($age >= 36 && $age <= 45) $ageRanges['36-45']++;
                elseif ($age >= 46 && $age <= 55) $ageRanges['46-55']++;
                elseif ($age >= 56 && $age <= 65) $ageRanges['56-65']++;
                elseif ($age > 65) $ageRanges['65+']++;
            }
        }
        $data['age'] = $ageRanges;

        // 3. ESTADO CIVIL
        $data['marital_status'] = $colaboradores->groupBy('marital_status')
            ->map(fn($group) => $group->count())
            ->toArray();

        // 4. NIVEL DE ESTUDIOS
        $data['scholarship'] = $colaboradores->groupBy('scholarship')
            ->map(fn($group) => $group->count())
            ->toArray();

        // 5. DEPARTAMENTO/ÁREA
        $data['department'] = $colaboradores->groupBy('department.name')
            ->map(fn($group) => $group->count())
            ->toArray();

        // 6. PUESTO
        $data['position'] = $colaboradores->groupBy('position.name')
            ->map(fn($group) => $group->count())
            ->toArray();

        // 7. TIPO DE CONTRATACIÓN
        $data['contract_type'] = $colaboradores->groupBy('contract_type')
            ->map(fn($group) => $group->count())
            ->toArray();

        // 8. TIPO DE PERSONAL (sindicalizado/confianza)
        $data['staff_type'] = $colaboradores->groupBy('staff_type')
            ->map(fn($group) => $group->count())
            ->toArray();

        // 9. TIPO DE JORNADA
        $data['work_shift'] = $colaboradores->groupBy('work_shift')
            ->map(fn($group) => $group->count())
            ->toArray();

        // 10. ROTACIÓN DE TURNOS
        $data['rotates_shifts'] = [
            'Sí' => $colaboradores->where('rotates_shifts', true)->count(),
            'No' => $colaboradores->where('rotates_shifts', false)->count(),
        ];

        // 11. TIEMPO EN EL PUESTO (rangos en meses)
        $timeRanges = [
            '0-6 meses' => 0,
            '6-12 meses' => 0,
            '1-2 años' => 0,
            '2-5 años' => 0,
            '5-10 años' => 0,
            '10+ años' => 0,
        ];

        foreach ($colaboradores as $colab) {
            if ($colab->time_in_position) {
                $months = $colab->time_in_position;
                if ($months <= 6) $timeRanges['0-6 meses']++;
                elseif ($months <= 12) $timeRanges['6-12 meses']++;
                elseif ($months <= 24) $timeRanges['1-2 años']++;
                elseif ($months <= 60) $timeRanges['2-5 años']++;
                elseif ($months <= 120) $timeRanges['5-10 años']++;
                else $timeRanges['10+ años']++;
            }
        }
        $data['time_in_position'] = $timeRanges;

        // 12. EXPERIENCIA LABORAL TOTAL (rangos en años)
        $expRanges = [
            '0-2 años' => 0,
            '3-5 años' => 0,
            '6-10 años' => 0,
            '11-15 años' => 0,
            '16-20 años' => 0,
            '20+ años' => 0,
        ];

        foreach ($colaboradores as $colab) {
            if ($colab->experience_years) {
                $years = $colab->experience_years;
                if ($years <= 2) $expRanges['0-2 años']++;
                elseif ($years <= 5) $expRanges['3-5 años']++;
                elseif ($years <= 10) $expRanges['6-10 años']++;
                elseif ($years <= 15) $expRanges['11-15 años']++;
                elseif ($years <= 20) $expRanges['16-20 años']++;
                else $expRanges['20+ años']++;
            }
        }
        $data['experience_years'] = $expRanges;

        return $data;
    }

    /**
     * Generar análisis de riesgo psicosocial segmentado por variables sociodemográficas
     * Solo para niveles 2 y 3
     */
    private function generateSegmentedRiskAnalysis($colaboradores, $normaId, $sedeId)
    {
        $analysis = [];

        // Determinar qué tabla usar según el nivel
        $surveyModel = $this->level === 3 ? RiskFactorSurveyOrganizational::class : RiskFactorSurvey::class;

        // Obtener usuarios que respondieron la encuesta
        $respondents = $surveyModel::where('norma_id', $normaId)
            ->where('sede_id', $sedeId)
            ->pluck('user_id')
            ->unique();

        $respondingUsers = $colaboradores->whereIn('id', $respondents);

        if ($respondingUsers->isEmpty()) {
            return null;
        }

        // 1. ANÁLISIS POR SEXO (el más importante)
        $analysis['by_sex'] = $this->calculateRiskBySegment($respondingUsers, 'sex', $normaId, $sedeId, $surveyModel);

        // 2. ANÁLISIS POR RANGO DE EDAD
        $analysis['by_age'] = $this->calculateRiskByAgeRange($respondingUsers, $normaId, $sedeId, $surveyModel);

        // 3. ANÁLISIS POR DEPARTAMENTO
        $analysis['by_department'] = $this->calculateRiskByRelation($respondingUsers, 'department', $normaId, $sedeId, $surveyModel);

        // 4. ANÁLISIS POR TIPO DE CONTRATACIÓN
        $analysis['by_contract'] = $this->calculateRiskBySegment($respondingUsers, 'contract_type', $normaId, $sedeId, $surveyModel);

        // 5. ANÁLISIS POR TIPO DE JORNADA
        $analysis['by_shift'] = $this->calculateRiskBySegment($respondingUsers, 'work_shift', $normaId, $sedeId, $surveyModel);

        // 6. ANÁLISIS POR ROTACIÓN DE TURNOS
        $analysis['by_rotation'] = $this->calculateRiskByBoolean($respondingUsers, 'rotates_shifts', $normaId, $sedeId, $surveyModel);

        return $analysis;
    }

    /**
     * Calcular nivel de riesgo por segmento simple
     */
    private function calculateRiskBySegment($users, $field, $normaId, $sedeId, $surveyModel)
    {
        $results = [];
        $segments = $users->groupBy($field);

        foreach ($segments as $segment => $segmentUsers) {
            if (empty($segment)) continue;

            $userIds = $segmentUsers->pluck('id');
            $totalScore = $surveyModel::where('norma_id', $normaId)
                ->where('sede_id', $sedeId)
                ->whereIn('user_id', $userIds)
                ->sum('equivalence_response');

            $count = $segmentUsers->count();
            $avgScore = $count > 0 ? $totalScore / $count : 0;
            $riskLevel = $this->getRiskLevelFromScore($avgScore);

            $results[$segment] = [
                'count' => $count,
                'avg_score' => round($avgScore, 2),
                'risk_level' => $riskLevel,
            ];
        }

        return $results;
    }

    /**
     * Calcular nivel de riesgo por rango de edad
     */
    private function calculateRiskByAgeRange($users, $normaId, $sedeId, $surveyModel)
    {
        $ageRanges = [
            '18-25' => [],
            '26-35' => [],
            '36-45' => [],
            '46-55' => [],
            '56-65' => [],
            '65+' => [],
        ];

        foreach ($users as $user) {
            if ($user->birthdate) {
                $age = \Carbon\Carbon::parse($user->birthdate)->age;
                if ($age >= 18 && $age <= 25) $ageRanges['18-25'][] = $user->id;
                elseif ($age >= 26 && $age <= 35) $ageRanges['26-35'][] = $user->id;
                elseif ($age >= 36 && $age <= 45) $ageRanges['36-45'][] = $user->id;
                elseif ($age >= 46 && $age <= 55) $ageRanges['46-55'][] = $user->id;
                elseif ($age >= 56 && $age <= 65) $ageRanges['56-65'][] = $user->id;
                elseif ($age > 65) $ageRanges['65+'][] = $user->id;
            }
        }

        $results = [];
        foreach ($ageRanges as $range => $userIds) {
            if (empty($userIds)) continue;

            $totalScore = $surveyModel::where('norma_id', $normaId)
                ->where('sede_id', $sedeId)
                ->whereIn('user_id', $userIds)
                ->sum('equivalence_response');

            $count = count($userIds);
            $avgScore = $count > 0 ? $totalScore / $count : 0;
            $riskLevel = $this->getRiskLevelFromScore($avgScore);

            $results[$range] = [
                'count' => $count,
                'avg_score' => round($avgScore, 2),
                'risk_level' => $riskLevel,
            ];
        }

        return $results;
    }

    /**
     * Calcular nivel de riesgo por relación (departamento, posición)
     */
    private function calculateRiskByRelation($users, $relation, $normaId, $sedeId, $surveyModel)
    {
        $results = [];
        $segments = $users->groupBy($relation . '.name');

        foreach ($segments as $segment => $segmentUsers) {
            if (empty($segment)) continue;

            $userIds = $segmentUsers->pluck('id');
            $totalScore = $surveyModel::where('norma_id', $normaId)
                ->where('sede_id', $sedeId)
                ->whereIn('user_id', $userIds)
                ->sum('equivalence_response');

            $count = $segmentUsers->count();
            $avgScore = $count > 0 ? $totalScore / $count : 0;
            $riskLevel = $this->getRiskLevelFromScore($avgScore);

            $results[$segment] = [
                'count' => $count,
                'avg_score' => round($avgScore, 2),
                'risk_level' => $riskLevel,
            ];
        }

        return $results;
    }

    /**
     * Calcular nivel de riesgo por campo booleano
     */
    private function calculateRiskByBoolean($users, $field, $normaId, $sedeId, $surveyModel)
    {
        $results = [];

        foreach ([true => 'Sí', false => 'No'] as $value => $label) {
            $segmentUsers = $users->where($field, $value);
            $userIds = $segmentUsers->pluck('id');

            if ($userIds->isEmpty()) continue;

            $totalScore = $surveyModel::where('norma_id', $normaId)
                ->where('sede_id', $sedeId)
                ->whereIn('user_id', $userIds)
                ->sum('equivalence_response');

            $count = $segmentUsers->count();
            $avgScore = $count > 0 ? $totalScore / $count : 0;
            $riskLevel = $this->getRiskLevelFromScore($avgScore);

            $results[$label] = [
                'count' => $count,
                'avg_score' => round($avgScore, 2),
                'risk_level' => $riskLevel,
            ];
        }

        return $results;
    }

    /**
     * Determinar nivel de riesgo a partir de puntuación
     */
    private function getRiskLevelFromScore($score)
    {
        if ($this->level === 2) {
            // Umbrales para Guía II
            if ($score < 50) return 'Nulo';
            if ($score < 75) return 'Bajo';
            if ($score < 99) return 'Medio';
            if ($score < 140) return 'Alto';
            return 'Muy Alto';
        } else {
            // Umbrales para Guía III
            if ($score < 50) return 'Nulo';
            if ($score < 75) return 'Bajo';
            if ($score < 99) return 'Medio';
            if ($score < 140) return 'Alto';
            return 'Muy Alto';
        }
    }

    /**
     * Generar análisis de riesgo por categorías/dominios segmentado por variables sociodemográficas
     */
    private function generateCategoryRiskAnalysis($colaboradores, $normaId, $sedeId)
    {
        $surveyModel = $this->level === 3 ? RiskFactorSurveyOrganizational::class : RiskFactorSurvey::class;

        // Definir categorías y dominios según el nivel
        if ($this->level === 2) {
            $categories = [
                'ambiente' => 'Ambiente de trabajo',
                'activity' => 'Factores propios de la actividad',
                'time' => 'Organización del tiempo de trabajo',
                'leadership' => 'Liderazgo y relaciones en el trabajo',
            ];
        } else {
            $categories = [
                'ambiente' => 'Ambiente de trabajo',
                'activity' => 'Factores propios de la actividad',
                'time' => 'Organización del tiempo de trabajo',
                'leadership' => 'Liderazgo y relaciones en el trabajo',
                'entorno' => 'Entorno organizacional',
            ];
        }

        $analysis = [];

        // Análisis por SEXO
        $analysis['by_sex'] = $this->analyzeCategoryByDemographic($colaboradores, 'sex', $categories, $normaId, $sedeId, $surveyModel);

        // Análisis por EDAD
        $analysis['by_age'] = $this->analyzeCategoryByAge($colaboradores, $categories, $normaId, $sedeId, $surveyModel);

        // Análisis por TIPO DE CONTRATACIÓN
        $analysis['by_contract'] = $this->analyzeCategoryByDemographic($colaboradores, 'contract_type', $categories, $normaId, $sedeId, $surveyModel);

        // Identificar grupos de mayor riesgo
        $analysis['top_risks'] = $this->identifyTopRiskGroups($analysis);

        return $analysis;
    }

    /**
     * Analizar categorías por segmento demográfico
     */
    private function analyzeCategoryByDemographic($users, $field, $categories, $normaId, $sedeId, $surveyModel)
    {
        $results = [];
        $segments = $users->groupBy($field);

        foreach ($segments as $segment => $segmentUsers) {
            if (empty($segment)) continue;

            $userIds = $segmentUsers->pluck('id');
            $results[$segment] = [];

            foreach ($categories as $categoryKey => $categoryName) {
                $categoryScores = $this->getCategoryScoresForUsers($userIds, $categoryKey, $normaId, $sedeId, $surveyModel);

                if (!empty($categoryScores)) {
                    $avgScore = array_sum($categoryScores) / count($categoryScores);
                    // Usar el método existente: si es level 3, usar getCategoryRiskLevelG3
                    if ($this->level === 3) {
                        $riskLevel = $this->getCategoryRiskLevelG3($categoryKey, $avgScore);
                    } else {
                        $riskLevel = $this->getCategoryRiskLevel($categoryKey, $avgScore);
                    }

                    $results[$segment][$categoryKey] = [
                        'name' => $categoryName,
                        'avg_score' => round($avgScore, 2),
                        'risk_level' => $riskLevel,
                        'count' => count($categoryScores),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Analizar categorías por rangos de edad
     */
    private function analyzeCategoryByAge($users, $categories, $normaId, $sedeId, $surveyModel)
    {
        $ageRanges = [
            '18-25' => [],
            '26-35' => [],
            '36-45' => [],
            '46-55' => [],
            '56-65' => [],
            '65+' => [],
        ];

        foreach ($users as $user) {
            if ($user->birthdate) {
                $age = \Carbon\Carbon::parse($user->birthdate)->age;
                if ($age >= 18 && $age <= 25) $ageRanges['18-25'][] = $user->id;
                elseif ($age >= 26 && $age <= 35) $ageRanges['26-35'][] = $user->id;
                elseif ($age >= 36 && $age <= 45) $ageRanges['36-45'][] = $user->id;
                elseif ($age >= 46 && $age <= 55) $ageRanges['46-55'][] = $user->id;
                elseif ($age >= 56 && $age <= 65) $ageRanges['56-65'][] = $user->id;
                elseif ($age > 65) $ageRanges['65+'][] = $user->id;
            }
        }

        $results = [];
        foreach ($ageRanges as $range => $userIds) {
            if (empty($userIds)) continue;

            $results[$range] = [];
            foreach ($categories as $categoryKey => $categoryName) {
                $categoryScores = $this->getCategoryScoresForUsers($userIds, $categoryKey, $normaId, $sedeId, $surveyModel);

                if (!empty($categoryScores)) {
                    $avgScore = array_sum($categoryScores) / count($categoryScores);
                    // Usar el método existente: si es level 3, usar getCategoryRiskLevelG3
                    if ($this->level === 3) {
                        $riskLevel = $this->getCategoryRiskLevelG3($categoryKey, $avgScore);
                    } else {
                        $riskLevel = $this->getCategoryRiskLevel($categoryKey, $avgScore);
                    }

                    $results[$range][$categoryKey] = [
                        'name' => $categoryName,
                        'avg_score' => round($avgScore, 2),
                        'risk_level' => $riskLevel,
                        'count' => count($categoryScores),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Obtener puntuaciones de categoría para usuarios específicos
     */
    private function getCategoryScoresForUsers($userIds, $categoryKey, $normaId, $sedeId, $surveyModel)
    {
        // Mapeo de preguntas por categoría para Guía II
        $categoryQuestionsG2 = [
            'ambiente' => [1, 2, 3],
            'activity' => [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 18, 19, 20, 21, 22, 26, 27, 41, 42, 43],
            'time' => [14, 15, 16, 17, 23, 24, 25],
            'leadership' => [28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 44, 45, 46],
        ];

        // Mapeo de preguntas por categoría para Guía III
        $categoryQuestionsG3 = [
            'ambiente' => [1, 2, 3],
            'activity' => [4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 23, 24, 25, 26, 31, 32, 33],
            'time' => [17, 18, 19, 20, 21, 22, 27, 28, 29, 30],
            'leadership' => [34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69],
            'entorno' => [70, 71, 72],
        ];

        $categoryQuestions = $this->level === 3 ? $categoryQuestionsG3 : $categoryQuestionsG2;

        if (!isset($categoryQuestions[$categoryKey])) {
            return [];
        }

        $questionIds = $categoryQuestions[$categoryKey];

        // Obtener puntuaciones por usuario
        $scores = [];
        foreach ($userIds as $userId) {
            $userScore = $surveyModel::where('norma_id', $normaId)
                ->where('sede_id', $sedeId)
                ->where('user_id', $userId)
                ->whereIn('question_id', $questionIds)
                ->sum('equivalence_response');

            if ($userScore > 0) {
                $scores[] = $userScore;
            }
        }

        return $scores;
    }

    /**
     * Identificar los grupos con mayor riesgo
     */
    private function identifyTopRiskGroups($analysis)
    {
        $topRisks = [];
        $riskWeight = ['Muy Alto' => 5, 'Alto' => 4, 'Medio' => 3, 'Bajo' => 2, 'Nulo' => 1];

        // Analizar por sexo
        foreach (['by_sex', 'by_age', 'by_contract'] as $dimension) {
            if (!isset($analysis[$dimension])) continue;

            foreach ($analysis[$dimension] as $segment => $categories) {
                foreach ($categories as $categoryKey => $data) {
                    $weight = $riskWeight[$data['risk_level']] ?? 0;

                    if ($weight >= 4) { // Alto o Muy Alto
                        $topRisks[] = [
                            'dimension' => $this->getDimensionLabel($dimension),
                            'segment' => $segment,
                            'category' => $data['name'],
                            'risk_level' => $data['risk_level'],
                            'avg_score' => $data['avg_score'],
                            'count' => $data['count'],
                        ];
                    }
                }
            }
        }

        // Ordenar por nivel de riesgo (descendente)
        usort($topRisks, function($a, $b) use ($riskWeight) {
            return ($riskWeight[$b['risk_level']] ?? 0) <=> ($riskWeight[$a['risk_level']] ?? 0);
        });

        return array_slice($topRisks, 0, 10); // Top 10
    }

    /**
     * Obtener etiqueta de dimensión
     */
    private function getDimensionLabel($dimension)
    {
        $labels = [
            'by_sex' => 'Sexo',
            'by_age' => 'Edad',
            'by_contract' => 'Tipo de Contratación',
        ];

        return $labels[$dimension] ?? $dimension;
    }

    /**
     * Generar gráfica de comparación por Sexo usando QuickChart
     */
    private function generateCategoryBySexChart($categoryAnalysis): string
    {
        if (!isset($categoryAnalysis['by_sex']) || empty($categoryAnalysis['by_sex'])) {
            return '';
        }

        $sexes = array_keys($categoryAnalysis['by_sex']);
        $categories = array_keys($categoryAnalysis['by_sex'][$sexes[0]] ?? []);

        $datasets = [];
        $colors = [
            'Masculino' => '#3b82f6',
            'Femenino' => '#ec4899',
            'Otro' => '#8b5cf6'
        ];

        foreach ($sexes as $sex) {
            $data = [];
            foreach ($categories as $catKey) {
                $data[] = $categoryAnalysis['by_sex'][$sex][$catKey]['avg_score'] ?? 0;
            }

            $datasets[] = [
                'label' => $sex,
                'data' => $data,
                'backgroundColor' => $colors[$sex] ?? '#999',
            ];
        }

        $labels = array_map(function($catKey) use ($categoryAnalysis, $sexes) {
            $name = $categoryAnalysis['by_sex'][$sexes[0]][$catKey]['name'] ?? $catKey;
            return strlen($name) > 20 ? substr($name, 0, 20) . '...' : $name;
        }, $categories);

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => $datasets
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Riesgo por Categoría según Sexo',
                        'font' => ['size' => 16]
                    ],
                    'legend' => ['display' => true, 'position' => 'top']
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Puntuación Promedio']
                    ]
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
    }

    /**
     * Generar gráfica de comparación por Edad usando QuickChart
     */
    private function generateCategoryByAgeChart($categoryAnalysis): string
    {
        if (!isset($categoryAnalysis['by_age']) || empty($categoryAnalysis['by_age'])) {
            return '';
        }

        $ages = array_keys($categoryAnalysis['by_age']);
        $categories = array_keys($categoryAnalysis['by_age'][$ages[0]] ?? []);

        $datasets = [];
        $colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#8b5cf6'];
        $colorIndex = 0;

        foreach ($ages as $age) {
            $data = [];
            foreach ($categories as $catKey) {
                $data[] = $categoryAnalysis['by_age'][$age][$catKey]['avg_score'] ?? 0;
            }

            $datasets[] = [
                'label' => $age . ' años',
                'data' => $data,
                'backgroundColor' => $colors[$colorIndex % count($colors)],
            ];
            $colorIndex++;
        }

        $labels = array_map(function($catKey) use ($categoryAnalysis, $ages) {
            $name = $categoryAnalysis['by_age'][$ages[0]][$catKey]['name'] ?? $catKey;
            return strlen($name) > 20 ? substr($name, 0, 20) . '...' : $name;
        }, $categories);

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => $datasets
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Riesgo por Categoría según Edad',
                        'font' => ['size' => 16]
                    ],
                    'legend' => ['display' => true, 'position' => 'top']
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Puntuación Promedio']
                    ]
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
    }

    /**
     * Generar gráfica de comparación por Tipo de Contratación usando QuickChart
     */
    private function generateCategoryByContractChart($categoryAnalysis): string
    {
        if (!isset($categoryAnalysis['by_contract']) || empty($categoryAnalysis['by_contract'])) {
            return '';
        }

        $contracts = array_keys($categoryAnalysis['by_contract']);
        $categories = array_keys($categoryAnalysis['by_contract'][$contracts[0]] ?? []);

        $datasets = [];
        $colors = ['#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
        $colorIndex = 0;

        foreach ($contracts as $contract) {
            $data = [];
            foreach ($categories as $catKey) {
                $data[] = $categoryAnalysis['by_contract'][$contract][$catKey]['avg_score'] ?? 0;
            }

            $datasets[] = [
                'label' => $contract,
                'data' => $data,
                'backgroundColor' => $colors[$colorIndex % count($colors)],
            ];
            $colorIndex++;
        }

        $labels = array_map(function($catKey) use ($categoryAnalysis, $contracts) {
            $name = $categoryAnalysis['by_contract'][$contracts[0]][$catKey]['name'] ?? $catKey;
            return strlen($name) > 20 ? substr($name, 0, 20) . '...' : $name;
        }, $categories);

        $config = [
            'type' => 'bar',
            'data' => [
                'labels' => $labels,
                'datasets' => $datasets
            ],
            'options' => [
                'plugins' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Riesgo por Categoría según Tipo de Contratación',
                        'font' => ['size' => 16]
                    ],
                    'legend' => ['display' => true, 'position' => 'top']
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title' => ['display' => true, 'text' => 'Puntuación Promedio']
                    ]
                ]
            ]
        ];

        return 'https://quickchart.io/chart?c=' . urlencode(json_encode($config));
    }


}
