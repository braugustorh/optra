<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Filament\Resources\CampaignResource\RelationManagers;
use App\Helpers\VisorRoleHelper;
use App\Models\Campaign;
use App\Models\EvaluationsTypes;
use App\Models\Sede;
use App\Models\Evaluation;
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
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;


class CampaignResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-s-calendar-days';
    protected static ?string $navigationLabel = 'Campañas';
    protected static ?string $navigationGroup = 'Configurar Evaluaciones';
    protected static ?int $navigationSort = 5;
    protected static ?string $label = 'Campañas';

    public static function canViewAny(): bool
    {
        return \auth()->user()->hasAnyRole('Administrador','RH Corp','Visor');

    }
    public static function canCreate(): bool
    {
        return \auth()->user()->hasAnyRole('Administrador','RH Corp','RH');
    }
    public static function canEdit(Model $record): bool
    {
        return (\auth()->user()->hasAnyRole('RH Corp','Administrador','RH'));
    }

    public static function form(Form $form): Form
    {
        $formCampaigns= Section::make('Campañas')
            ->description('Agrega la información del Puesto para cada departamento')
            ->icon('heroicon-s-calendar-days')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la Campaña')
                    ->required(),
                /*Forms\Components\MultiSelect::make('evaluations_id')
                    ->label('Evaluations')
                    ->options(fn (Get $get): Collection => EvaluationsTypes::query()
                        ->pluck('name', 'id'))
                    ->preload()
                    ->required(),
                Forms\Components\MultiSelect::make('sedes_id')
                    ->label('Sedes')
                    ->options(fn (Get $get): Collection => Sede::query()
                        ->pluck('name', 'id'))
                    ->preload()
                    ->required(),*/
                Forms\Components\Select::make('evaluations')
                    ->multiple()
                    ->relationship('evaluations', 'name')
                    ->label('Evaluaciones')
                    ->required()
                    ->preload(),

                Forms\Components\Select::make('sedes')
                    ->multiple()
                    ->relationship('sedes', 'name')
                    ->label('Sedes')
                    ->required()
                    ->preload(),
                Forms\Components\Textarea::make('description')
                    ->label('Description'),
                Forms\Components\DatePicker::make('start_date')
                    ->label('Fecha de Inicio')
                    ->default(Carbon::now()->startOfDay())
                    ->reactive()  // Permite que el campo reaccione a cambios
                    ->afterStateUpdated(function (callable $set, $get, $state) {
                        $currentDate = Carbon::now()->startOfDay();

                        if (Carbon::parse($state)->greaterThan($currentDate)) {
                            $set('status', 'Programada'); // Establecer como Programada
                           // $set('isStatusDisabled', true); // Deshabilitar el select de estatus
                        } else  {
                            $set('status', 'Activa'); // Establecer como Activa
                           // $set('isStatusDisabled', false); // Habilitar el select si no cumple la condición
                        }
                    })
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->label('Fecha de Termino')
                    ->minDate(Carbon::now()->startOfDay())
                    ->reactive()  // Permite que el campo reaccione a cambios
                   ->gte('start_date') // La fecha de termino debe ser mayor o igual a la de inicio
                    ->validationMessages([
                        'gte' => 'La fecha de termino debe ser mayor a la de inicio',
                    ])
                    ->afterStateUpdated(function (callable $set, $state) {
                        $currentDate = Carbon::now()->startOfDay();

                        if (Carbon::parse($state)->greaterThanOrEqualTo($currentDate) ) {
                            //dd('entro');
                            $set('status', 'Programada'); // Establecer como Programada
                           // $set('isStatusDisabled', true); // Deshabilitar el select de estatus
                        } elseif (Carbon::parse($state)->greaterThanOrEqualTo($currentDate) ) {
                            //$set('status', 'Concluida'); // Establecer como Activa
                           // $set('isStatusDisabled', false); // Habilitar el select si no cumple la condición
                        }
                    })
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Estatus')
                    ->options([
                        'Activa' => 'Activa',
                        'Programada' => 'Programada',
                        'Concluida' => 'Concluida',
                        'Suspendida' => 'Suspendida',
                        'Cancelada' => 'Cancelada',
                    ])
            ]);


        return $form
            ->schema([
                $formCampaigns,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label('Nombre de la Campaña')
                ->searchable(),
                Tables\Columns\TextColumn::make('evaluations')
                    ->label('Evaluaciones')
                    ->formatStateUsing(function ($record) {
                        return implode(', ', $record->evaluations->pluck('name')->toArray());
                    })
                ->wrap(),
                Tables\Columns\TextColumn::make('sedes')
                    ->label('Sedes')
                    ->wrap()
                    ->formatStateUsing(function ($record) {
                        return implode(', ', $record->sedes->pluck('name')->toArray());
                    })
                    ->size('md')
                ->searchable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha de Inicio')
                ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha de Termino')
                ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estatus')
                    ->colors([
                        'primary' => 'Activa',
                        'info' => 'Programada',
                        'success' => 'Concluida',
                        'warning' => 'Suspendida',
                        'danger' => 'Inactivo',
                    ])
                ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estatus')
                    ->options([
                        'Activa' => 'Activa',  // true
                        'Programada' => 'Programada',  // false
                        'Concluida' => 'Concluida',  // false
                        'Suspendida' => 'Suspendida',  // false
                        'Cancelada' => 'Cancelada',  // false
                    ])
                    ->query(function (Builder $query, $state) {
                        if ($state['value']!==null){

                            return $query->where('status',$state);
                        }
                    }),

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
            ]);
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
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }
}
