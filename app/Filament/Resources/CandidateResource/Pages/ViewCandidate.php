<?php

namespace App\Filament\Resources\CandidateResource\Pages;

use App\Filament\Resources\CandidateResource;
use App\Models\Candidate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewCandidate extends ViewRecord
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_status')
                ->label('Cambiar Estatus')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => CandidateResource::canChangeStatus())
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Nuevo estatus')
                        ->options(Candidate::STATUS_LABELS)
                        ->default(fn () => $this->record->status)
                        ->native(false)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $this->record->changeStatus($data['status']);
                }),
            Actions\EditAction::make(),
        ];
    }
}
