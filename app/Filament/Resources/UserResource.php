<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\Widgets\UsersStatsOverview;
use App\Helpers\VisorRoleHelper;
use App\Models\User;
use App\Models\Sede;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Wizard;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Pages\Actions\CreateAction;
use App\Notifications\UserReactivated;
use App\Models\UserTermination;
use Filament\Notifications\Notification;



class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Colaboradores';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $label = 'Usuario';
    protected static ?string $pluralLabel = 'Usuarios';
    protected static ?string $slug = 'usuarios';

    protected static ?int $navigationSort = 1;

    public static function canViewAny():bool
    {
        return \auth()->user()->hasAnyRole(
            'RH',
            'RH Corp',
            'Administrador',
            'Supervisor',
            'Visor','Gerente');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('status')
                    ->label('Estatus')
                    ->boolean(),
                Tables\Columns\ImageColumn::make('profile_photo')
                    ->circular()
                    ->disk('sedyco_disk')
                    ->label('Avatar')
                    ->defaultImageUrl(function (User $record): string {
                        $initials = mb_substr($record->name, 0, 1);
                        if (isset($record->first_name)) {
                            $initials .= mb_substr($record->first_name, 0, 1);
                        }
                        return "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&color=7F9CF5&background=EBF4FF";
                    }),
//                Tables\Columns\ImageColumn::make('profile_photo')
//                    ->circular()
//                    ->label('Avatar'),
                    //->defaultView('filament.components.user-avatar'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Primer Apellido')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Segundo Apellido')
                    ->searchable(),
                Tables\Columns\TextColumn::make('curp')
                    ->label('CURP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nationality')
                    ->label('Nacionalidad')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('birthdate')
                    ->label('Fecha de Nacimiento')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('birth_country')
                    ->label('País de Nacimiento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('birth_state')
                    ->label('Estado de Nacimiento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('birth_city')
                    ->label('Ciudad de Nacimiento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('disability')
                    ->label('Discapacidad')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('colony')
                    ->label('Colonia')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('cp')
                    ->label('Código Postal')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('state')
                    ->label('Estado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ciudad')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('scholarship')
                    ->label('Escolaridad')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('career')
                    ->label('Carrera')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('sede.name')
                    ->label('Sede')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('razonSocial.name')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departamento')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('position.name')
                    ->label('Puesto')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('mi')
                    ->label('Mi')
                    ->boolean(),
                Tables\Columns\TextColumn::make('rfc')
                    ->label('RFC')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('imss')
                    ->label('IMSS')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('contract_type')
                    ->label('Tipo de Contrato')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('entry_date')
                    ->label('Fecha de Ingreso')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estatus')
                    ->options([
                        '1' => 'Activo',
                        '0' => 'Baja',
                    ])
                    ->placeholder('Todos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => \Auth::user()->hasAnyRole('RH','RH Corp','Administrador')),
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\Fieldset::make('Información Personal')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->disabled(),
                                Forms\Components\TextInput::make('first_name')
                                    ->label('Primer Apellido')
                                    ->disabled(),
                                Forms\Components\TextInput::make('last_name')
                                    ->label('Segundo Apellido')
                                    ->disabled(),
                                Forms\Components\TextInput::make('curp')
                                    ->label('CURP')
                                    ->disabled(),
                                Forms\Components\TextInput::make('rfc')
                                    ->label('RFC')
                                    ->disabled(),
                                Forms\Components\TextInput::make('ims')
                                    ->label('ims')
                                    ->disabled(),
                                Forms\Components\TextInput::make('sex')
                                    ->label('Sexo')
                                    ->disabled(),
                                Forms\Components\TextInput::make('birthdate')
                                    ->label('Fecha de Nacimiento')
                                    ->disabled(),

                                Forms\Components\Textarea::make('address')
                                    ->label('Dirección')
                                    ->disabled(),
                                Forms\Components\TextInput::make('colony')
                                    ->label('Colonia')
                                    ->disabled(),
                                Forms\Components\TextInput::make('cp')
                                    ->label('Código Postal')
                                    ->disabled(),
                                Forms\Components\TextInput::make('state')
                                    ->label('Estado')
                                    ->disabled(),
                            ])->columns(3),

                        Forms\Components\Fieldset::make('Información de Contacto')
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Correo Electrónico')
                                    ->disabled(),
                                Forms\Components\TextInput::make('phone')
                                    ->label('Teléfono')
                                    ->disabled(),
                            ]),
                        Forms\Components\Fieldset::make('Información Laboral')
                            ->schema([
                                Forms\Components\BelongsToSelect::make('sede_id')
                                    ->label('Sede')
                                    ->relationship('sede', 'name')
                                    ->disabled(),
                                Forms\Components\BelongsToSelect::make('department_id')
                                    ->label('Departamento')
                                    ->relationship('department', 'name')
                                    ->disabled(),
                                Forms\Components\BelongsToSelect::make('position_id')
                                    ->label('Puesto')
                                    ->relationship('position', 'name')
                                    ->disabled(),
                                Forms\Components\TextInput::make('contract_type')
                                    ->label('Tipo de Contrato')
                                    ->disabled(),
                                Forms\Components\DatePicker::make('entry_date')
                                    ->label('Fecha de Ingreso')
                                    ->disabled(),
                            ])
                        ]),
                Tables\Actions\Action::make('bajaUsuario')
                    ->visible(fn () => \Auth::user()->hasAnyRole('RH','RH Corp','Administrador'))
                    ->label('Baja')
                    ->color('danger')
                    ->icon('heroicon-s-user-minus')
                    ->requiresConfirmation(false)
                    ->modalHeading('Formato de Baja de Usuario')
                    ->modalDescription('Por favor, complete la información para dar de baja del usuario.')
                    ->modalCloseButton()
                    ->modalCancelAction()
                    ->modalSubmitAction()
                    ->modalSubmitActionLabel('Dar de Baja')
                    ->form([
                        Forms\Components\Fieldset::make('Información de baja')
                            ->schema([
                                Forms\Components\DatePicker::make('termination_date')
                                    ->label('Fecha Efectiva de Baja')
                                    ->required()
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->termination_date ?? now()),
                                Forms\Components\Select::make('termination_type')
                                    ->label('Motivo de Baja')
                                    ->options([
                                        'renuncia_voluntaria' => 'Renuncia Voluntaria',
                                        'despido' => 'Despido',
                                        'abandono'=> 'Abandono',
                                        'terminacion_contrato' => 'Terminación de Contrato',
                                        'jubilacion' => 'Jubilación',
                                        'incapacidad' => 'Baja por Salud/Incapacidad',
                                        'otro' => 'Otro'
                                    ])
                                    ->reactive()
                                    ->required()
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->termination_type),
                                Forms\Components\TextInput::make('other_reason')
                                    ->label('Especificar Otro Motivo')
                                    ->visible(fn ($get) => $get('termination_type') === 'otro')
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->other_reason),

                                Forms\Components\Toggle::make('prior_notice')
                                    ->label('¿Dio previo aviso?')
                                    ->default(fn ($record) => (bool) optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->prior_notice)
                                    ->reactive(),
                                Forms\Components\TextInput::make('notice_days')
                                    ->label('Días de anticipación')
                                    ->type('number')
                                    ->visible(fn ($get) => $get('prior_notice'))
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->notice_days),
                                Forms\Components\Textarea::make('detailed_reason')
                                    ->label('Motivo Detallado de la Baja')
                                    ->required()
                                    ->columnSpanFull()
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->detailed_reason),
                            ])->columns(2),
                        Forms\Components\Fieldset::make('Desempeño')
                            ->schema([
                                Forms\Components\Select::make('performance')
                                    ->label('Desempeño General')
                                    ->options([
                                        'bueno' => 'Bueno',
                                        'regular' => 'Regular',
                                        'deficiente' => 'Deficiente'
                                    ])
                                    ->required()
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->performance),
                                Forms\Components\Textarea::make('performance_comments')
                                    ->label('Comentarios sobre Desempeño')
                                    ->required()
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->performance_comments),
                                Forms\Components\Textarea::make('supervisor_feedback')
                                    ->label('Retroalimentación del Jefe Inmediato')
                                    ->required()
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->supervisor_feedback),
                            ]),

                        Forms\Components\Fieldset::make('Proceso de entrega')
                            ->schema([
                                Forms\Components\CheckboxList::make('documents_delivered')
                                    ->label('Documentos Entregados')
                                    ->options([
                                        'carta_renuncia' => 'Carta de Renuncia',
                                        'finiquito' => 'Finiquito',
                                        'otros' => 'Otros Documentos',
                                    ])
                                    ->default(fn ($record) => (function ($record) {
                                        $term = \App\Models\UserTermination::where('user_id', $record->id)->latest()->first();
                                        if (! $term) {
                                            return [];
                                        }
                                        $docs = $term->documents_delivered;
                                        if (is_string($docs)) {
                                            $decoded = json_decode($docs, true);
                                            return is_array($decoded) ? $decoded : [];
                                        }
                                        if (is_array($docs)) {
                                            return $docs;
                                        }
                                        return [];
                                    })($record)),
                                Forms\Components\Toggle::make('settlement_completed')
                                    ->label('¿Baja Completada?')
                                    ->default(fn ($record) => (bool) optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->settlement_completed)
                                    ->reactive(),
                                Forms\Components\Textarea::make('settlement_details')
                                    ->label('Detalles de la Liquidación')
                                    ->visible(fn ($get) => $get('settlement_completed'))
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->settlement_details),
                            ]),
                        Forms\Components\Fieldset::make('Puesto de Trabajo')
                            ->schema([
                                Forms\Components\Toggle::make('impacts_team')
                                    ->label('¿Es una posición crítica para el equipo?')
                                    ->default(fn ($record) => (bool) optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->impacts_team)
                                    ->reactive(),
                                Forms\Components\Toggle::make('position_replaced')
                                    ->label('¿Requiere Reemplazo?')
                                    ->default(fn ($record) => (bool) optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->position_replaced)
                                    ->reactive(),
                                Forms\Components\Select::make('replacement_urgency')
                                    ->label('Urgencia del Reemplazo')
                                    ->options([
                                        'inmediato' => 'Inmediato',
                                        'proximos_dias' => 'Próximos Días',
                                        'proximas_semanas' => 'Próximas Semanas',
                                        'un_mes' => '1 Mes',
                                        'tres_meses' => '3 Meses',
                                        '6_meses' => '6 Meses',
                                        'un_año' => '1 Año'
                                    ])
                                    ->visible(fn ($get) => $get('position_replaced'))
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->replacement_urgency),
                            ]),
                        Forms\Components\Fieldset::make('Información Adicional')
                            ->schema([
                                Forms\Components\Textarea::make('additional_comments')
                                    ->label('Comentarios Adicionales')
                                    ->rows(4)
                                    ->columnSpanFull()
                                    ->default(fn ($record) => optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->additional_comments),
                                Forms\Components\Toggle::make('access_deactivated')
                                    ->label('¿Desactivar Acceso a Sistemas?')
                                    ->default(fn ($record) => (bool) optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->access_deactivated),

                                Forms\Components\Toggle::make('exit_interview')
                                    ->label('Enviar entrevista de salida')
                                    ->default(fn ($record) => (bool) optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->exit_interview),
                                Forms\Components\Toggle::make('re_hire')
                                    ->label('¿El colaborador es Recontratable?')
                                    ->helperText('Cuando el indicador está en verde (Sí), el colaborador puede ser recontratado.')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->required()
                                    ->default(fn ($record) => (bool) optional(\App\Models\UserTermination::where('user_id', $record->id)->latest()->first())->re_hire)
                            ]),
                    ])
                    ->action(function (array $data, User $record): void {
                        try {
                            $payload = [
                                'processed_by' => auth()->id(),
                                'termination_date' => $data['termination_date'] ?? null,
                                'termination_type' => $data['termination_type'] ?? null,
                                'other_reason' => $data['other_reason'] ?? null,
                                'prior_notice' => !empty($data['prior_notice']) ? 1 : 0,
                                'notice_days' => $data['notice_days'] ?? null,
                                'detailed_reason' => $data['detailed_reason'] ?? null,
                                'performance' => $data['performance'] ?? null,
                                'performance_comments' => $data['performance_comments'] ?? null,
                                'supervisor_feedback' => $data['supervisor_feedback'] ?? null,
                                'documents_delivered' => isset($data['documents_delivered']) ? json_encode($data['documents_delivered']) : null,
                                'settlement_completed' => !empty($data['settlement_completed']) ? 1 : 0,
                                'settlement_details' => $data['settlement_details'] ?? null,
                                'impacts_team' => !empty($data['impacts_team']) ? 1 : 0,
                                'position_replaced' => !empty($data['position_replaced']) ? 1 : 0,
                                'replacement_urgency' => $data['replacement_urgency'] ?? null,
                                'additional_comments' => $data['additional_comments'] ?? null,
                                'access_deactivated' => !empty($data['access_deactivated']) ? 1 : 0,
                                'exit_interview' => !empty($data['exit_interview']) ? 1 : 0,
                                're_hire' => !empty($data['re_hire']) ? 1 : 0,
                            ];

                            UserTermination::updateOrCreate(
                                ['user_id' => $record->id],
                                $payload
                            );
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error en la base de datos procesar la baja del usuario')
                                ->body($e->getMessage())
                                ->send();
                            return;
                        }

                        $record->update(['status' => false]);

                        Notification::make()
                            ->success()
                            ->title('Usuario dado de baja correctamente')
                            ->send();
                    }),
                Tables\Actions\Action::make('reactivarUsuario')
                    ->visible(fn (User $record) => !$record->status && \Auth::user()->hasAnyRole('RH','RH Corp','Administrador'))
                    ->label('Reactivar')
                    ->color('success')
                    ->icon('heroicon-s-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('Reactivar Usuario')
                    ->modalDescription(function (User $record) {
                        $termination = UserTermination::where('user_id', $record->id)->latest()->first();

                        if ($termination && $termination->re_hire == 0) {
                            return '⚠️ ADVERTENCIA: Este usuario fue dado de baja y NO es recontratable. ¿Está seguro de que desea reactivarlo?';
                        }

                        return '¿Está seguro de que desea reactivar este usuario?';
                    })
                    ->modalIcon(function (User $record) {
                        $termination = UserTermination::where('user_id', $record->id)->latest()->first();
                        return ($termination && $termination->re_hire == 0) ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-arrow-path';
                    })
                    ->modalIconColor(function (User $record) {
                        $termination = UserTermination::where('user_id', $record->id)->latest()->first();
                        return ($termination && $termination->re_hire == 0) ? 'warning' : 'success';
                    })
                    ->action(function (User $record): void {
                        try {
                            // Obtener el registro de terminación
                            $termination = UserTermination::where('user_id', $record->id)->latest()->first();
                            $isRehireable = $termination ? ($termination->re_hire == 1) : true;

                            // Reactivar el usuario
                            $record->update(['status' => true]);

                            // Obtener usuarios con el rol 'RH Corp'
                            $rhCorpUsers = User::whereHas('roles', function ($query) {
                                $query->where('name', 'RH Corp');
                            })->where('status', 1)->get();

                            // Enviar notificación a cada usuario con rol RH Corp
                                $userName=$record->name.' '.$record->first_name.' '.$record->last_name;
                                $statusText = $isRehireable ? 'Recontratable' : 'No recontratable';
                                $editBy=auth()->user();
                                $reactivated_by =$editBy->name . ' ' . $editBy->first_name . ' ' . $editBy->last_name;
                                $body ="El usuario '{$userName}' fue reactivado por {$reactivated_by }. Y el colaborador reintegrado tiene el estatus: {$statusText}";


                            foreach ($rhCorpUsers as $rhUser) {

                                try {
                                    Notification::make()
                                        ->title('Usuario Reactivado')
                                        ->warning()
                                        ->body($body)
                                        ->sendToDatabase($rhUser);

                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->danger()
                                        ->title('error al enviar notificación')
                                        ->body('Se daha deyecyadp un error'.$e->getMessage())
                                        ->send();
                                    Log::error('Error al enviar notificación: ' . $e->getMessage());
                                }
                                Log::info('Notificación enviada correctamente');

                            }

                            Notification::make()
                                ->success()
                                ->title('Usuario reactivado correctamente')
                                ->body('Se ha notificado al personal de RH Corp sobre la reactivación.')
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error al reactivar el usuario')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('downloadExitSurvey')
                    ->label('')
                    ->tooltip('Descargar Entrevista de Salida')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->url(fn (User $record) => route('users.download-exit-survey', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (User $record) =>
                        !$record->status &&
                        \App\Models\ExitSurvey::where('user_id', $record->id)->exists() &&
                        \Auth::user()->hasAnyRole('RH Corp', 'Administrador','RH')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn()=>VisorRoleHelper::canEdit()),
                ]),
                ExportBulkAction::make(),
            ])->modifyQueryUsing(function (Builder $query) {
                // Si el usuario tiene el rol "Jefe RH", filtrar por su sede_id
                if (auth()->user()->hasAnyRole('RH','Gerente')) {
                    $query->where('sede_id', \auth()->user()->sede_id);
                }elseif ( auth()->user()->hasRole('Supervisor') ) {
                    $supervisorId = auth()->user()->position_id;
                    $users = User::where('status', true)
                        ->whereNotNull('department_id')
                        ->whereNotNull('position_id')
                        ->whereNotNull('sede_id')
                        ->whereHas('position', function ($query) use ($supervisorId) {
                            $query->where('supervisor_id', $supervisorId);
                        })
                        ->pluck('users.id');

                    $query->whereIn('id', $users);
                }elseif ( !auth()->user()->hasAnyRole('RH Corp','Administrador','Director') ) {

                    $query->where('id',auth()->id());
                }
            });
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PortfolioRelationManager::class,
        ];
    }
    public static function getWidgets(): array
    {
        return [
          UsersStatsOverview::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
