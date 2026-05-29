<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RazonSocialResource\Pages;
use App\Filament\Resources\RazonSocialResource\RelationManagers;
use App\Models\RazonSocial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

class RazonSocialResource extends Resource
{
    public static function canViewAny(): bool
    {
        return \auth()->user()->hasAnyRole('Administrador','RH Corp','Visor');
    }
    protected static ?string $model = RazonSocial::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';
    protected static ?string $navigationGroup = 'Optra Estructura';
    protected static ?string $modelLabel = 'Razón Social';
    protected static ?string $pluralModelLabel = 'Razones Sociales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Razón Social')
                    ->description('Administra la información fiscal y legal de la empresa')
                    ->icon('heroicon-m-building-library')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre (Razón Social)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(250)
                            ->placeholder('Ej. Servicios Sedyco S.A. de C.V.')
                            ->columnSpan(2),
                        TextInput::make('code')
                            ->label('Código')
                            ->maxLength(4)
                            ->placeholder('Ej. SEDY')
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                        TextInput::make('rfc')
                            ->label('RFC')
                            ->maxLength(13)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Ej. SED123456XX1')
                            ->required()
                            ->extraInputAttributes(['style' => 'text-transform: uppercase;'])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                        Toggle::make('status')
                            ->label('Activo / Operativo')
                            ->inline(false)
                            ->default(true),
                        TextInput::make('fiscal_address')
                            ->label('Dirección Fiscal')
                            ->maxLength(255)
                            ->placeholder('Calle, No. Exterior, No. Interior, Colonia, C.P., Estado')
                            ->columnSpan(2),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rfc')
                    ->label('RFC')
                    ->searchable(),
                Tables\Columns\TextColumn::make('sedes.name')
                    ->label('Sedes')
                    ->badge()
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Estado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListRazonSocials::route('/'),
            'create' => Pages\CreateRazonSocial::route('/create'),
            'edit' => Pages\EditRazonSocial::route('/{record}/edit'),
        ];
    }
}
