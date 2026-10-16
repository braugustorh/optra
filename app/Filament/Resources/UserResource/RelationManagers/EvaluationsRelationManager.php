<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Candidate;
use App\Models\PsychometricEvaluation;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'psychometricEvaluations';
    protected static ?string $title = 'Evaluaciones Psicométricas';
    protected static ?string $icon = 'heroicon-o-clipboard-document-check';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) auth()->user()?->hasAnyRole([
            'RH Corp',
            'RH',
            'Reclutador',
            'Administrador',
            'Super Administrador',
        ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = $this->getOwnerRecord();
                $candidateId = Candidate::where('user_id', $user->id)->value('id');

                return $query->where(function ($q) use ($user, $candidateId) {
                    $q->where(function ($sub) use ($user) {
                        $sub->where('evaluable_type', get_class($user))
                            ->where('evaluable_id', $user->id);
                    });
                    if ($candidateId) {
                        $q->orWhere('candidate_origin_id', $candidateId);
                    }
                })->with('evaluationType');
            })
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('puesto')
                    ->label('Puesto de la batería'),
                Tables\Columns\TextColumn::make('evaluationType.name')
                    ->label('Prueba')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'Moss' => 'success',
                        'Cleaver' => 'warning',
                        'Kostick' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'completed' => 'success',
                        'assigned' => 'warning',
                        'started', 'in_progress' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Asignada')
                    ->date(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Vencimiento')
                    ->date()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completada')
                    ->date(),
            ])
            ->groups([
                Tables\Grouping\Group::make('batch_id')
                    ->label('Batería')
                    ->getTitleFromRecordUsing(function ($record) {
                        $fecha = $record->assigned_at
                            ? \Carbon\Carbon::parse($record->assigned_at)->format('d/m/Y')
                            : 'Sin fecha';
                        return "{$record->puesto} — {$fecha}";
                    }),
            ])
            ->defaultGroup('batch_id')
            ->defaultSort('assigned_at', 'desc')
            ->emptyStateHeading('Sin evaluaciones asignadas')
            ->emptyStateDescription('Asigna una batería psicométrica desde el Dashboard de Psicometrías.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->actions([
                Tables\Actions\Action::make('resend_batch')
                    ->label('Reactivar / Reenviar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalHeading('Reactivar y Reenviar Evaluación')
                    ->modalDescription('Extiende la vigencia del enlace y reenvía el correo al colaborador.')
                    ->form([
                        TextInput::make('extension_days')
                            ->label('Días de vigencia adicionales')
                            ->numeric()
                            ->default(7)
                            ->minValue(1)
                            ->required(),
                        Toggle::make('reset_answers')
                            ->label('¿Reiniciar respuestas y progreso?')
                            ->helperText('Si se activa, se borrarán las respuestas previas para comenzar desde cero. Si se desactiva, conservará su progreso.')
                            ->default(false),
                        Toggle::make('regenerate_token')
                            ->label('Generar nuevo enlace de acceso')
                            ->helperText('Invalida el enlace anterior y genera uno nuevo por seguridad.')
                            ->default(true),
                    ])
                    ->action(function ($record, array $data) {
                        $result = PsychometricEvaluation::reactivateBatch(
                            batchId: $record->batch_id,
                            extensionDays: (int) $data['extension_days'],
                            resetAnswers: (bool) $data['reset_answers'],
                            regenerateToken: (bool) $data['regenerate_token'],
                        );
                        if ($result['success']) {
                            Notification::make()->title($result['message'])->success()->send();
                        } else {
                            Notification::make()->title($result['message'])->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('delete_batch')
                    ->label('Eliminar Batería')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Eliminar batería de evaluaciones?')
                    ->modalDescription('Se eliminarán todas las evaluaciones correspondientes a este lote y sus respuestas asociadas. Esta acción no se puede deshacer.')
                    ->action(function ($record) {
                        $result = PsychometricEvaluation::deleteBatch($record->batch_id);
                        if ($result['success']) {
                            Notification::make()->title($result['message'])->success()->send();
                        } else {
                            Notification::make()->title($result['message'])->danger()->send();
                        }
                    }),
            ]);
    }
}
