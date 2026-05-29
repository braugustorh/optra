<?php

namespace App\Filament\Pages;

use App\Models\PsychometricEvaluation;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;

class MyPsychometricEvaluations extends Page implements HasTable
{
    /*public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->can('view-page my-psychometric-evaluations');
    }
    */

    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string $view = 'filament.pages.my-psychometric-evaluations';
    protected static ?string $title = 'Mis Evaluaciones Psicométricas';
    protected static ?string $navigationLabel = 'Mis Psicometrías';
    protected static ?string $navigationGroup = 'Mi Perfil'; // Puedes cambiar el grupo si lo deseas

    // =========================================================================
    // MAGIA DE UX: Solo mostrar en el menú si hay pruebas pendientes
    // =========================================================================
    public static function shouldRegisterNavigation(): bool
    {
        return PsychometricEvaluation::where('evaluable_type', User::class)
            ->where('evaluable_id', auth()->id())
            ->whereIn('status', ['assigned', 'started'])
            ->exists();
    }

    // =========================================================================
    // TABLA: Mostrar solo las pruebas del usuario autenticado
    // =========================================================================
    public function table(Table $table): Table
    {
        return $table
            ->query(
                PsychometricEvaluation::query()
                    ->where('evaluable_type', User::class)
                    ->where('evaluable_id', auth()->id())
                    ->whereIn('status', ['assigned', 'started'])
            )
            ->columns([
                TextColumn::make('evaluationType.name')
                    ->label('Prueba')
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('assigned_at')
                    ->label('Fecha de Asignación')
                    ->date('d M Y, h:i a')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'assigned' => 'warning',
                        'started' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'assigned' => 'Pendiente',
                        'started' => 'En Progreso',
                        default => $state,
                    }),

                TextColumn::make('progress')
                    ->label('Progreso')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->color('success'),
            ])
            ->actions([
                // Botón para ir a contestar la prueba (Fase 4)
                Action::make('take_test')
                    ->label(fn (PsychometricEvaluation $record) => $record->status === 'started' ? 'Continuar' : 'Comenzar')
                    ->icon('heroicon-o-play-circle')
                    ->color('primary')
                    ->button()
                    // CONECTAMOS A LA NUEVA PÁGINA
                    ->url(fn (PsychometricEvaluation $record) => TakeInternalEvaluation::getUrl(['record' => $record->id]))
            ])
            ->emptyStateHeading('¡Todo al día!')
            ->emptyStateDescription('No tienes ninguna evaluación psicométrica pendiente en este momento.')
            ->emptyStateIcon('heroicon-o-check-badge');
    }
}
