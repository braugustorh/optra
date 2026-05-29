<?php

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Models\Evaluation360Response;
use App\Models\EvaluationAssign;
use App\Models\User;
use App\Models\Position;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;

class Panel360 extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-m-arrow-path';
    protected static ?string $navigationLabel = ' Panel 360';
    protected static ?string $navigationGroup = 'Evaluaciones';
    protected ?string $heading = 'Panel 360';
    protected ?string $subheading = 'Visualiza a las personas a evaluar y el estatus de las evaluaciones';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.360-panel';
    public string $activeTab = 'tab1';
    public Collection $members;
    public Collection $supervisors;
    public Collection $subordinates;
    public Collection $peers;
    public Collection $clients;
    public Collection $autoEvaluations;
    public $campaigns;
    public $daysRemaining;
    public $today;
    public $responses;

    public static function canView(): bool
    {
        return \auth()->user()->hasAnyRole(['Administrador','RH Corp','RH','Supervisor','Colaborador']);

    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getTitle(): string|Htmlable
    {
        return __('Panel 360');
    }

    public function mount()
    {
        $loggedInUser = auth()->user();

        $exists = Campaign::whereStatus('Activa')
            ->whereHas('sedes', function ($query) use ($loggedInUser) {
                $query->where('sede_id', $loggedInUser->sede_id);
            })->exists();

        if ($exists && !$loggedInUser->hasRole('Administrador')) {
            $campaign = Campaign::whereStatus('Activa')
                ->whereHas('sedes', function ($query) use ($loggedInUser) {
                    $query->where('sede_id', $loggedInUser->sede_id);
                })->first();

            $this->campaigns = $campaign;
            $this->today = Carbon::now();
            $this->campaigns->end_date = Carbon::parse($this->campaigns->end_date);
            $this->daysRemaining = (int)$this->today->diffInDays($this->campaigns->end_date);

            // Obtener todas las asignaciones para el usuario actual en la campaña activa
            $allAssignments = EvaluationAssign::where('campaign_id', $campaign->id)
                ->where('user_id', $loggedInUser->id)
                ->get();

            // Filtrar las asignaciones por tipo
            $this->autoEvaluations = $allAssignments->where('type', 'A');
            $this->supervisors = $allAssignments->where('type', 'J');
            $this->subordinates = $allAssignments->where('type', 'S');
            $this->peers = $allAssignments->where('type', 'P');
            $this->clients = $allAssignments->where('type', 'C');



            // La colección $members ahora contendrá todas las asignaciones para la tabla general
            $this->members = $allAssignments;

            $this->responses = Evaluation360Response::where('campaign_id', $campaign->id)
                ->where('user_id', $loggedInUser->id)
                ->get();

        } else {
            $this->campaigns = collect();
            $this->members = collect();
            $this->supervisors = collect();
            $this->subordinates = collect();
            $this->peers = collect();
            $this->clients = collect();
            $this->autoEvaluations = collect();
            $this->responses = collect();
        }
    }
}
