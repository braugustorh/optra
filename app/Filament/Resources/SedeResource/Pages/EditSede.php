<?php

namespace App\Filament\Resources\SedeResource\Pages;

use App\Filament\Resources\SedeResource;
use App\Helpers\VisorRoleHelper;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSede extends EditRecord
{
    protected static string $resource = SedeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => VisorRoleHelper::canEditOrDelete()),
        ];
    }
    protected function authorizeAccess(): void
    {
        abort_unless(VisorRoleHelper::canEdit(), 403, __('Ups!, no estas autorizado para realizar esta acción.'));
    }

}
