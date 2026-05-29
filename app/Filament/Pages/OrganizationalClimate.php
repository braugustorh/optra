<?php

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Models\ClimateOrganizationalResponses;
use App\Models\Evaluation360Response;
use App\Models\EvaluationAssign;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use crypt;

class OrganizationalClimate extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-m-check-badge';
    protected static ?string $navigationLabel = 'Test de Clima Organizacional';
    protected static ?string $navigationGroup = 'Clima Organizacional';
    protected ?string $heading = 'Test de Clima Organizacional';
    //protected ?string $subheading = 'Realiza la evaluación vertical de tu colaborador';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.test-organizational-climate';

    public $user;
    public $members;
    public $campaigns;
    public $campaing;
    public $supervisors;
    public $responses;
    public $disabledButton=false;
    public $disabledArea=false;
    public function mount()
    {
        $user = auth()->user();
        $this->user = $user->id;

        // Buscar campaña activa, filtrando por la sede del usuario y el tipo de evaluación
        $campaign = Campaign::where('status', 'Activa')
            ->whereHas('sedes', function ($query) use ($user) {
                $query->where('sede_id', $user->sede_id);
            })
            ->whereHas('evaluations', function ($query) {
                // Verificamos que exista el tipo de evaluación en la tabla pivot campaign_evaluation
                $query->where('name', 'Clima Organizacional');
            })
            ->latest() // Seleccionar la más reciente si hay duplicados
            ->first();

        if ($campaign) {
            $this->campaigns = $campaign;

            // Verificar si el usuario ya contestó la evaluación de esta campaña
            $hasResponse = ClimateOrganizationalResponses::where('campaign_id', $this->campaigns->id)
                ->where('user_id', $this->user)
                ->exists();

            if ($hasResponse) {
                // Si ya contestó, deshabilitamos el área
                $this->disabledArea = false;
            } else {
                // Si no ha contestado, habilitamos el área
                $this->disabledArea = true;
            }

        } else {
            // No hay campaña activa válida para este usuario/sede/tipo
            $this->campaigns = collect();
            $this->disabledArea = false; // Deshabilitar área por defecto
        }
    }

}
