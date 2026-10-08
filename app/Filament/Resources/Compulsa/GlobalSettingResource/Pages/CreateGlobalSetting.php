<?php

declare(strict_types=1);

namespace App\Filament\Resources\Compulsa\GlobalSettingResource\Pages;

use App\Filament\Resources\Compulsa\GlobalSettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGlobalSetting extends CreateRecord
{
    protected static string $resource = GlobalSettingResource::class;
}
