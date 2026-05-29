<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ControlPanelResource\Pages;
use App\Filament\Resources\ControlPanelResource\RelationManagers;
use App\Models\ControlPanel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ControlPanelResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = ControlPanel::class;
    protected static ?string $navigationGroup = 'Configuraciones';
    protected static ?string $navigationLabel = 'Panel de Control';
    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return \auth()->user()->hasRole('Administrador');

    }

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListControlPanels::route('/'),
            'create' => Pages\CreateControlPanel::route('/create'),
            'edit' => Pages\EditControlPanel::route('/{record}/edit'),
        ];
    }
}
