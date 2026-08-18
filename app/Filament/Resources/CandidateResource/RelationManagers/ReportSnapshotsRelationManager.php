<?php

namespace App\Filament\Resources\CandidateResource\RelationManagers;

use App\Filament\Resources\CandidateResource;
use App\Models\PsychometricEvaluation;
use App\Services\DeepSeekService;
use App\Services\GeneralReportService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class ReportSnapshotsRelationManager extends RelationManager
{
    protected static string $relationship = 'reportSnapshots';
    protected static ?string $title = 'Reportes Generados';
    protected static ?string $icon = 'heroicon-o-document-chart-bar';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('puesto_evaluado')
            ->columns([
                Tables\Columns\TextColumn::make('puesto_evaluado')
                    ->label('Puesto evaluado')
                    ->badge()
                    ->color(fn ($record) => $record->isOverride() ? 'warning' : 'gray')
                    ->formatStateUsing(fn ($state, $record) => $record->isOverride() ? "↺ {$state}" : $state),
                Tables\Columns\TextColumn::make('puesto_original')
                    ->label('Puesto original')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('ajuste_relativo')
                    ->label('Ajuste Relativo')
                    ->weight('bold')
                    ->color('primary')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('ajuste_global')
                    ->label('🛡️ Ajuste Global (seguridad)')
                    ->color('gray')
                    ->suffix('%'),
                Tables\Columns\TextColumn::make('dictamen')
                    ->label('Dictamen')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'ALINEACIÓN ÓPTIMA' => 'success',
                        'POTENCIAL CON PLAN DE DESARROLLO' => 'warning',
                        'POTENCIAL LATENTE' => 'gray',
                        'PERFIL NO ALINEADO AL PUESTO' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('generatedBy.name')
                    ->label('Generado por')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin reportes generados')
            ->emptyStateDescription('Genera un reporte de IA a partir de una batería completada, o reevalúa con otro puesto.')
            ->emptyStateIcon('heroicon-o-document-chart-bar')
            ->headerActions([
                Tables\Actions\Action::make('generate_report')
                    ->label('Generar / Reevaluar Reporte')
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('batch_id')
                            ->label('Batería de evaluaciones')
                            ->options(function () {
                                $candidate = $this->getOwnerRecord();

                                return PsychometricEvaluation::where('evaluable_type', get_class($candidate))
                                    ->where('evaluable_id', $candidate->id)
                                    ->get()
                                    ->groupBy('batch_id')
                                    ->map(function ($evals, $batchId) {
                                        $first     = $evals->first();
                                        $completed = $evals->where('status', 'completed')->count();
                                        $total     = $evals->count();
                                        $puesto    = $first->puesto ?? 'Sin puesto';
                                        $date      = $first->assigned_at
                                            ? \Carbon\Carbon::parse($first->assigned_at)->format('d/m/Y')
                                            : 'Sin fecha';
                                        $icon      = $completed === $total ? '✅' : ($completed > 0 ? '🔄' : '⏳');
                                        return "{$icon} {$puesto} — {$date} ({$completed}/{$total})";
                                    })
                                    ->toArray();
                            })
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('puesto_override')
                            ->label('Puesto para este cálculo')
                            ->helperText('Por defecto se usa el puesto original de la batería. Cámbialo para reevaluar sin repetir las pruebas.')
                            ->options(CandidateResource::puestoOptions())
                            ->native(false)
                            ->visible(fn (Forms\Get $get) => (bool) $get('batch_id')),
                    ])
                    ->action(function (array $data) {
                        $batchId = $data['batch_id'] ?? null;
                        if (! $batchId) {
                            Notification::make()->title('Selecciona una batería')->warning()->send();
                            return;
                        }

                        $generalService = new GeneralReportService();
                        $deepSeekService = app(DeepSeekService::class);

                        $output = $generalService->generateAiReport($batchId, $deepSeekService, $data['puesto_override'] ?? null);

                        if (isset($output['error'])) {
                            Notification::make()->title('Error al generar reporte')->body($output['error'])->danger()->send();
                            return;
                        }

                        Notification::make()
                            ->title('Reporte generado')
                            ->body('Puesto evaluado: ' . ($output['puesto_evaluado'] ?? 'N/A') . ' — Dictamen: ' . ($output['dictamen_calculado'] ?? ''))
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('ver_detalle')
                    ->label('Ver Detalle')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->modalHeading('Detalle del Reporte')
                    ->modalSubmitAction(false)
                    ->modalWidth('3xl')
                    ->modalContent(fn ($record) => view('filament.pages.partials.snapshot-detail', ['snapshot' => $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('comparar')
                    ->label('Comparar Seleccionados')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->deselectRecordsAfterCompletion()
                    ->modalHeading('Comparación de Reportes')
                    ->modalSubmitAction(false)
                    ->modalWidth('4xl')
                    ->modalContent(function (\Illuminate\Support\Collection $records) {
                        if ($records->count() < 2) {
                            return new HtmlString(
                                '<div style="padding:12px;color:#991b1b;">Selecciona al menos 2 reportes para comparar.</div>'
                            );
                        }

                        $generalService = new GeneralReportService();
                        $comparison = $generalService->compareSnapshots($records->pluck('id')->toArray());

                        return view('filament.pages.partials.snapshot-compare', ['comparison' => $comparison]);
                    })
                    ->action(fn () => null),
            ]);
    }
}
