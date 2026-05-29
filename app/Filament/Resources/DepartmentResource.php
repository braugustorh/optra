<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Filament\Resources\DepartmentResource\RelationManagers;
use App\Helpers\VisorRoleHelper;
use App\Models\Department;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;
    protected static ?string $navigationGroup = 'Optra Estructura';
    protected static ?string $navigationLabel = 'Departamentos';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    public static function canViewAny(): bool
    {
        return \auth()->user()->hasAnyRole('Administrador','RH Corp','Visor');

    }

    public static function form(Form $form): Form
    {
        $formulario=Section::make('Información del Departamento')
            ->description('Agrega la información del Departamento por cada una de las sedes')
            ->icon('heroicon-m-building-office')
            ->schema([
                Forms\Components\Select::make('sede_id')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->relationship('sede', 'name'),
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Departamento')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Toggle::make('status')
                    ->label('Estatus')
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
                Tables\Columns\TextColumn::make('sede.name')
                    ->searchable()
                    ->label('Sede')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre del Departamento')
                    ->searchable(),
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
                //agregar filtro para sedes
                Tables\Filters\SelectFilter::make('sede_id')
                    ->relationship('sede', 'name')
                    ->label('Sede')
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modal(),
                Tables\Actions\EditAction::make()->visible(fn()=>VisorRoleHelper::canEdit()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn()=>VisorRoleHelper::canEdit()),
                    ExportBulkAction::make(),
                ]),

            ])
            ->defaultSort('sede_id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
         RelationManagers\PositionsRelationManager::class,
            RelationManagers\UserRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
            'view' => Pages\ViewDepartment::route('/{record}'),
        ];
    }
}
