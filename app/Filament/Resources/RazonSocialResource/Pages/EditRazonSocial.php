<?php

namespace App\Filament\Resources\RazonSocialResource\Pages;

use App\Filament\Resources\RazonSocialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRazonSocial extends EditRecord
{
    protected static string $resource = RazonSocialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
