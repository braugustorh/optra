<?php

namespace App\Filament\Resources\CandidateResource\Pages;

use App\Filament\Resources\CandidateResource;
use App\Models\Candidate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;

class ViewCandidate extends ViewRecord
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_status')
                ->label('Cambiar Estatus')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => CandidateResource::canChangeStatus())
                ->form([
                    Forms\Components\Select::make('status')
                        ->label('Nuevo estatus')
                        ->options(Candidate::STATUS_LABELS)
                        ->default(fn () => $this->record->status)
                        ->native(false)
                        ->required()
                        ->live(),
                    Forms\Components\Section::make('Vinculación con Colaborador')
                        ->visible(fn (Forms\Get $get) => $get('status') === Candidate::STATUS_CONTRATADO)
                        ->description('Al contratar al candidato, puedes vincularlo a un Colaborador y transferir sus evaluaciones psicométricas.')
                        ->schema([
                            Forms\Components\Placeholder::make('duplicate_email_alert')
                                ->label('')
                                ->visible(function (Forms\Get $get) {
                                    return \App\Models\User::where('email', $this->record->email)->exists();
                                })
                                ->content(function (Forms\Get $get) {
                                    $existing = \App\Models\User::where('email', $this->record->email)->first();
                                    $name = $existing ? trim("{$existing->name} {$existing->first_name} {$existing->last_name}") : '';
                                    $isNewMode = $get('colaborador_mode') === 'new';

                                    if ($isNewMode) {
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="p-3 text-sm rounded-lg bg-red-50 dark:bg-red-950/40 text-red-800 dark:text-red-200 border border-red-300 dark:border-red-700 flex items-start gap-2">' .
                                            '<svg class="w-5 h-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>' .
                                            '<div><strong>Alerta de Duplicidad:</strong> Ya existe un Colaborador registrado con este correo (<strong>' . e($this->record->email) . '</strong> - ' . e($name) . '). ' .
                                            'No es posible crear un nuevo colaborador con el mismo correo. Por favor selecciona <strong>"Vincular a un Colaborador existente"</strong>.</div></div>'
                                        );
                                    }

                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="p-3 text-sm rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-200 border border-blue-300 dark:border-blue-700 flex items-start gap-2">' .
                                        '<svg class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' .
                                        '<div><strong>Colaborador Encontrado:</strong> El correo (<strong>' . e($this->record->email) . '</strong>) pertenece a <strong>' . e($name) . '</strong>. Se ha preseleccionado automáticamente para su vinculación.</div></div>'
                                    );
                                }),
                            Forms\Components\Radio::make('colaborador_mode')
                                ->label('Modalidad de vinculación')
                                ->options([
                                    'existing' => 'Vincular a un Colaborador existente',
                                    'new'      => 'Crear automáticamente un Colaborador con los datos del Candidato',
                                    'none'     => 'Solo cambiar estatus (sin vincular)',
                                ])
                                ->default(fn () => \App\Models\User::where('email', $this->record->email)->exists() ? 'existing' : 'new')
                                ->live(),
                            Forms\Components\Select::make('user_id')
                                ->label('Seleccionar Colaborador')
                                ->options(fn () => \App\Models\User::orderBy('name')->get()->mapWithKeys(fn ($u) => [
                                    $u->id => trim("{$u->name} {$u->first_name} {$u->last_name}") . ($u->email ? " ({$u->email})" : ''),
                                ]))
                                ->default(fn () => \App\Models\User::where('email', $this->record->email)->value('id'))
                                ->searchable()
                                ->visible(fn (Forms\Get $get) => $get('colaborador_mode') === 'existing')
                                ->required(fn (Forms\Get $get) => $get('colaborador_mode') === 'existing'),
                            Forms\Components\Toggle::make('transfer_psychometrics')
                                ->label('Traspasar evaluaciones psicométricas al Colaborador')
                                ->helperText('Las evaluaciones realizadas por el candidato pasarán a formar parte del expediente del colaborador.')
                                ->default(true)
                                ->visible(fn (Forms\Get $get) => in_array($get('colaborador_mode'), ['existing', 'new'])),
                        ]),
                ])
                ->action(function (array $data, Actions\Action $action) {
                    if ($data['status'] === Candidate::STATUS_CONTRATADO && ($data['colaborador_mode'] ?? null) === 'new') {
                        $existing = \App\Models\User::where('email', $this->record->email)->first();
                        if ($existing) {
                            $name = trim("{$existing->name} {$existing->first_name} {$existing->last_name}");
                            \Filament\Notifications\Notification::make()
                                ->title('Alerta: Correo ya registrado')
                                ->body("Ya existe un Colaborador registrado con el correo {$this->record->email} ({$name}). Selecciona la opción 'Vincular a un Colaborador existente' para continuar.")
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                            return;
                        }
                    }

                    $this->record->matchAndContract($data['status'], $data);
                    \Filament\Notifications\Notification::make()
                        ->title('Estatus actualizado correctamente')
                        ->success()
                        ->send();
                }),
            Actions\EditAction::make(),
        ];
    }
}
