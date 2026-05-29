<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NineBoxResource\Pages;
use App\Filament\Resources\NineBoxResource\RelationManagers;
use App\Models\NineBox;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NineBoxResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = NineBox::class;
    protected static ?string $navigationGroup = 'Evaluaciones';
    protected static ?string $navigationLabel = 'NineBox';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    public static function canViewAny(): bool
    {
        return false;

    }

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
            'index' => Pages\ListNineBoxes::route('/'),
            'create' => Pages\CreateNineBox::route('/create'),
            'edit' => Pages\EditNineBox::route('/{record}/edit'),
        ];
    }
}
