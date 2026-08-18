<?php

namespace App\Filament\Resources\CandidateResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';
    protected static ?string $title = 'Comentarios del Proceso';
    protected static ?string $icon = 'heroicon-o-chat-bubble-left-right';

    public function isReadOnly(): bool
    {
        // Filament hace los relation managers de solo lectura por defecto en la
        // página "Ver". Los comentarios deben poder crearse/editarse ahí mismo.
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('comment')
                ->label('Comentario')
                ->required()
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->columns([
                Tables\Columns\TextColumn::make('comment')
                    ->label('Comentario')
                    ->wrap()
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->columnSpanFull(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Autor')
                    ->icon('heroicon-o-user-circle')
                    ->placeholder('Usuario eliminado'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->since()
                    ->tooltip(fn ($record) => $record->created_at?->format('d/m/Y H:i'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Aún no hay comentarios')
            ->emptyStateDescription('Registra observaciones sobre entrevistas, referencias u otros puntos del proceso.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-right')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Comentario')
                    ->icon('heroicon-o-plus-circle')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->user_id === auth()->id()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->user_id === auth()->id()),
            ]);
    }
}
