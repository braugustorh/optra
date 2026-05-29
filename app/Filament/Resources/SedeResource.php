<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SedeResource\Pages;
use App\Filament\Resources\SedeResource\RelationManagers;
use App\Helpers\VisorRoleHelper;
use App\Models\Sede;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Set;
use Filament\Forms\Get;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class SedeResource extends Resource
{
    protected static ?string $model = Sede::class;
    public static ?string $cp = null;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Optra Estructura';
    public static function canViewAny(): bool
    {
        return \auth()->user()->hasAnyRole('Administrador','RH Corp','Visor');
    }

    public static function form(Form $form): Form
    {
        $formulario=Section::make('Catálogo de Departamentos')
            ->description('Agrega las sedes que conforman a la organización')
            ->icon('heroicon-m-building-office-2')
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre de la Sede')
                    ->required()
                    ->maxLength(150),
                Forms\Components\TextInput::make('cp')
                    ->label('Código Postal')
                    ->minLength(5)
                    ->maxLength(5)
                    ->lazy()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $cp = $get('cp');
                        if ($cp===null){
                            return $set('state', 'Capture CP');
                        }else{
                            if (strlen($cp) < 5){
                                return $set('state', 'cargando');
                            }else{
                                $response = Http::withHeaders([
                                    "Accept"=> "application/json",
                                    "APIKEY"=> "5e41fcafd8ee7e437980977e8b8ad009e357c2cd",
                                ])->get('https://api.tau.com.mx/dipomex/v1/codigo_postal?cp='.$cp);
                                $data = $response->json();
                                if ($data=== null){
                                    Notification::make()
                                        ->title('Error')
                                        ->danger()
                                        ->icon('heroicon-o-x-circle')
                                        ->iconColor('danger')
                                        ->body('No existe coincidencias para ese código postal')
                                        ->send();
                                    $set('state', null);
                                    $set('city', null);
                                }else{
                                    //dd($data);
                                    $set('city', $data['codigo_postal']['municipio']);
                                    return $set('state',$data['codigo_postal']['estado']);
                                    //return $set('state', $data['response']['asentamiento']);
                                }

                            }

                        }
                    }),
                    //->default(null),
                Forms\Components\TextInput::make('state')
                    ->label('Estado')
                    ->required()
                    ->live()
                    ->maxLength(100),
                Forms\Components\TextInput::make('city')
                    ->label('Ciudad')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('address')
                    ->label('Dirección')
                    ->maxLength(255)
                    ->placeholder('Calle, número, colonia, etc.')
                    ->default(null),
                Forms\Components\TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel()
                    ->maxLength(10)
                    ->default(null),
                Forms\Components\TextInput::make('open_positions')
                    ->label('Número de posiciones')
                    ->numeric()
                    ->required()
                    ->default(0),
                Forms\Components\Select::make('razonSocials')
                    ->label('Razones Sociales')
                    ->relationship('razonSocials', 'name')
                    ->multiple()
                    ->preload(),
                Forms\Components\Toggle::make('status')
                    ->label('Estatus')
                    ->required(),
            ])->columns(2);

        return $form->schema([
            $formulario
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('razonSocials.name')
                    ->label('Razones Sociales')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('positions_count')
                    ->label('Count')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $occupiedPositions = $record->count_positions($record->id); // Obtener el número de puestos ocupados
                        $openPositions = $record->open_positions; // Obtener el número total de posiciones abiertas
                        return "{$occupiedPositions} ocupadas de ".$openPositions??0;
                    }),
                Tables\Columns\TextColumn::make('state')
                    ->label('Estado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('cp')
                    ->label('Código Postal')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
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
                //
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
            'index' => Pages\ListSedes::route('/'),
            'create' => Pages\CreateSede::route('/create'),
            'edit' => Pages\EditSede::route('/{record}/edit'),
        ];
    }
}
