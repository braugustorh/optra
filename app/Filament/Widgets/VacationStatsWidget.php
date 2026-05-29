<?php

namespace App\Filament\Widgets;

use App\Models\VacationRequest;
use App\Models\VacationBalance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class VacationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();

        // Si es RH o supervisor, mostrar estadísticas del equipo
        if ($user->hasAnyRole(['RH', 'RH Corp', 'Supervisor', 'Gerente', 'Director'])) {
            return $this->getTeamStats();
        }

        // Usuario normal: mostrar sus propias estadísticas
        return $this->getUserStats();
    }

    protected function getUserStats(): array
    {
        $user = Auth::user();
        $balance = VacationBalance::where('user_id', $user->id)
            ->where('year', now()->year)
            ->first();

        $pendingRequests = VacationRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        return [
            Stat::make('Días Disponibles', $balance?->available_days ?? 0)
                ->description('Días de vacaciones')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('success')
                ->chart([7, 8, 10, 12, 15, $balance?->available_days ?? 0]),

            Stat::make('Días Usados', $balance?->used_days ?? 0)
                ->description('Este año')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('warning'),

            Stat::make('Solicitudes Pendientes', $pendingRequests)
                ->description('Por aprobar')
                ->descriptionIcon('heroicon-o-clock')
                ->color('danger'),
        ];
    }

    protected function getTeamStats(): array
    {
        $user = Auth::user();

        // Construir query base según rol
        $query = VacationRequest::query();

        if ($user->hasRole('RH Corp')) {
            // Ve todas las solicitudes
        } elseif ($user->hasRole('RH')) {
            // Solo de su sede
            $query->whereHas('user', fn($q) => $q->where('sede_id', $user->sede_id));
        } else {
            // Supervisores/Gerentes/Directores: subordinados
            $subordinateIds = $this->getSubordinateIds($user);
            $query->whereIn('user_id', $subordinateIds);
        }

        $pending = (clone $query)->where('status', 'pending')->count();
        $approved = (clone $query)->where('status', 'approved')
            ->whereYear('created_at', now()->year)
            ->count();
        $total = (clone $query)->whereYear('created_at', now()->year)->count();

        return [
            Stat::make('Solicitudes Pendientes', $pending)
                ->description('Requieren aprobación')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
            // ->url() eliminado temporalmente

            Stat::make('Aprobadas este Año', $approved)
                ->description('Total: ' . $total)
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Tasa de Aprobación', $total > 0 ? round(($approved / $total) * 100) . '%' : '0%')
                ->description('Este año')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('info'),
        ];
    }

    protected function getSubordinateIds($user): array
    {
        return \App\Models\User::whereHas('position', function ($q) use ($user) {
            $q->where('supervisor_id', $user->position_id);
        })->pluck('id')->toArray();
    }

    public static function canView(): bool
    {
        return true;
    }
}

