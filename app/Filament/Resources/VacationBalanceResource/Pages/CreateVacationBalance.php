<?php

namespace App\Filament\Resources\VacationBalanceResource\Pages;

use App\Filament\Resources\VacationBalanceResource;
use App\Services\VacationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateVacationBalance extends CreateRecord
{
    protected static string $resource = VacationBalanceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = \App\Models\User::find($data['user_id']);

        if (!$user || !$user->entry_date) {
            $this->halt();
        }

        // Usar el servicio para calcular automáticamente
        $balance = VacationService::createOrUpdateBalance($user);

        return [
            'user_id' => $user->id,
            'year' => $balance->year,
            'period_start' => $balance->period_start,
            'period_end' => $balance->period_end,
            'total_days' => $balance->total_days,
            'used_days' => 0,
            'pending_days' => 0,
            'available_days' => $balance->total_days,
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
