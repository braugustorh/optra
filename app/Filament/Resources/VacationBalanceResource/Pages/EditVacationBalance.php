<?php

namespace App\Filament\Resources\VacationBalanceResource\Pages;

use App\Filament\Resources\VacationBalanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVacationBalance extends EditRecord
{
    protected static string $resource = VacationBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Obtener valores del registro actual si no vienen en $data
        $totalDays = $data['total_days'] ?? $this->record->total_days;
        $usedDays = $data['used_days'] ?? 0;
        $pendingDays = $data['pending_days'] ?? $this->record->pending_days;

        // Recalcular días disponibles
        $data['available_days'] = $totalDays - $usedDays - $pendingDays;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
