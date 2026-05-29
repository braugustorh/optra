<?php

namespace App\Filament\Resources\VacationRequestResource\Pages;

use App\Filament\Resources\VacationRequestResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditVacationRequest extends EditRecord
{
    protected static string $resource = VacationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => $this->record->status === 'pending'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si se está aprobando o rechazando
        if ($this->record->status === 'pending' && in_array($data['status'], ['approved', 'rejected'])) {
            $data['approved_by'] = Auth::id();
            $data['approved_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $request = $this->record;

        // Notificar al colaborador del resultado
        if (in_array($request->status, ['approved', 'rejected'])) {
            $status = $request->status === 'approved' ? 'aprobada' : 'rechazada';
            $color = $request->status === 'approved' ? 'success' : 'danger';

            Notification::make()
                ->$color()
                ->title("Solicitud de Vacaciones {$status}")
                ->body("Tu solicitud de {$request->days_requested} días del {$request->start_date->format('d/m/Y')} al {$request->end_date->format('d/m/Y')} ha sido {$status}.")
                ->sendToDatabase($request->user);

            // Notificar a RH si fue aprobada
            if ($request->status === 'approved') {
                $this->notifyHR();
            }
        }
    }

    protected function notifyHR(): void
    {
        $request = $this->record;

        // Notificar a RH de la sede
        $rhUsers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['RH', 'RH Corp']);
        })
            ->where(function ($q) use ($request) {
                $q->where('sede_id', $request->sede_id)
                    ->orWhereHas('roles', fn ($q) => $q->where('name', 'RH Corp'));
            })
            ->get();

        foreach ($rhUsers as $rh) {
            Notification::make()
                ->info()
                ->title('Vacaciones Aprobadas')
                ->body("{$request->user->name} tiene vacaciones aprobadas del {$request->start_date->format('d/m/Y')} al {$request->end_date->format('d/m/Y')}")
                ->sendToDatabase($rh);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
