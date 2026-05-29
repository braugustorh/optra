<?php

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Livewire\CompetencesChart;
use App\Models\Competence;
use App\Models\Psychometry;
use Filament\Actions\Contracts\HasLivewire;
use Filament\Resources\Concerns\HasTabs;
use Filament\Tables\Grouping\Group;
use App\Models\Evaluation360Response;
use App\Models\EvaluationAssign;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\Attributes\On;


class Panel9Box extends Page implements HasTable
{
    protected static bool $shouldRegisterNavigation = false;

    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-c-squares-plus';
    protected static ?string $navigationLabel = ' Panel 9Box';
    protected static ?string $navigationGroup = 'Evaluaciones';
    protected ?string $heading = 'Panel 9 Box';
    protected ?string $subheading = 'Visualiza el análisis de los colaboradores';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.panel9-box';
    public $members;
    public $campaigns;
    public $campaignId;
    public $totales;
    public $users,$userToEvaluated;
    public $user;
    public $show=false;
    public $showChartOnView=false;
    public $activeTab='tab1';
    public $quadrants = [];
    public $orderedIndexes = [4, 7, 9, 2, 5, 8, 1, 3, 6];
 public $titles = [
        9 => 'Futuro Líder',
        8 => 'Estrella Emergente',
        7 => 'Líder Emergente',
        6 => 'Experto Destacado',
        5 => 'Jugador Clave',
        4 => 'Diamante en Bruto',
        3 => 'Desempeño Solido',
        2 => 'Jugador Inconsistente',
        1 => 'Riesgo de Talento',
        ];
 public $colorBadgets=[
        9 => 'success',
        8 => 'success',
        7 => 'success',
        6 => 'warning',
        5 => 'warning',
        4 => 'warning',
        3 => 'info',
        2 => 'info',
        1 => 'danger',
        ];

