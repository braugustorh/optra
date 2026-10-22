<?php

namespace App\Livewire;

use App\Models\PrivacyNotice;
use App\Services\PrivacyNoticeService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class PrivacyNoticeModal extends Component
{
    public bool $showModal = false;
    public bool $isPreview = false;
    public bool $canBypass = false;
    public string $step = 'initial'; // 'initial' | 'simplified' | 'extended'
    public bool $acceptedCheckbox = false;
    public ?int $noticeId = null;

    public string $headerTitle = 'Aviso de Privacidad';
    public ?string $version = null;
    public string $scopeType = 'razon_social';
    public string $initialHtml = '';
    public string $simplifiedHtml = '';
    public string $extendedHtml = '';
    public string $checkboxLabel = '';
    public array $resolvedData = [];

    public function mount(PrivacyNoticeService $service): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        // No mostrar si el usuario está inactivo (flujo de encuesta de salida)
        if (!$user || !$user->status) {
            return;
        }

        // Verificar si ya aceptó el aviso activo (consulta optimizada con caché + sesión + índice único)
        if ($service->hasUserAcceptedActiveNotice($user)) {
            return;
        }

        $activeNotice = PrivacyNotice::getActive();

        if (!$activeNotice) {
            return;
        }

        // Si el usuario tiene rol administrativo y ya decidió saltarse el aviso en su sesión, no mostrarlo
        if (session("optra_privacy_skipped_admin_{$user->id}") === $activeNotice->id) {
            return;
        }

        $this->loadNoticeForUser($activeNotice, $service, false);
    }

    #[On('open-privacy-notice-preview')]
    public function openPreview(int $noticeId, PrivacyNoticeService $service): void
    {
        $notice = PrivacyNotice::find($noticeId);

        if (!$notice) {
            return;
        }

        $this->loadNoticeForUser($notice, $service, true);
    }

    protected function loadNoticeForUser(PrivacyNotice $notice, PrivacyNoticeService $service, bool $isPreview = false): void
    {
        $user = Auth::user();
        $resolved = $service->resolveDataForUser($notice, $user);

        $this->noticeId = $notice->id;
        $this->isPreview = $isPreview;
        $this->canBypass = $user?->hasAnyRole('RH Corp', 'Administrador', 'Super Administrador') ?? false;
        $this->headerTitle = $notice->title ?: 'Aviso de Privacidad y Protección de Datos Personales';
        $this->version = $notice->version;
        $this->scopeType = $notice->scope_type;
        $this->resolvedData = $resolved;

        $this->initialHtml = $service->renderContent($notice->initial_notice_content, $resolved);
        $this->simplifiedHtml = $service->renderContent($notice->simplified_content, $resolved);
        $this->extendedHtml = $service->renderContent($notice->extended_content, $resolved);
        $this->checkboxLabel = $notice->checkbox_label ?: PrivacyNoticeService::getDefaultCheckboxLabel();

        $this->step = 'initial';
        $this->acceptedCheckbox = false;
        $this->showModal = true;
    }

    public function goToSimplified(): void
    {
        $this->step = 'simplified';
        $this->dispatch('privacy-notice-scroll-top');
    }

    public function goToExtended(): void
    {
        $this->step = 'extended';
        $this->dispatch('privacy-notice-scroll-top');
    }

    public function goToInitial(): void
    {
        $this->step = 'initial';
        $this->dispatch('privacy-notice-scroll-top');
    }

    public function skipNoticeForAdmin(): void
    {
        $user = Auth::user();

        if (!$user || !$user->hasAnyRole('RH Corp', 'Administrador', 'Super Administrador')) {
            return;
        }

        $activeNotice = $this->noticeId ? PrivacyNotice::find($this->noticeId) : PrivacyNotice::getActive();

        if ($activeNotice) {
            session(["optra_privacy_skipped_admin_{$user->id}" => $activeNotice->id]);
        }

        $this->showModal = false;

        Notification::make()
            ->info()
            ->title('Modo Administrador: Aviso omitido')
            ->body('Has omitido el aviso de privacidad con tus permisos de administración para revisar o corregir la plataforma.')
            ->duration(6000)
            ->send();
    }

    public function closePreview(): void
    {
        if ($this->isPreview) {
            $this->showModal = false;
            $this->isPreview = false;
        }
    }

    public function acceptNotice(Request $request, PrivacyNoticeService $service): void
    {
        if (!$this->acceptedCheckbox) {
            Notification::make()
                ->warning()
                ->title('Confirmación requerida')
                ->body('Por favor marque la casilla de aceptación para continuar.')
                ->send();
            return;
        }

        if ($this->isPreview) {
            $this->showModal = false;
            $this->isPreview = false;

            Notification::make()
                ->info()
                ->title('Vista previa finalizada')
                ->body('Así es como el colaborador completará la aceptación del Aviso de Privacidad.')
                ->send();
            return;
        }

        $user = Auth::user();
        $notice = $this->noticeId ? PrivacyNotice::find($this->noticeId) : PrivacyNotice::getActive();

        if ($user && $notice) {
            $service->recordAcceptance(
                $notice,
                $user,
                $request->ip(),
                $request->userAgent()
            );

            session()->forget("optra_privacy_skipped_admin_{$user->id}");
        }

        $this->showModal = false;

        Notification::make()
            ->success()
            ->title('Aviso de Privacidad aceptado')
            ->body('Su consentimiento ha sido registrado correctamente.')
            ->send();
    }

    public function rejectAndLogout(Request $request)
    {
        if ($this->isPreview) {
            $this->showModal = false;
            $this->isPreview = false;

            Notification::make()
                ->info()
                ->title('Simulación de rechazo (Vista Previa)')
                ->body('En modo real, el colaborador sería desconectado y enviado a la pantalla de inicio de sesión.')
                ->send();
            return;
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->flush();
        $request->session()->regenerateToken();

        return redirect(filament()->getLoginUrl());
    }

    public function render()
    {
        return view('livewire.privacy-notice-modal');
    }
}
