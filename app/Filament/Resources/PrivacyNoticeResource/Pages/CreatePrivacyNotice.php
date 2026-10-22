<?php

namespace App\Filament\Resources\PrivacyNoticeResource\Pages;

use App\Filament\Resources\PrivacyNoticeResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePrivacyNotice extends CreateRecord
{
    protected static string $resource = PrivacyNoticeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
