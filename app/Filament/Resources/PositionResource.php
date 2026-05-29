<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Filament\Resources\PositionResource\RelationManagers;
use App\Helpers\VisorRoleHelper;
use App\Models\Department;
use App\Models\Position;
use App\Models\Sede;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use PHPUnit\Util\Filter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Optra Estructura';
    protected static ?string $navigationLabel = 'Puestos';
    protected static ?int $navigationSort = 3;
    public static function canViewAny(): bool
    {
        return \auth()->user()->hasAnyRole('Administrador','RH Corp','Visor');

    }

    public static function form(Form $form): Form
    {
        $formulario= Section::make('Información del Puesto')
            ->description('Agrega la información del Puesto para cada departamento')
            ->icon('heroicon-m-identification')
            ->schema([
                Forms\Components\Select::make('sede')
                    ->label('Sede')
                    ->relationship('department.sede', 'name')
                    ->required()
                    ->reactive()
                    ->preload()
                    ->loadStateFromRelationshipsUsing(function ($state) {
                        return $state->department->sede_id ?? null;
                    })
                    ->afterStateUpdated(function (callable $set) {
                        $set('department_id', null);
                    }),

                Forms\Components\Select::make('department_id')
                    ->label('Departamento')
                    ->options(function (Get $get, ?string $state): Collection {
                        // Si estamos editando y hay un valor en state
                        if ($state) {
                            $departments = Department::where('sede_id', $get('sede'))
                                ->orWhere('id', $state)  // Incluye el departamento actual
                                ->get();
                        } else {
                            $departments = Department::where('sede_id', $get('sede'))->get();
                        }
                        return $departments->pluck('name', 'id');
                    })
                    ->required()
                    ->preload(),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Puesto')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('description')
                    ->label('Descripción del Puesto')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('order')
                    ->label('Orden Jerárquico del Puesto')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('supervisor_id')
                    ->label('Supervisor')
                    ->options(function (Get $get, ?string $state): Collection {
                        $selectedSedeId = $get('sede');

                        if (!$selectedSedeId) {
                            return Position::where('id', $state ?? 0)->pluck('name', 'id');
                        }

                        return Position::whereHas('department', function (Builder $query) use ($selectedSedeId) {
                            $query->where('sede_id', $selectedSedeId);
                        })
                            ->where('id', '!=', $state ?? 0) // Evitar que se seleccione a sí mismo
                            ->pluck('name', 'id');
                    })
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->reactive(),
                Forms\Components\Select::make('evaluation_grades')
                    ->label('Tipos de Evaluación 360')
                    ->helperText('Selecciona el tipo de evaluación 360 correspondiente al puesto')
                    ->options([
                        180 => '180',
                        270 => '270',
                        360 => '360',
                    ])
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label('Estatus')
                    ->default(true)
                    ->required(),
            ]);

        return $form->schema([
            $formulario,
        ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department.sede.name')
                    ->searchable()
                    ->label('Sede')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Puesto')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->label('Jefe Inmediato')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('department.name')
                    ->searchable()
                    ->label('Departamento')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('evaluation_grades')
                    ->label('Tipo de 360')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order')
                    ->label('Orden Jerarquico')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Estatus')
                    ->boolean(),
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
                Tables\Filters\Filter::make('sede')
                    ->form([
                        Forms\Components\Select::make('sede_id')
                            ->relationship('department.sede', 'name')
                            ->label('Sede')
                            ->preload()
                            ->searchable(),

                        Forms\Components\Select::make('department_id')
                                    ->label('Departamento')
                                    ->relationship('department', 'name', function (Builder $query, Get $get) {
                                        $sedeId = $get('sede_id');
                                        if ($sedeId) {
                                            $query->where('sede_id', $sedeId)->pluck('name', 'id');
                                        }
                                    })
                                    ->preload()
                                    ->reactive()
                                    ->searchable()
                    ])

            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn()=>VisorRoleHelper::canEdit()),
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modal(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn()=>VisorRoleHelper::canEdit()),
                    ExportBulkAction::make(),
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
            'index' => Pages\ListPositions::route('/'),
            'create' => Pages\CreatePosition::route('/create'),
            'edit' => Pages\EditPosition::route('/{record}/edit'),
        ];
    }
}
