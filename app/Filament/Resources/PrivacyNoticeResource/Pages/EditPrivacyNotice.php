<?php

namespace App\Filament\Resources\PrivacyNoticeResource\Pages;

use App\Filament\Resources\PrivacyNoticeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPrivacyNotice extends EditRecord
{
    protected static string $resource = PrivacyNoticeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Vista Previa del Modal')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->action(function () {
                    $this->dispatch('open-privacy-notice-preview', noticeId: $this->record->id);
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
