<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PsychometryResource\Pages;
use App\Filament\Resources\PsychometryResource\RelationManagers;
use App\Helpers\VisorRoleHelper;
use App\Models\Psychometry;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class PsychometryResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Psychometry::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationGroup = 'Colaboradores';
    protected static ?string $navigationLabel = 'Talento';
    protected static ?string $name = 'Talento';
    protected static ?string $label = 'Talento';
    protected static ?string $title= 'Talento ';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()?->hasAnyRole([
            'Supervisor',
            'Administrador',
            'RH',
            'RH Corp',
            'Visor',
        ]);

    }
    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()?->hasAnyRole([
            'Administrador',
            'RH',
            'RH Corp',
        ]);
    }
    public static function canEdit(Model $record): bool
    {
        return (\auth()->user()->hasAnyRole('RH','RH Corp','Administrador'));
    }


    public static function form(Form $form): Form
    {
       /* $forms=Section::make('Psicometría')
            ->description('Genera el registro por cada colaborador del resultado de la prueba psicométrica. Puedes subir un documento de respaldo en formato PDF.')
            ->icon('heroicon-m-puzzle-piece')
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Usuario')
                    ->searchable()
                    ->options(function (Get $get):Collection {
                        if ($get('user_id')!==null){
                            $user= User::query()
                                ->where('id', $get('user_id'))
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [$user->id => $user->name.' '.$user->first_name .' '.$user->last_name]);
                        }else{
                            $user= User::query()
                                ->doesntHave('psychometry')
                                ->with('psychometry') // Incluye la relación 'portfolio' en la consulta
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [
                                    $user->id => $user->name.' '.$user->firts_name.' '.$user->last_name
                                ]);
                        }
                        return $user;
                    })
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('test_name')
                    ->label('Tipo de Prueba')
                    ->options([
                        'Diectivo'=>'Plan de Desarrollo Directivo',
                        'Mando Medio'=>'Plan de Desarrollo Mando Medio',
                        'Supervisor' =>'Plan de Desarrollo Supervisor',
                        'Administrativo'=>'Plan de Desarrollo Administrativo',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('test_description')
                    ->label('Descripción de la Prueba')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan('full'),
                Forms\Components\Datepicker::make('application_date')
                    ->label('Fecha de Aplicación')
                    ->required(),
                Forms\Components\Datepicker::make('expiration_date')
                    ->label('Fecha de Expiración')
                    ->gte('application_date')
                    ->required(),
                Forms\Components\Textarea::make('comments')
                    ->label('Comentarios')
                    ->columnSpan('full')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('result_url')
                    ->label('Documento de Respaldo')
                    ->acceptedFileTypes(['application/pdf'])
                    ->downloadable('true')
                    ->required()
                    ->columnSpan('full'),
                Forms\Components\FileUpload::make('interpretation_url')
                    ->label('Interpretación del Resultado')
                    ->acceptedFileTypes(['application/pdf'])
                    ->downloadable('true')
                    ->required()
                    ->columnSpan('full'),
        ])
            ->columns(2);
       */

        return $form->schema([
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Psicometría')
                        ->description('Genera el registro por cada colaborador del resultado de la prueba psicométrica. Puedes subir un documento de respaldo en formato PDF.')
                        ->icon('heroicon-m-puzzle-piece')
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->label('Usuario')
                                ->searchable()
                                ->options(function (Get $get):Collection {
                                    if ($get('user_id')!==null){
                                        $user= User::query()
                                            ->where('id', $get('user_id'))
                                            ->get()
                                            ->mapWithKeys(fn (User $user): array => [$user->id => $user->name.' '.$user->first_name .' '.$user->last_name]);
                                    }else{
                                        $user= User::query()
                                            ->doesntHave('psychometry')
                                            ->with('psychometry') // Incluye la relación 'portfolio' en la consulta
                                            ->get()
                                            ->mapWithKeys(fn (User $user): array => [
                                                $user->id => $user->name.' '.$user->first_name.' '.$user->last_name
                                            ]);
                                    }
                                    return $user;
                                })
                                ->preload()
                                ->required(),
                            Forms\Components\Select::make('test_name')
                                ->label('Tipo de Prueba')
                                ->options([
                                    'Directivo'=>'Plan de Desarrollo Directivo',
                                    'Mando Medio'=>'Plan de Desarrollo Mando Medio',
                                    'Supervisor' =>'Plan de Desarrollo Supervisor',
                                    'Administrativo'=>'Plan de Desarrollo Administrativo',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('test_description')
                                ->label('Descripción de la Prueba')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan('full'),
                            Forms\Components\DatePicker::make('application_date')
                                ->label('Fecha de Aplicación')
                                ->required(),
                            Forms\Components\DatePicker::make('expiration_date')
                                ->label('Fecha de Expiración')
                                ->gte('application_date')
                                ->required(),
                            Forms\Components\Textarea::make('comments')
                                ->label('Comentarios')
                                ->columnSpan('full')
                                ->maxLength(255),
                            Forms\Components\FileUpload::make('result_url')
                                ->label('Documento de Respaldo')
                                ->disk('sedyco_disk')
                                ->visibility('public')
                                ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                                ->acceptedFileTypes(['application/pdf'])
                                ->downloadable('true')
                                ->openable('true')
                                ->columnSpan('full'),
                            Forms\Components\FileUpload::make('interpretation_url')
                                ->label('Interpretación del Resultado')
                                ->acceptedFileTypes(['application/pdf'])
                                ->label('Documento de Respaldo')
                                ->disk('sedyco_disk')
                                ->visibility('public')
                                ->directory(fn (Get $get): string => "portafolio/{$get('user_id')}")
                                ->downloadable('true')
                                ->openable('true')
                                ->columnSpan('full'),
                        ])->columns(2),
                    Forms\Components\Section::make('Resultados')
                        ->schema([
                            Forms\Components\TextInput::make('leadership')
                                ->label('Liderazgo')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),
                            Forms\Components\TextInput::make('communication')
                                ->label('Comunicación')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),
                            Forms\Components\TextInput::make('conflict_management')
                                ->label('Manejo de Conflictos')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),
                            Forms\Components\TextInput::make('negotiation')
                                ->label('Negociación')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),
                            Forms\Components\TextInput::make('organization')
                                ->label('Organización')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),
                            Forms\Components\TextInput::make('problem_analysis')
                                ->label('Análisis de Problemas')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),
                            Forms\Components\TextInput::make('decision_making')
                                ->label('Toma de Decisiones')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),
                            Forms\Components\TextInput::make('strategic_thinking')
                                ->label('Pensamiento Estratégico')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),
                            Forms\Components\TextInput::make('resilience')
                                ->label('Resiliencia')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),
                            Forms\Components\TextInput::make('focus_on_results')
                                ->label('Enfoque en Resultados')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),

                            Forms\Components\TextInput::make('teamwork')
                                ->label('Trabajo en Equipo')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),

                            Forms\Components\TextInput::make('willingness_service')
                                ->label('Disposición de Servicio')
                                ->numeric()
                                ->step(1)
                                ->minValue(0)
                                ->maxValue(5)
                                ->inputMode('decimal')
                                ->required(),
                        ])->columns(4),
                ])->columnSpan(['lg' => 2]),

            //

        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('test_name')
                    ->label('Nombre de la Prueba')
                    ->searchable(),
                Tables\Columns\TextColumn::make('test_description')
                    ->label('Descripción de la Prueba')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('result_url')
                    ->label('Documento de Respaldo')
                    ->icon(function ($record): string {
                        $url =$record->result_url;
                        return $url ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle';

                    })->color(function ($record): string {
                        $url =$record->result_url;
                        return $url ? 'success' : 'danger';
                    })->alignCenter(),
                Tables\Columns\IconColumn::make('interpretation_url')
                    ->label('Interpretación de la prueba')
                    ->icon(function ($record): ?string {
                        $url = $record->interpretation_url;
                        return $url ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle';
                    })
                    ->color(function ($record): string {
                        $url = $record->interpretation_url;
                        return $url ? 'success' : 'danger';
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('comments')
                    ->label('Comentarios Adicionales')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('leadership')
                    ->label('Liderazgo')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('communication')
                    ->label('Comunicación')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('conflict_management')
                    ->label('Manejo de Conflictos')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('negotiation')
                    ->label('Negoziación')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('organization')
                    ->label('Organización')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('problem_analysis')
                    ->label('Análisis de Problemas')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('decision_making')
                    ->label('Toma de Decisiones')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('strategic_thinking')
                    ->label('Pensamiento Estratégico')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('resilience')
                    ->label('Resilencia')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('focus_on_results')
                    ->label('Enfoque en Resultados')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('teamwork')
                    ->label('Trabajo en Equipo')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('willingness_service')
                    ->label('Actitud de Servicio')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('application_date')
                    ->date()
                    ->toggleable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiration_date')
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
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modal(),
                Tables\Actions\EditAction::make()
                    ->visible(fn()=>VisorRoleHelper::canEdit()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn()=>VisorRoleHelper::canEdit()),
                ]),
            ])->modifyQueryUsing(function (Builder $query) {
                // Si el usuario tiene el rol "Jefe RH", filtrar por su sede_id
                if (auth()->check() && auth()->user()?->hasRole('RH')) {
                    $users = User::where('status', true)
                        ->whereNotNull('department_id')
                        ->whereNotNull('position_id')
                        ->whereNotNull('sede_id')
                        ->where('sede_id', auth()->user()?->sede_id)
                        ->get();

                    $query->whereIn('user_id', $users->pluck('id'));
                }elseif(auth()->check() && auth()->user()?->hasRole('Supervisor')){
                    $supervisorId = auth()->user()?->position_id;
                    $users = User::where('status', true)
                        ->whereNotNull('department_id')
                        ->whereNotNull('position_id')
                        ->whereNotNull('sede_id')
                        ->whereHas('position', function ($query) use ($supervisorId) {
                            $query->where('supervisor_id', $supervisorId);
                        })
                        ->get();
                    $query->whereIn('user_id', $users->pluck('id'));
                }
            });
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPsychometries::route('/'),
            'create' => Pages\CreatePsychometry::route('/create'),
            'edit' => Pages\EditPsychometry::route('/{record}/edit'),
        ];
    }
}
