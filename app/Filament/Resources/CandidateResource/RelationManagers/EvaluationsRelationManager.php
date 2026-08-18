<?php

namespace App\Filament\Resources\CandidateResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'psychometricEvaluations';
    protected static ?string $title = 'Evaluaciones (agrupadas por batería)';
    protected static ?string $icon = 'heroicon-o-clipboard-document-check';

    public function isReadOnly(): bool
    {
        // Las evaluaciones se asignan desde el Dashboard de Psicometrías, no aquí.
        return true;
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('evaluationType'))
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('puesto')
                    ->label('Puesto de la batería'),
                Tables\Columns\TextColumn::make('evaluationType.name')
                    ->label('Prueba')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'Moss' => 'success',
                        'Cleaver' => 'warning',
                        'Kostick' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'completed' => 'success',
                        'assigned' => 'warning',
                        'started', 'in_progress' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Asignada')
                    ->date(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completada')
                    ->date(),
            ])
            ->groups([
                Tables\Grouping\Group::make('batch_id')
                    ->label('Batería')
                    ->getTitleFromRecordUsing(function ($record) {
                        $fecha = $record->assigned_at
                            ? \Carbon\Carbon::parse($record->assigned_at)->format('d/m/Y')
                            : 'Sin fecha';
                        return "{$record->puesto} — {$fecha}";
                    }),
            ])
            ->defaultGroup('batch_id')
            ->defaultSort('assigned_at', 'desc')
            ->emptyStateHeading('Sin evaluaciones asignadas')
            ->emptyStateDescription('Asigna una batería psicométrica desde el Dashboard de Psicometrías.')
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->actions([]);
    }
}
