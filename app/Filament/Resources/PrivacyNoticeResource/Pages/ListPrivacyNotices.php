<?php

namespace App\Filament\Resources\PrivacyNoticeResource\Pages;

use App\Filament\Resources\PrivacyNoticeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPrivacyNotices extends ListRecords
{
    protected static string $resource = PrivacyNoticeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nuevo Aviso de Privacidad'),
        ];
    }
}
