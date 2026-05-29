<?php

namespace App\Observers;


use App\Models\VacationRequest;
use App\Services\VacationService;

class VacationRequestObserver
{
    /**
     * Handle the VacationRequest "created" event.
     */
    public function created(VacationRequest $vacationRequest): void
    {
        // Al crear una solicitud, restamos de días pendientes
        $balance = VacationService::getCurrentBalance($vacationRequest->user);
        if ($balance) {
            $balance->pending_days += $vacationRequest->days_requested;
            $balance->updateAvailableDays();
        }
    }

    public function updated(VacationRequest $vacationRequest): void
    {
        if ($vacationRequest->isDirty('status')) {
            $balance = VacationService::getCurrentBalance($vacationRequest->user);
            if (!$balance) {
                return;
            }

            if ($vacationRequest->status === 'approved') {
                // Aprobada: movemos de pendientes a usados
                $balance->pending_days -= $vacationRequest->days_requested;
                $balance->used_days += $vacationRequest->days_requested;
            } elseif ($vacationRequest->status === 'rejected') {
                // Rechazada: devolvemos los días pendientes
                $balance->pending_days -= $vacationRequest->days_requested;
            }

            $balance->updateAvailableDays();
        }
    }
}
