<?php

namespace App\Filament\Resources\VacationBalanceResource\Pages;

use App\Filament\Resources\VacationBalanceResource;
use App\Models\User;
use App\Services\VacationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListVacationBalances extends ListRecords
{
    protected static string $resource = VacationBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Balance'),

            Actions\Action::make('recalculate_all')
                ->label('Recalcular Todos')
                ->icon('heroicon-o-calculator')
                ->requiresConfirmation()
                ->modalDescription('Esto recalculará los balances de todos los colaboradores según su antigüedad.')
                ->action(function () {
                    $users = User::whereNotNull('entry_date')->get();

                    foreach ($users as $user) {
                        VacationService::createOrUpdateBalance($user);
                    }

                    Notification::make()
                        ->success()
                        ->title('Balances Recalculados')
                        ->body('Todos los balances han sido recalculados correctamente.')
                        ->send();
                })
                ->successNotificationTitle('Todos los balances han sido recalculados'),
        ];
    }
}
