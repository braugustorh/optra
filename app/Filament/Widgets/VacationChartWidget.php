<?php

namespace App\Filament\Widgets;

use App\Models\VacationRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class VacationChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Solicitudes de Vacaciones (Últimos 6 meses)';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(function ($monthsAgo) {
            return now()->subMonths($monthsAgo);
        });

        $data = $months->map(function ($month) {
            $query = VacationRequest::query()
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month);

            if (!Auth::user()->hasRole('RH Corp')) {
                $user = Auth::user();
                if ($user->hasRole('RH')) {
                    $query->whereHas('user', fn($q) => $q->where('sede_id', $user->sede_id));
                }
            }

            return $query->count();
        });

        return [
            'datasets' => [
                [
                    'label' => 'Solicitudes',
                    'data' => $data->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $months->map(fn ($m) => $m->format('M Y'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['RH', 'RH Corp']);
    }
}
