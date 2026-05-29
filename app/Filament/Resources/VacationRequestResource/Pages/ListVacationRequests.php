<?php

namespace App\Filament\Resources\VacationRequestResource\Pages;
use App\Filament\Resources\VacationRequestResource;
use App\Services\VacationService;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListVacationRequests extends ListRecords
{
    protected static string $resource = VacationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Solicitud')
                ->before(function () {
                    $user = Auth::user();
                    $balance = VacationService::getCurrentBalance($user);

                    if (!$balance) {
                        VacationService::createOrUpdateBalance($user);
                    }
                }),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'todas' => Tab::make('Todas'),

            'pendientes' => Tab::make('Pendientes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => VacationRequestResource::getModel()::query()
                    ->where('status', 'pending')
                    ->tap(fn ($q) => VacationRequestResource::applyUserScopeQuery($q))
                    ->count()
                ),

            'aprobadas' => Tab::make('Aprobadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved'))
                ->badge(fn () => VacationRequestResource::getModel()::query()
                    ->where('status', 'approved')
                    ->tap(fn ($q) => VacationRequestResource::applyUserScopeQuery($q))
                    ->count()
                ),

            'rechazadas' => Tab::make('Rechazadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected'))
                ->badge(fn () => VacationRequestResource::getModel()::query()
                    ->where('status', 'rejected')
                    ->tap(fn ($q) => VacationRequestResource::applyUserScopeQuery($q))
                    ->count()
                ),
        ];

        // Solo agregar el tab "Mis Solicitudes" si el usuario tiene roles que aprueban
        if (Auth::user()->hasAnyRole(['RH', 'RH Corp', 'Supervisor', 'Gerente', 'Director'])) {
            $tabs['mis_solicitudes'] = Tab::make('Mis Solicitudes')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('user_id', Auth::id()))
                ->badge(fn () => VacationRequestResource::getModel()::query()
                    ->where('user_id', Auth::id())
                    ->count()
                );
        }

        return $tabs;
    }
}
