<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationalClimateResource\Pages;
use App\Filament\Resources\OrganizationalClimateResource\RelationManagers;
use App\Models\OrganizationalClimate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrganizationalClimateResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return false;

    }
    protected static ?string $model = OrganizationalClimate::class;
    protected static ?string $navigationGroup = 'Evaluaciones';
    protected static ?string $navigationLabel = 'Clima Organizacional';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';

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
            'index' => Pages\ListOrganizationalClimates::route('/'),
            'create' => Pages\CreateOrganizationalClimate::route('/create'),
            'edit' => Pages\EditOrganizationalClimate::route('/{record}/edit'),
        ];
    }
}
