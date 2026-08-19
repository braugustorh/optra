<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use App\Notifications\CustomResetPasswordNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

class CustomPasswordReset extends RequestPasswordReset
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email_or_curp')
                    ->label('Usuario (Email) o CURP')
                    ->required()
                    ->autofocus(),
            ]);
    }

    public function request(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            FilamentNotification::make()
                ->title(__('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();
        $input = $data['email_or_curp'];

        // Buscar al usuario por email o curp
        $user = User::where('email', $input)->orWhere('curp', $input)->first();

        if (! $user) {
            FilamentNotification::make()
                ->title('Error')
                ->body('No se encontraron coincidencias con el correo o CURP ingresado.')
                ->danger()
                ->send();
            return;
        }

        if (empty($user->alternate_email)) {
            FilamentNotification::make()
                ->title('Atención')
                ->body('No tienes un correo alterno registrado. Por favor, contacta a Recursos Humanos para restablecer tu contraseña manualmente.')
                ->warning()
                ->send();
            return;
        }

        // Generar token usando el broker de Laravel
        $token = Password::broker()->createToken($user);

        // Enviar correo alterno usando Route on-demand
        Notification::route('mail', trim($user->alternate_email))
            ->notify(new CustomResetPasswordNotification($token, $user));

        FilamentNotification::make()
            ->title('El link de recuperación ha sido enviado al correo alterno ' . $user->alternate_email . ' del usuario')
            ->success()
            ->send();

        // Opcionalmente podemos limpiar el formulario
        $this->form->fill();
    }

}