    public static function canView(): bool
    {
        //Este Panel solo lo debe de ver los Jefes de Área y el Administrador
        //Se debe de agregar la comprobación de que estpo se cumpla para que solo sea visible para los Jefes de Área
        if (\auth()->user()->hasRole('RH Corp')||
            \auth()->user()->hasRole('RH') ||
            \auth()->user()->hasRole('Supervisor') ||
            \auth()->user()->hasRole('Visor') ||
            \auth()->user()->hasRole('Administrador')) {
            return true;
        }else{
            return false;
        }

    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
    //protected static ?string $model = Evaluation360Response::class;
    public function mount(){
        $this->campaignId = Campaign::whereStatus('Activa')->first()->id??null;
        $supervisorId = auth()->user()->position_id;
        $this->users = User::where('status', true)
            ->whereNotNull('department_id')
            ->whereNotNull('position_id')
            ->whereNotNull('sede_id')
            ->whereHas('position', function ($query) use ($supervisorId) {
                $query->where('supervisor_id', $supervisorId);
            })
            ->get();
        //$this->quadrants=collect();
       //$this->loadQuadrantData();
    }
    protected function getTableQuery()
    {

        /*
        return Evaluation360Response::select('evaluated_user_id', 'competence_id', DB::raw('AVG(response) as score'))
            ->where('campaign_id', $this->campaignId)
            ->whereIn('evaluated_user_id', $this->users->pluck('id')->toArray())
            ->groupBy('evaluated_user_id', 'competence_id');
        */

        return Evaluation360Response::select('competence_id', DB::raw('AVG(response) as score'))
            ->where('campaign_id', $this->campaignId)
            ->whereIn('evaluated_user_id', $this->users->pluck('id')->toArray())
            ->groupBy('competence_id') // Agregar id al GROUP BY
            ->orderBy('competence_id');// Agrupar por nombre de competencia
    }
    protected function getTableColumns(): array
    {
        return [
            //Tables\Columns\ImageColumn::make('evaluatedUsers.profile_photo')->rounded()->label(''),
            //Tables\Columns\TextColumn::make('evaluatedUsers.name')->label('User'),
            Tables\Columns\TextColumn::make('competences.name')->label('Competencia')
                ->description(
                    fn (Evaluation360Response $record): string => $record->competences->description
                )->wrap(),
        //Tables\Columns\TextColumn::make('competences.description')->label('Descripción')->wrap(),
            // Tables\Columns\TextColumn::make('questions.question')->label('Pregunta'),
            Tables\Columns\TextColumn::make('score')
                ->label('Score')
                ->formatStateUsing(fn (string $state): string => is_numeric($state) ? number_format((float)$state, 3) : $state)
                ->badge()
                ->color(function (string $state): string {
                    $score = (float) $state;
                    return match (true) {
                        $score >= 4.0 && $score <= 5.0 => 'success',
                        $score >= 2.0 && $score <= 3.9999 => 'warning',
                        $score >= 0.0 && $score <= 1.99 => 'danger',
                        default => 'gray',
                    };
                }),
        ];
    }
    public function getTableRecordKey($record): string
    {
        return $record->evaluated_user_id . '-' . $record->competence_id;
    }

    public function updatedUser($value):void
    {
        $this->show=true;
        $this->userToEvaluated = User::find($value);
        $this->dispatch('update-chart', $value, $this->campaignId);
        $this->dispatch('data-updated', $value, $this->campaignId,$this->titles,$this->colorBadgets);
      //  $this->getChartUser($this->user);
    }
    #[On('show-chart')]
    public function showChart():void
    {
        $this->showChartOnView=true;
    }

    public function prepareQuadrantData():void
    {
        // Inicializar los cuadrantes
        for ($i = 1; $i <= 9; $i++) {
            $this->quadrants[$i] = [
                'collaborators' => [],
                'percentage' => 0,
            ];
        }
        // Obtener todos los colaboradores
        $collaborators = Evaluation360Response::select('evaluated_user_id')
            ->selectRaw('AVG(response) as total_360')
            ->where('campaign_id', $this->campaignId)
            ->whereIN('evaluated_user_id', $this->users->pluck('id')->toArray())
            ->groupBy('evaluated_user_id')
            ->get();

        $totalCollaborators = $collaborators->count();
        $potentials=Psychometry::select('user_id',DB::raw('
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
            ->groupBy('user_id')
            ->pluck('total_average','user_id');

        foreach ($collaborators as $collaborator) {

            $potentialScore = $potentials[$collaborator->evaluated_user_id] ?? null;
            // Determinar el cuadrante del colaborador
            $quadrant = $this->getQuadrantForCollaborator($collaborator,$potentialScore);

            // Agregar el colaborador al cuadrante correspondiente
            $this->quadrants[$quadrant]['collaborators'][] = $collaborator;
        }
        // Calcular el porcentaje de colaboradores en cada cuadrante
        for ($i = 1; $i <= 9; $i++) {
            $count = count($this->quadrants[$i]['collaborators']);
            $percentage = $totalCollaborators > 0 ? ($count / $totalCollaborators) * 100 : 0;
            $this->quadrants[$i]['percentage'] = number_format($percentage, 2);
        }

    }

    private function getQuadrantForCollaborator($collaborator,$potential)
    {
        // Supongamos que tienes los puntajes de desempeño y potencial
        /*
         * Aqui buscamos los puntajes de desempeño y potencial del colaborador
         * en la Psicometria y en la evaluación
         * el eje X será el desempeño y el eje Y será el potencial
         */
        // Obtener los puntajes de desempeño y potencial (valores de 0 a 5)

        $performanceScore = $collaborator->total_360;
        $potentialScore = $potential;

        // Mapear los puntajes a niveles (1: Bajo, 2: Medio, 3: Alto)
        $performanceLevel = $this->mapScoreToLevel($performanceScore); //2
        $potentialLevel = $this->mapScoreToLevel($potentialScore); //1


        $quadrant = ($performanceLevel - 1) * 3 + $potentialLevel;
        static $remap = [
            1 => 1,
            2 => 2,
            3 => 4,
            4 => 3,
            5 => 5,
            6 => 7,
            7 => 6,
            8 => 8,
            9 => 9,
        ];

        return $remap[$quadrant];
    }
    private function mapScoreToLevel($score)
    {
        if ($score >= 4.0 && $score <= 5.0) {
            return 3; // Alto
        } elseif ($score >= 3.0 && $score < 4){
            return 2; // Medio
        } else {
            return 1; // Bajo
        }
    }
    public function changeTab($tab)
    {

        if ($tab === 'tab3') {
           $this->loadQuadrantData();
            $this->activeTab = $tab;

        } else {
            $this->quadrants = [];

            $this->activeTab = $tab;
        }
    }
    public function loadQuadrantData()
    {
        $this->prepareQuadrantData();
    }





}
