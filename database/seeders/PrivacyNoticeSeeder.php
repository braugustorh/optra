<?php

namespace Database\Seeders;

use App\Models\PrivacyNotice;
use App\Services\PrivacyNoticeService;
use Illuminate\Database\Seeder;

class PrivacyNoticeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PrivacyNotice::firstOrCreate(
            ['title' => 'Aviso de Privacidad y Protección de Datos Personales'],
            [
                'version' => 'v1.0',
                'scope_type' => PrivacyNotice::SCOPE_RAZON_SOCIAL,
                'provider_name' => 'NetInnfode',
                'general_company_name' => null,
                'general_address' => null,
                'initial_notice_content' => PrivacyNoticeService::getDefaultInitialContent(),
                'simplified_content' => PrivacyNoticeService::getDefaultSimplifiedContent(),
                'extended_content' => PrivacyNoticeService::getDefaultExtendedContent(),
                'checkbox_label' => PrivacyNoticeService::getDefaultCheckboxLabel(),
                'is_active' => true,
                'published_at' => now(),
            ]
        );
    }
}
