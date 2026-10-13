<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Panel360;
use App\Filament\Pages\RiskFactorTest;
use App\Filament\Pages\RiskFactorTestOrgEnviroment;
use App\Filament\Pages\TakeInternalEvaluation;
use App\Filament\Pages\TestGuiaI;
use App\Models\ActiveSurvey;
use App\Models\Campaign;
use App\Models\ClimateOrganizationalResponses;
use App\Models\Evaluation360Response;
use App\Models\EvaluationAssign;
use App\Models\Nom035Process;
use App\Models\PsychometricEvaluation;
use App\Models\RiskFactorSurvey;
use App\Models\RiskFactorSurveyOrganizational;
use App\Models\TraumaticEventSurvey;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Crypt;

class CampaignEvaluationsWidget extends Widget
{
    protected static ?int $sort = -2;
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];
    protected static string $view = 'filament.widgets.campaign-evaluations-widget';

    protected function getViewData(): array
    {
        $user = auth()->user();
        if (!$user) {
            return [
                'pendingActivities'   => [],
                'completedActivities' => [],
                'pendingCount'        => 0,
                'completedCount'      => 0,
                'totalActivities'     => 0,
                'completionRate'      => 100,
                'user'                => null,
            ];
        }

        $pendingActivities = [];
        $completedActivities = [];
        $today = now()->startOfDay();

        // -------------------------------------------------------------
        // 1. CAMPAÑAS ACTIVAS (Clima Organizacional y Evaluación 360°)
        // -------------------------------------------------------------
        $campaigns = Campaign::where('status', 'Activa')
            ->where('end_date', '>=', $today)
            ->whereHas('sedes', function ($query) use ($user) {
                $query->where('sede_id', $user->sede_id);
            })
            ->with(['evaluations'])
            ->get();

        foreach ($campaigns as $campaign) {
            $daysLeft = $campaign->end_date
                ? (int) $today->diffInDays(Carbon::parse($campaign->end_date)->startOfDay(), false)
                : null;
            $isUrgent = $daysLeft !== null && $daysLeft <= 3;

            foreach ($campaign->evaluations as $evaluation) {
                $evalName = trim($evaluation->name);
                $evalNameLower = strtolower($evalName);

                // --- CLIMA ORGANIZACIONAL ---
                if (str_contains($evalNameLower, 'clima organizacional')) {
                    $hasResponded = ClimateOrganizationalResponses::where('user_id', $user->id)
                        ->where('campaign_id', $campaign->id)
                        ->exists();

                    if (!$hasResponded) {
                        $pendingActivities[] = [
                            'id'          => 'clima_' . $campaign->id . '_' . $evaluation->id,
                            'type'        => 'clima',
                            'category'    => 'Clima Laboral',
                            'title'       => $evalName,
                            'subtitle'    => 'Campaña: ' . $campaign->name,
                            'badge'       => $isUrgent ? ($daysLeft <= 0 ? 'Vence hoy' : "Vence en {$daysLeft} días") : 'Pendiente',
                            'badge_color' => $isUrgent ? 'danger' : 'warning',
                            'icon'        => 'heroicon-o-chat-bubble-bottom-center-text',
                            'icon_color'  => 'info',
                            'url'         => route('clima-organizacional.index', [
                                'user'     => Crypt::encryptString($user->id),
                                'campaign' => Crypt::encryptString($campaign->id),
                            ]),
                            'action_text' => 'Realizar Evaluación',
                            'progress'    => null,
                            'is_urgent'   => $isUrgent,
                            'days_left'   => $daysLeft,
                        ];
                    } else {
                        $completedActivities[] = [
                            'id'       => 'clima_done_' . $campaign->id,
                            'category' => 'Clima Laboral',
                            'title'    => $evalName,
                            'subtitle' => 'Campaña: ' . $campaign->name,
                        ];
                    }
                }

                // --- EVALUACIÓN 360° ---
                elseif (str_contains($evalNameLower, '360')) {
                    // Colaboradores que este usuario debe evaluar en esta campaña
                    $assignedCount = EvaluationAssign::where('campaign_id', $campaign->id)
                        ->where('user_id', $user->id)
                        ->distinct('user_to_evaluate_id')
                        ->count('user_to_evaluate_id');

                    if ($assignedCount > 0) {
                        // Colaboradores que este usuario ya evaluó en esta campaña
                        $evaluatedCount = Evaluation360Response::where('campaign_id', $campaign->id)
                            ->where('user_id', $user->id)
                            ->distinct('evaluated_user_id')
                            ->count('evaluated_user_id');

                        $remaining = max(0, $assignedCount - $evaluatedCount);
                        $progressPercent = (int) round(($evaluatedCount / $assignedCount) * 100);

                        if ($remaining > 0) {
                            $pendingActivities[] = [
                                'id'          => 'eval360_' . $campaign->id,
                                'type'        => '360',
                                'category'    => 'Evaluación 360°',
                                'title'       => 'Evaluación 360°',
                                'subtitle'    => "Campaña: {$campaign->name} · {$evaluatedCount} de {$assignedCount} colaboradores evaluados",
                                'badge'       => "{$remaining} por calificar",
                                'badge_color' => $isUrgent ? 'danger' : 'warning',
                                'icon'        => 'heroicon-o-user-group',
                                'icon_color'  => 'purple',
                                'url'         => Panel360::getUrl(),
                                'action_text' => $evaluatedCount > 0 ? 'Continuar Evaluaciones' : 'Comenzar Evaluaciones',
                                'progress'    => $progressPercent,
                                'is_urgent'   => $isUrgent,
                                'days_left'   => $daysLeft,
                            ];
                        } else {
                            $completedActivities[] = [
                                'id'       => 'eval360_done_' . $campaign->id,
                                'category' => 'Evaluación 360°',
                                'title'    => 'Evaluación 360°',
                                'subtitle' => "Campaña: {$campaign->name} ({$assignedCount} evaluaciones completadas)",
                            ];
                        }
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // 2. NOM-035 (Guías I, II y III)
        // -------------------------------------------------------------
        $norma = Nom035Process::findActiveProcess($user->sede_id);
        if ($norma) {
            $activeSurveys = ActiveSurvey::where('norma_id', $norma->id)
                ->with('evaluation')
                ->get();

            foreach ($activeSurveys as $survey) {
                $evalType = $survey->evaluation;
                if (!$evalType) {
                    continue;
                }
                $typeName = trim($evalType->name);
                $typeNameLower = strtolower($typeName);

                // --- GUÍA III: Entorno Organizacional (> 50 colaboradores) ---
                if (preg_match('/gu[íi]a\s+iii\b/iu', $typeName)) {
                    $alreadyAnswered = RiskFactorSurveyOrganizational::where('sede_id', $user->sede_id)
                        ->where('user_id', $user->id)
                        ->where('norma_id', $norma->id)
                        ->exists();

                    if (!$alreadyAnswered) {
                        $pendingActivities[] = [
                            'id'          => 'nom035_g3_' . $norma->id,
                            'type'        => 'nom035',
                            'category'    => 'NOM-035',
                            'title'       => 'Guía III: Entorno Organizacional',
                            'subtitle'    => 'Identificación de factores de riesgo y evaluación del entorno laboral',
                            'badge'       => 'Obligatoria',
                            'badge_color' => 'danger',
                            'icon'        => 'heroicon-o-shield-check',
                            'icon_color'  => 'success',
                            'url'         => RiskFactorTestOrgEnviroment::getUrl(),
                            'action_text' => 'Responder Cuestionario',
                            'progress'    => null,
                            'is_urgent'   => false,
                            'days_left'   => null,
                        ];
                    } else {
                        $completedActivities[] = [
                            'id'       => 'nom035_g3_done_' . $norma->id,
                            'category' => 'NOM-035',
                            'title'    => 'Guía III: Entorno Organizacional',
                            'subtitle' => 'Cuestionario completado satisfactoriamente',
                        ];
                    }
                }

                // --- GUÍA II: Factores de Riesgo Psicosocial (16 - 50 colaboradores) ---
                elseif (preg_match('/gu[íi]a\s+ii\b/iu', $typeName)) {
                    $alreadyAnswered = RiskFactorSurvey::where('sede_id', $user->sede_id)
                        ->where('user_id', $user->id)
                        ->where('norma_id', $norma->id)
                        ->exists();

                    if (!$alreadyAnswered) {
                        $pendingActivities[] = [
                            'id'          => 'nom035_g2_' . $norma->id,
                            'type'        => 'nom035',
                            'category'    => 'NOM-035',
                            'title'       => 'Guía II: Factores de Riesgo Psicosocial',
                            'subtitle'    => 'Cuestionario para centros de trabajo de hasta 50 colaboradores',
                            'badge'       => 'Obligatoria',
                            'badge_color' => 'danger',
                            'icon'        => 'heroicon-o-shield-check',
                            'icon_color'  => 'success',
                            'url'         => RiskFactorTest::getUrl(),
                            'action_text' => 'Responder Cuestionario',
                            'progress'    => null,
                            'is_urgent'   => false,
                            'days_left'   => null,
                        ];
                    } else {
                        $completedActivities[] = [
                            'id'       => 'nom035_g2_done_' . $norma->id,
                            'category' => 'NOM-035',
                            'title'    => 'Guía II: Factores de Riesgo Psicosocial',
                            'subtitle' => 'Cuestionario completado satisfactoriamente',
                        ];
                    }
                }

                // --- GUÍA I: Acontecimientos Traumáticos Severos ---
                elseif (preg_match('/gu[íi]a\s+i\b/iu', $typeName)) {
                    $applies = true;
                    if ($survey->some_users) {
                        $applies = $norma->identifiedCollaborators()
                            ->where('sede_id', $user->sede_id)
                            ->where('user_id', $user->id)
                            ->where('norma_id', $norma->id)
                            ->where('type_identification', 'manual')
                            ->exists();
                    }

                    if ($applies) {
                        $alreadyAnswered = TraumaticEventSurvey::where('sede_id', $user->sede_id)
                            ->where('user_id', $user->id)
                            ->where('norma_id', $norma->id)
                            ->exists();

                        if (!$alreadyAnswered) {
                            $pendingActivities[] = [
                                'id'          => 'nom035_g1_' . $norma->id,
                                'type'        => 'nom035',
                                'category'    => 'NOM-035',
                                'title'       => 'Guía I: Acontecimientos Traumáticos Severos',
                                'subtitle'    => 'Identificación de eventos traumáticos durante o con motivo del trabajo',
                                'badge'       => 'Obligatoria',
                                'badge_color' => 'danger',
                                'icon'        => 'heroicon-o-shield-check',
                                'icon_color'  => 'success',
                                'url'         => TestGuiaI::getUrl(),
                                'action_text' => 'Responder Cuestionario',
                                'progress'    => null,
                                'is_urgent'   => false,
                                'days_left'   => null,
                            ];
                        } else {
                            $completedActivities[] = [
                                'id'       => 'nom035_g1_done_' . $norma->id,
                                'category' => 'NOM-035',
                                'title'    => 'Guía I: Acontecimientos Traumáticos',
                                'subtitle' => 'Cuestionario completado satisfactoriamente',
                            ];
                        }
                    }
                }
            }
        }

        // -------------------------------------------------------------
        // 3. EVALUACIONES PSICOMÉTRICAS PENDIENTES
        // -------------------------------------------------------------
        $psychometrics = PsychometricEvaluation::where('evaluable_type', User::class)
            ->where('evaluable_id', $user->id)
            ->whereIn('status', ['assigned', 'started'])
            ->with('evaluationType')
            ->get();

        foreach ($psychometrics as $psych) {
            $testTitle = $psych->evaluationType?->name ?? 'Evaluación Psicométrica';
            $isStarted = $psych->status === 'started';
            $progress = (int) ($psych->progress ?? 0);

            $pendingActivities[] = [
                'id'          => 'psico_' . $psych->id,
                'type'        => 'psicometria',
                'category'    => 'Psicometría',
                'title'       => $testTitle,
                'subtitle'    => $isStarted ? "En progreso ({$progress}% completado)" : 'Asignada y pendiente de inicio',
                'badge'       => $isStarted ? 'En progreso' : 'Asignada',
                'badge_color' => $isStarted ? 'info' : 'warning',
                'icon'        => 'heroicon-o-academic-cap',
                'icon_color'  => 'amber',
                'url'         => TakeInternalEvaluation::getUrl(['record' => $psych->id]),
                'action_text' => $isStarted ? 'Continuar Prueba' : 'Comenzar Prueba',
                'progress'    => $isStarted ? $progress : null,
                'is_urgent'   => false,
                'days_left'   => null,
            ];
        }

        // -------------------------------------------------------------
        // 4. CÁLCULO DE PROGRESO Y MÉTRICAS GLOBALES
        // -------------------------------------------------------------
        $pendingCount = count($pendingActivities);
        $completedCount = count($completedActivities);
        $totalActivities = $pendingCount + $completedCount;
        $completionRate = $totalActivities > 0
            ? (int) round(($completedCount / $totalActivities) * 100)
            : 100;

        return [
            'pendingActivities'   => $pendingActivities,
            'completedActivities' => $completedActivities,
            'pendingCount'        => $pendingCount,
            'completedCount'      => $completedCount,
            'totalActivities'     => $totalActivities,
            'completionRate'      => $completionRate,
            'user'                => $user,
        ];
    }
}
