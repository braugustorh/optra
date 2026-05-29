<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PsychometricEvaluationResource\Pages;
use App\Models\PsychometricEvaluation;
use App\Models\User;
use App\Models\Candidate;
use App\Models\EvaluationsTypes;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;

class PsychometricEvaluationResource extends Resource
{

    public static function canViewAny(): bool
    {

        return (\auth()->user()->hasAnyRole('Administrador'));

    }





    protected static ?string $model = PsychometricEvaluation::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Psicometrías';
    protected static ?string $modelLabel = 'Evaluación Psicométrica';
    protected static ?string $pluralModelLabel = 'Evaluaciones Psicométricas';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Evaluación')
                    ->schema([
                        Forms\Components\Select::make('evaluations_type_id')
                            ->label('Tipo de Evaluación')
                            ->relationship('evaluationType', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('evaluable_type')
                            ->label('Tipo de Evaluado')
                            ->options([
                                User::class => 'Colaboradores',
                                Candidate::class => 'Candidatos'
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('evaluable_id', null)),

                        Forms\Components\Select::make('evaluable_id')
                            ->label('Seleccionar Evaluado')
                            ->options(function (Forms\Get $get) {
                                $type = $get('evaluable_type');
                                if ($type === User::class) {
                                    return User::pluck('name', 'id')->toArray();
                                } elseif ($type === Candidate::class) {
                                    return Candidate::pluck('name', 'id')->toArray();
                                }
                                return [];
                            })
                            ->required()
                            ->searchable()
                            ->reactive(),

                        Forms\Components\Hidden::make('assigned_by')
                            ->default(auth()->id()),
                    ])->columns(2),

                Forms\Components\Section::make('Configuración')
                    ->schema([
                        Forms\Components\DateTimePicker::make('assigned_at')
                            ->label('Fecha de Asignación')
                            ->default(now())
                            ->required(),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Fecha Límite')
                            ->after('assigned_at'),

                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'assigned' => 'Asignada',
                                'started' => 'Iniciada',
                                'in_progress' => 'En Progreso',
                                'completed' => 'Completada',
                                'expired' => 'Expirada',
                            ])
                            ->default('assigned')
                            ->required(),

                        Forms\Components\TextInput::make('progress')
                            ->label('Progreso (%)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(0)
                            ->suffix('%'),
                    ])->columns(2),

                Forms\Components\Section::make('Instrucciones y Notas')
                    ->schema([
                        Forms\Components\Textarea::make('instructions')
                            ->label('Instrucciones')
                            ->placeholder('Instrucciones especiales para la evaluación...')
                            ->rows(3),

                        Forms\Components\Textarea::make('manual_notes')
                            ->label('Notas Manuales')
                            ->rows(3),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\ImageColumn::make('evaluable.avatar')
                        ->label('')
                        ->circular()
                        ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->getEvaluatedName()) . '&color=6366f1&background=e0e7ff')
                        ->size(40),

                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('evaluable_name')
                            ->label('Usuario')
                            ->getStateUsing(fn($record) => $record->getEvaluatedName())
                            ->weight(FontWeight::Medium)
                            ->size('sm'),

                        Tables\Columns\TextColumn::make('evaluable_info')
                            ->label('')
                            ->getStateUsing(function($record) {
                                if ($record->evaluable_type === User::class) {
                                    return $record->evaluable->position->name ?? 'Sin posición';
                                }
                                return 'Puesto: ' . ($record->evaluable->position_applied ?? 'N/A');
                            })
                            ->size('xs')
                            ->color('gray'),
                    ])->space(0),
                ])->from('md'),

                Tables\Columns\TextColumn::make('evaluationType.name')
                    ->label('Tipo')
                    ->weight(FontWeight::Medium)
                    ->badge()
                    ->color('info'),

                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ViewColumn::make('progress')
                        ->label('Progreso')
                        ->view('filament.tables.columns.progress-bar'),

                    Tables\Columns\TextColumn::make('progress_text')
                        ->getStateUsing(fn($record) => $record->progress . '%')
                        ->size('xs')
                        ->color('gray'),
                ])->space(1),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'assigned' => 'gray',
                        'started', 'in_progress' => 'info',
                        'completed' => 'success',
                        'expired' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'assigned' => 'Asignada',
                        'started' => 'Iniciada',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completada',
                        'expired' => 'Expirada',
                    }),

                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Asignada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'assigned' => 'Asignada',
                        'started' => 'Iniciada',
                        'in_progress' => 'En Progreso',
                        'completed' => 'Completada',
                        'expired' => 'Expirada'
                    ]),

                Tables\Filters\SelectFilter::make('evaluations_type_id')
                    ->label('Tipo de Evaluación')
                    ->relationship('evaluationType', 'name'),

                Tables\Filters\SelectFilter::make('evaluable_type')
                    ->label('Tipo de Evaluado')
                    ->options([
                        User::class => 'Colaboradores',
                        Candidate::class => 'Candidatos'
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('results')
                    ->label('Resultados')
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'completed')
                    ->url(fn($record) => route('filament.admin.resources.psychometric-evaluations.view', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('send_reminders')
                        ->label('Enviar Recordatorios')
                        ->icon('heroicon-o-envelope')
                        ->action(function($records) {
                            // Lógica para enviar recordatorios
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPsychometricEvaluations::route('/'),
           // 'create' => Pages\CreatePsychometricEvaluation::route('/create'),
           //'view' => Pages\ViewPsychometricEvaluation::route('/{record}'),
           // 'edit' => Pages\EditPsychometricEvaluation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'in_progress')->count();
    }
    //Se agrega función para que solo la persona con permiso pueda ver el recurso


}
