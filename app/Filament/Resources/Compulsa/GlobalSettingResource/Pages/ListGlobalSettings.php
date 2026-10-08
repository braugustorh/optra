<?php

declare(strict_types=1);

namespace App\Filament\Resources\Compulsa\GlobalSettingResource\Pages;

use App\Filament\Resources\Compulsa\GlobalSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGlobalSettings extends ListRecords
{
    protected static string $resource = GlobalSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
