<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacationRequestResource\Pages;
use App\Filament\Resources\VacationRequestResource\RelationManagers;
use App\Models\User;
use App\Models\VacationRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Services\VacationService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class VacationRequestResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

       protected static ?string $model = VacationRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Solicitudes de Vacaciones';
    protected static ?string $modelLabel = 'Solicitud de Vacaciones';
    protected static ?string $pluralModelLabel = 'Solicitudes de Vacaciones';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Solicitud')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Fecha de Inicio')
                            ->required()
                            ->minDate(now())
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                            self::calculateDays($get, $set)
                            ),

                        Forms\Components\DatePicker::make('end_date')
                            ->label('Fecha de Fin')
                            ->required()
                            ->minDate(fn (callable $get) => $get('start_date'))
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                            self::calculateDays($get, $set)
                            ),

                        Forms\Components\TextInput::make('days_requested')
                            ->label('Días Solicitados')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\Textarea::make('comments')
                            ->label('Comentarios')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn ($livewire) => $livewire instanceof Pages\CreateVacationRequest ||
                        ($livewire instanceof Pages\EditVacationRequest &&
                            $livewire->record->status === 'pending')),

                Forms\Components\Section::make('Aprobación')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'pending' => 'Pendiente',
                                'approved' => 'Aprobada',
                                'rejected' => 'Rechazada',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motivo de Rechazo')
                            ->rows(3)
                            ->visible(fn (callable $get) => $get('status') === 'rejected')
                            ->required(fn (callable $get) => $get('status') === 'rejected'),
                    ])
                    ->visible(fn ($livewire) => $livewire instanceof Pages\EditVacationRequest &&
                        self::canApprove()),
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

                Tables\Columns\TextColumn::make('sede.name')
                    ->label('Sede')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('days_requested')
                    ->label('Días')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                    }),

                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Aprobada por')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha Solicitud')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                    ]),

                Tables\Filters\SelectFilter::make('sede_id')
                    ->label('Sede')
                    ->relationship('sede', 'name')
                    ->visible(fn () => self::isRHCorp()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (VacationRequest $record) =>
                        $record->status === 'pending' && self::canApprove()
                    ),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => self::applyUserScopeQuery($query));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVacationRequests::route('/'),
            'create' => Pages\CreateVacationRequest::route('/create'),
          //  'view' => Pages\ViewVacationRequest::route('/{record}'),
            'edit' => Pages\EditVacationRequest::route('/{record}/edit'),
        ];
    }

    protected static function calculateDays(callable $get, callable $set): void
    {
        $startDate = $get('start_date');
        $endDate = $get('end_date');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            $days = VacationService::calculateBusinessDays($start, $end);
            $set('days_requested', $days);
        }
    }

    protected static function canApprove(): bool
    {
        $user = Auth::user();

        return $user->hasAnyRole(['RH', 'RH Corp', 'Supervisor', 'Gerente', 'Director']);
    }

    protected static function isRHCorp(): bool
    {
        return Auth::user()->hasRole('RH Corp');
    }

    public static function applyUserScopeQuery(Builder $query): Builder
    {
        $user = Auth::user();

        // RH Corp ve todas
        if ($user->hasRole('RH Corp')) {
            return $query;
        }

        // RH ve solo de su sede
        if ($user->hasRole('RH')) {
            return $query->where('sede_id', $user->sede_id);
        }

        // Supervisores, Gerentes y Directores ven solicitudes de sus subordinados
        if ($user->hasAnyRole(['Supervisor', 'Gerente', 'Director'])) {
            $subordinateIds = self::getSubordinateIds($user);
            return $query->whereIn('user_id', $subordinateIds)
                ->orWhere('user_id', $user->id);
        }

        // Otros usuarios solo ven sus propias solicitudes
        return $query->where('user_id', $user->id);
    }

    protected static function getSubordinateIds($user): array
    {
        // Obtener IDs de usuarios que tienen como supervisor al usuario actual
        return \App\Models\User::whereHas('position', function ($q) use ($user) {
            $q->where('supervisor_id', $user->position_id);
        })->pluck('id')->toArray();
    }
}
