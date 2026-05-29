<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacationBalanceResource\Pages;
use App\Models\VacationBalance;
use App\Services\VacationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VacationBalanceResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = VacationBalance::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Balances de Vacaciones';
    protected static ?string $modelLabel = 'Balance de Vacaciones';
    protected static ?string $pluralModelLabel = 'Balances de Vacaciones';
    protected static ?string $navigationGroup = 'Gestión de Vacaciones';

    public static function canViewAny(): bool
    {
        return Auth::user()->hasAnyRole(['RH', 'RH Corp']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Balance')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Colaborador')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->disabled(fn ($livewire) => $livewire instanceof Pages\EditVacationBalance),

                        Forms\Components\TextInput::make('year')
                            ->label('Año')
                            ->numeric()
                            ->required()
                            ->disabled(),

                        Forms\Components\DatePicker::make('period_start')
                            ->label('Inicio de Período')
                            ->required()
                            ->disabled(),

                        Forms\Components\DatePicker::make('period_end')
                            ->label('Fin de Período')
                            ->required()
                            ->disabled(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Días de Vacaciones')
                    ->schema([
                        Forms\Components\TextInput::make('total_days')
                            ->label('Total de Días')
                            ->numeric()
                            ->required()
                            ->helperText('Días totales según antigüedad (LFT)')
                            ->disabled(),

                        Forms\Components\TextInput::make('used_days')
                            ->label('Días Usados')
                            ->numeric()
                            ->default(0)
                            ->helperText('Días ya tomados por el colaborador'),

                        Forms\Components\TextInput::make('pending_days')
                            ->label('Días Pendientes')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Días en solicitudes pendientes'),

                        Forms\Components\TextInput::make('available_days')
                            ->label('Días Disponibles')
                            ->numeric()
                            ->disabled()
                            ->helperText('Total - Usados - Pendientes'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Colaborador')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.sede.name')
                    ->label('Sede')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('year')
                    ->label('Año')
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Inicio Período')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_end')
                    ->label('Fin Período')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_days')
                    ->label('Total')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('used_days')
                    ->label('Usados')
                    ->alignCenter()
                    ->sortable()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('pending_days')
                    ->label('Pendientes')
                    ->alignCenter()
                    ->sortable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('available_days')
                    ->label('Disponibles')
                    ->alignCenter()
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->label('Año')
                    ->options(fn () => VacationBalance::query()
                        ->distinct()
                        ->pluck('year', 'year')
                        ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('sede_id')
                    ->label('Sede')
                    ->relationship('user.sede', 'name')
                    ->visible(fn () => Auth::user()->hasRole('RH Corp')),

                Tables\Filters\Filter::make('sin_dias')
                    ->label('Sin días disponibles')
                    ->query(fn (Builder $query) => $query->where('available_days', '<=', 0)),

                Tables\Filters\Filter::make('con_dias')
                    ->label('Con días disponibles')
                    ->query(fn (Builder $query) => $query->where('available_days', '>', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('recalculate')
                    ->label('Recalcular')
                    ->icon('heroicon-o-calculator')
                    ->requiresConfirmation()
                    ->action(function (VacationBalance $record) {
                        VacationService::createOrUpdateBalance($record->user);
                    })
                    ->successNotificationTitle('Balance recalculado correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('user.name', 'asc')
            ->modifyQueryUsing(fn (Builder $query) => self::applyUserScopeQuery($query));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVacationBalances::route('/'),
            'create' => Pages\CreateVacationBalance::route('/create'),
            'edit' => Pages\EditVacationBalance::route('/{record}/edit'),
        ];
    }

    protected static function applyUserScopeQuery(Builder $query): Builder
    {
        $user = Auth::user();

        // RH Corp ve todas
        if ($user->hasRole('RH Corp')) {
            return $query;
        }

        // RH solo ve de su sede
        if ($user->hasRole('RH')) {
            return $query->whereHas('user', function ($q) use ($user) {
                $q->where('sede_id', $user->sede_id);
            });
        }

        return $query;
    }
}
