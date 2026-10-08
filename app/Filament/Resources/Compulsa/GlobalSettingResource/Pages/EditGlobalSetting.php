<?php

declare(strict_types=1);

namespace App\Filament\Resources\Compulsa\GlobalSettingResource\Pages;

use App\Filament\Resources\Compulsa\GlobalSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGlobalSetting extends EditRecord
{
    protected static string $resource = GlobalSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
