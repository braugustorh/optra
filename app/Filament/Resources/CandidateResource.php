<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CandidateResource\Pages;
use App\Filament\Resources\CandidateResource\RelationManagers;
use App\Models\Candidate;
use App\Models\PsychometricEvaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Psicometría';
    protected static ?string $navigationLabel = 'Gestión de Candidatos';
    protected static ?string $modelLabel = 'Candidato';
    protected static ?string $pluralModelLabel = 'Candidatos';
    protected static ?int $navigationSort = 2;

    /**
     * Badge en el menú de navegación con el número de candidatos "En Proceso",
     * para que RH tenga visibilidad inmediata de la carga de trabajo pendiente.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = Candidate::where('status', Candidate::STATUS_EN_PROCESO)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    /**
     * Colores de badge por puesto, consistentes con PsychometricDashboard.
     */
    public static function puestoColor(?string $puesto): string
    {
        return match ($puesto) {
            'Directivo'      => 'danger',
            'Gerencia'       => 'warning',
            'Mando Medio'    => 'primary',
            'Supervisor'     => 'info',
            'Administrativo' => 'success',
            default          => 'gray',
        };
    }

    /**
     * Íconos por estatus del pipeline, usados en tabla, tabs e infolist.
     */
    public static function statusIcon(?string $status): string
    {
        return match ($status) {
            Candidate::STATUS_EN_PROCESO    => 'heroicon-m-clock',
            Candidate::STATUS_CONTRATADO    => 'heroicon-m-check-badge',
            Candidate::STATUS_BANCO_TALENTO => 'heroicon-m-archive-box-arrow-down',
            Candidate::STATUS_ARCHIVADO     => 'heroicon-m-archive-box',
            default                         => 'heroicon-m-question-mark-circle',
        };
    }

    /**
     * Puestos disponibles, reutilizando el mismo catálogo que PsychometricDashboard
     * para no duplicar la matriz de niveles jerárquicos.
     */
    public static function puestoOptions(): array
    {
        return [
            'Directivo'      => 'Directivo (Dirección General / Área / Subdirección)',
            'Gerencia'       => 'Gerencia (Corporativa / Coordinador Senior)',
            'Mando Medio'    => 'Mando Medio (Gerencia B / Jefatura)',
            'Supervisor'     => 'Supervisor / Analista Senior',
            'Administrativo' => 'Administrativo / Auxiliar / Operativo',
        ];
    }

    public static function canViewAny(): bool
    {
        return \auth()->user()->hasAnyRole(['Administrador', 'RH Corp', 'RH']);
    }

    public static function canChangeStatus(): bool
    {
        return \auth()->user()->hasAnyRole(['Administrador', 'RH Corp']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del Candidato')
                ->description('Información de contacto y estatus dentro del proceso de selección.')
                ->icon('heroicon-o-identification')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre completo')
                        ->prefixIcon('heroicon-o-user')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo')
                        ->prefixIcon('heroicon-o-envelope')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->prefixIcon('heroicon-o-phone')
                        ->tel()
                        ->maxLength(50),
                    Forms\Components\Select::make('position_applied')
                        ->label('Puesto al que aplica')
                        ->prefixIcon('heroicon-o-briefcase')
                        ->options(self::puestoOptions())
                        ->native(false),
                    Forms\Components\Select::make('status')
                        ->label('Estatus del proceso')
                        ->options(Candidate::STATUS_LABELS)
                        ->native(false)
                        ->required()
                        ->disabled(fn () => ! self::canChangeStatus()),
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas generales')
                        ->columnSpanFull()
                        ->rows(3),
                ]),
        ]);
    }

    /**
     * Vista de detalle (página "Ver") con un layout tipo ficha, más legible que
     * el formulario deshabilitado por defecto.
     */
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            InfoSection::make('Perfil del Candidato')
                ->description('Datos de contacto y estatus actual dentro del proceso de selección.')
                ->icon('heroicon-o-identification')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')
                        ->label('Nombre')
                        ->icon('heroicon-o-user')
                        ->weight('bold')
                        ->columnSpan(2),
                    TextEntry::make('status')
                        ->label('Estatus')
                        ->badge()
                        ->formatStateUsing(fn (?string $state) => Candidate::STATUS_LABELS[$state] ?? $state)
                        ->color(fn (?string $state) => Candidate::STATUS_COLORS[$state] ?? 'gray')
                        ->icon(fn (?string $state) => self::statusIcon($state)),
                    TextEntry::make('email')
                        ->label('Correo')
                        ->icon('heroicon-o-envelope')
                        ->copyable()
                        ->copyMessage('Correo copiado'),
                    TextEntry::make('phone')
                        ->label('Teléfono')
                        ->icon('heroicon-o-phone')
                        ->placeholder('—'),
                    TextEntry::make('position_applied')
                        ->label('Puesto aplicado')
                        ->state(fn (Candidate $record) => $record->effectivePosition())
                        ->badge()
                        ->color(fn (?string $state) => self::puestoColor($state))
                        ->formatStateUsing(fn (?string $state, Candidate $record) => $state && ! $record->position_applied
                            ? "{$state} (según última batería)"
                            : $state)
                        ->placeholder('Sin puesto asignado'),
                    TextEntry::make('notes')
                        ->label('Notas generales')
                        ->columnSpanFull()
                        ->placeholder('Sin notas registradas.'),
                ]),
            InfoSection::make('Resumen del Proceso')
                ->icon('heroicon-o-chart-bar')
                ->columns(3)
                ->schema([
                    TextEntry::make('evaluations_summary')
                        ->label('Evaluaciones asignadas')
                        ->state(fn (Candidate $record) => (string) $record->psychometricEvaluations()->count())
                        ->badge()
                        ->color('info')
                        ->icon('heroicon-o-clipboard-document-check'),
                    TextEntry::make('reports_summary')
                        ->label('Reportes generados')
                        ->state(fn (Candidate $record) => (string) $record->reportSnapshots()->count())
                        ->badge()
                        ->color('primary')
                        ->icon('heroicon-o-document-chart-bar'),
                    TextEntry::make('created_at')
                        ->label('Registrado el')
                        ->date('d/m/Y'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Candidate::query()->withCount(['psychometricEvaluations', 'reportSnapshots']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Candidato')
                    ->weight('bold')
                    ->icon('heroicon-o-user-circle')
                    ->iconColor(fn (Candidate $record) => $record->statusColor())
                    ->searchable()
                    ->sortable()
                    ->description(fn (Candidate $record) => $record->email),
                Tables\Columns\TextColumn::make('position_applied')
                    ->label('Puesto aplicado')
                    ->state(fn (Candidate $record) => $record->effectivePosition())
                    ->badge()
                    ->placeholder('Sin puesto')
                    ->color(fn (?string $state) => self::puestoColor($state)),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estatus')
                    ->badge()
                    ->icon(fn (?string $state) => self::statusIcon($state))
                    ->formatStateUsing(fn (?string $state) => Candidate::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (?string $state) => Candidate::STATUS_COLORS[$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('psychometric_evaluations_count')
                    ->label('Evaluaciones')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->alignCenter()
                    ->tooltip('Número total de pruebas psicométricas asignadas'),
                Tables\Columns\TextColumn::make('report_snapshots_count')
                    ->label('Reportes')
                    ->icon('heroicon-o-document-chart-bar')
                    ->alignCenter()
                    ->tooltip('Reportes de IA generados para este candidato'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última actividad')
                    ->since()
                    ->tooltip(fn (Candidate $record) => $record->updated_at?->format('d/m/Y H:i'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('position_applied')
                    ->label('Puesto aplicado')
                    ->options(self::puestoOptions()),
            ])
            ->defaultSort('updated_at', 'desc')
            ->striped()
            ->emptyStateHeading('No hay candidatos en este estatus')
            ->emptyStateDescription('Cuando registres o muevas candidatos a este estatus, aparecerán aquí.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('change_status')
                    ->label('Cambiar Estatus')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn () => self::canChangeStatus())
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Nuevo estatus')
                            ->options(Candidate::STATUS_LABELS)
                            ->default(fn (Candidate $record) => $record->status)
                            ->native(false)
                            ->required(),
                    ])
                    ->action(function (Candidate $record, array $data) {
                        $record->changeStatus($data['status']);
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EvaluationsRelationManager::class,
            RelationManagers\ReportSnapshotsRelationManager::class,
            RelationManagers\CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCandidates::route('/'),
            'create' => Pages\CreateCandidate::route('/create'),
            'edit'   => Pages\EditCandidate::route('/{record}/edit'),
            'view'   => Pages\ViewCandidate::route('/{record}'),
        ];
    }
}
