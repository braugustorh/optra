<?php

namespace App\Filament\Resources\VacationRequestResource\Pages;

use App\Filament\Resources\VacationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;
use App\Services\VacationService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateVacationRequest extends CreateRecord
{
    protected static string $resource = VacationRequestResource::class;
   protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['user_id'] = $user->id;
        $data['sede_id'] = $user->sede_id;
        $data['status'] = 'pending';

        // Validar días disponibles
        $balance = VacationService::getCurrentBalance($user);

        if (!$balance || $balance->available_days < $data['days_requested']) {
            Notification::make()
                ->danger()
                ->title('Días insuficientes')
                ->body('No tienes suficientes días de vacaciones disponibles.')
                ->persistent()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $request = $this->record;
        $supervisor = $this->getSupervisor();

        if ($supervisor) {
            Notification::make()
                ->success()
                ->title('Nueva Solicitud de Vacaciones')
                ->body("{$request->user->name} ha solicitado {$request->days_requested} días de vacaciones del {$request->start_date->format('d/m/Y')} al {$request->end_date->format('d/m/Y')}")
                ->sendToDatabase($supervisor);
        }
    }

    protected function getSupervisor(): ?User
    {
        $user = Auth::user();

        if (!$user->position || !$user->position->supervisor_id) {
            return null;
        }

        return User::where('position_id', $user->position->supervisor_id)->first();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
