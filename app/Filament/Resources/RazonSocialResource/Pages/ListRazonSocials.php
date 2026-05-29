<?php

namespace App\Filament\Resources\RazonSocialResource\Pages;

use App\Filament\Resources\RazonSocialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRazonSocials extends ListRecords
{
    protected static string $resource = RazonSocialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
