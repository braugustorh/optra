<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class TransfersRelationManager extends RelationManager
{
    protected static string $relationship = 'transfers';
    protected static ?string $title = 'Historial de Traslados de Sede';
    protected static ?string $modelLabel = 'Traslado';
    protected static ?string $pluralModelLabel = 'Traslados';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('transfer_date')
                    ->label('Fecha de Traslado')
                    ->disabled(),
                Forms\Components\TextInput::make('reason')
                    ->label('Motivo')
                    ->disabled(),
                Forms\Components\TextInput::make('fromSede.name')
                    ->label('Sede de Origen')
                    ->disabled(),
                Forms\Components\TextInput::make('toSede.name')
                    ->label('Sede de Destino')
                    ->disabled(),
                Forms\Components\TextInput::make('toDepartment.name')
                    ->label('Departamento Asignado')
                    ->disabled(),
                Forms\Components\TextInput::make('toPosition.name')
                    ->label('Puesto Asignado')
                    ->disabled(),
                Forms\Components\TextInput::make('transferredBy.name')
                    ->label('Procesado por')
                    ->disabled(),
                Forms\Components\Textarea::make('notes')
                    ->label('Observaciones')
                    ->columnSpanFull()
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->defaultSort('transfer_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('transfer_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fromSede.name')
                    ->label('Sede Origen')
                    ->placeholder('N/A')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('toSede.name')
                    ->label('Sede Destino')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('toDepartment.name')
                    ->label('Departamento')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('toPosition.name')
                    ->label('Puesto')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Motivo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transferredBy.name')
                    ->label('Responsable')
                    ->formatStateUsing(function ($record) {
                        if (!$record->transferredBy) return '-';
                        return trim("{$record->transferredBy->name} {$record->transferredBy->first_name}");
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // No creación manual aquí, los traslados se gestionan por la acción de reasignación
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detalle'),
            ])
            ->bulkActions([
                // Auditoría protegida sin eliminación masiva
            ]);
    }
}
