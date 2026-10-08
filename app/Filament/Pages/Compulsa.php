<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\CalculationStatus;
use App\Enums\PeriodType;
use App\Enums\ReconciliationStatus;
use App\Exports\Compulsa\CompulsaFullExport;
use App\Exports\Compulsa\CompulsaSummaryExport;
use App\Jobs\Compulsa\ProcessCompulsaFilesJob;
use App\Models\Compulsa\Calculation;
use App\Models\Compulsa\CalculationRecord;
use App\Models\Compulsa\GlobalSetting;
use App\Models\Compulsa\RiskPremium;
use App\Models\RazonSocial;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class Compulsa extends Page implements HasTable
{
    // HasActions, HasForms + sus traits ya vienen de Filament\Pages\BasePage.
    // Solo agregamos HasTable/InteractsWithTable que sí es específico de esta página.
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'Compulsa';
    protected static ?string $navigationGroup = 'Nómina';
    protected static ?string $title = 'Compulsa IMSS';
    protected static ?string $slug = 'compulsa';
    protected static string $view = 'filament.pages.compulsa';

    public ?int $activeCalculationId = null;
    public string $tempUploadDir = '';

    public static function canView(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['Administrador', 'RH Corp']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canView();
    }

    public function mount(): void
    {
        abort_unless(static::canView(), 403);

        $this->tempUploadDir = 'compulsa/temp/' . Str::random(24);
    }

    public function getHeading(): string|Htmlable
    {
        return 'Compulsa IMSS';
    }

    public function getSubheading(): ?string
    {
        return 'Confronta obrero-patronal: SUA + Nómina interna vs. EMA/EBA del IMSS.';
    }

    /**
     * Cálculo activo actualmente cargado en la página (accesible como $this->activeCalculation).
     */
    public function getActiveCalculationProperty(): ?Calculation
    {
        if ($this->activeCalculationId === null) {
            return null;
        }

        return Calculation::query()
            ->with('razonSocial')
            ->find($this->activeCalculationId);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->newCompulsaAction(),
            $this->historyAction(),
        ];
    }

    // ---------------------------------------------------------------------
    // Action: Nueva compulsa
    // ---------------------------------------------------------------------

    public function newCompulsaAction(): Action
    {
        return Action::make('newCompulsa')
            ->label('Nueva compulsa')
            ->icon('heroicon-o-plus-circle')
            ->color('primary')
            ->modalHeading('Nueva compulsa')
            ->modalDescription('Selecciona la razón social, el período y sube los archivos correspondientes.')
            ->modalWidth('4xl')
            ->modalSubmitActionLabel('Procesar')
            ->form(fn(): array => $this->newCompulsaFormSchema())
            ->action(fn(array $data) => $this->createCompulsa($data));
    }

    protected function newCompulsaFormSchema(): array
    {
        return [
            Section::make('Razón Social y período')
                ->columns(2)
                ->schema([
                    Select::make('razon_social_id')
                        ->label('Razón Social')
                        ->options(fn() => RazonSocial::query()
                            ->where('status', 1)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?int $state, Set $set, Get $get): void {
                            if ($state === null) {
                                $set('registro_patronal', null);
                                $set('prima_riesgo', null);
                                return;
                            }

                            $razonSocial = RazonSocial::query()->find($state);
                            if ($razonSocial) {
                                $set('registro_patronal', $razonSocial->registro_patronal);
                                $ejercicio = (int) ($get('ejercicio') ?? now()->year);
                                $premium = $razonSocial->riskPremiumFor($ejercicio);
                                if ($premium) {
                                    $set('prima_riesgo', round((float) $premium->prima_riesgo * 100, 5));
                                }
                            }
                        }),

                    TextInput::make('registro_patronal')
                        ->label('Registro patronal')
                        ->maxLength(50)
                        ->required(),

                    Select::make('tipo_periodo')
                        ->label('Tipo de período')
                        ->options(PeriodType::class)
                        ->default(PeriodType::Mensual->value)
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn(Set $set) => $set('mes_bimestre', null)),

                    Select::make('ejercicio')
                        ->label('Ejercicio')
                        ->options(fn() => collect(range(now()->year, now()->year - 4))
                            ->mapWithKeys(fn(int $y) => [$y => (string) $y])
                            ->all())
                        ->default(now()->year)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (?int $state, Get $get, Set $set): void {
                            $razonSocialId = $get('razon_social_id');
                            if (!$razonSocialId || !$state) {
                                return;
                            }
                            $razonSocial = RazonSocial::query()->find($razonSocialId);
                            if (!$razonSocial) {
                                return;
                            }
                            $premium = $razonSocial->riskPremiumFor((int) $state);
                            if ($premium) {
                                $set('prima_riesgo', round((float) $premium->prima_riesgo * 100, 5));
                            }
                        })
                        ->rule(fn() => function (string $attribute, mixed $value, Closure $fail): void {
                            if (!$value) {
                                return;
                            }
                            $globals = GlobalSetting::forYear((int) $value);
                            if (!$globals) {
                                $fail(sprintf(
                                    'No hay configuración anual para %s. Cárgala en "Config. Cuotas IMSS" antes de continuar.',
                                    $value,
                                ));
                                return;
                            }
                            if ((float) $globals->valor_uma <= 0) {
                                $fail(sprintf('La UMA no está configurada para el ejercicio %s.', $value));
                                return;
                            }
                            if ((float) $globals->salario_minimo <= 0) {
                                $fail(sprintf('El salario mínimo no está configurado para el ejercicio %s.', $value));
                            }
                        }),

                    Select::make('mes_bimestre')
                        ->label(fn(Get $get) => $this->periodFieldLabel($get('tipo_periodo')))
                        ->options(fn(Get $get) => $this->periodOptions($get('tipo_periodo')))
                        ->required()
                        ->rule(fn(Get $get) => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                            $razonSocialId = $get('razon_social_id');
                            $tipo = $get('tipo_periodo');
                            $ejercicio = $get('ejercicio');
                            if (!$razonSocialId || !$tipo || !$ejercicio || !$value) {
                                return;
                            }

                            $exists = Calculation::query()
                                ->where('razon_social_id', (int) $razonSocialId)
                                ->where('tipo_periodo', $tipo)
                                ->where('ejercicio', (int) $ejercicio)
                                ->where('mes_bimestre', (int) $value)
                                ->exists();

                            if ($exists) {
                                $fail('Ya existe una compulsa para esa razón social, ejercicio y período. Elimínala desde el Historial o elige otro período.');
                            }
                        }),

                    TextInput::make('prima_riesgo')
                        ->label('Prima de riesgo (%)')
                        ->helperText('Porcentaje anual asignado por el IMSS (ej. 1.11178 para 1.11178 %).')
                        ->numeric()
                        ->step(0.00001)
                        ->minValue(0)
                        ->maxValue(100)
                        ->required(),
                ]),

            Section::make('Archivos')
                ->description(fn(Get $get) => $get('tipo_periodo') === PeriodType::Bimestral->value
                    ? 'Sube EBA (IMSS), SUA y Nómina interna.'
                    : 'Sube EMA (IMSS), SUA y Nómina interna.')
                ->columns(3)
                ->schema([
                    FileUpload::make('archivo_imss')
                        ->label(fn(Get $get) => $get('tipo_periodo') === PeriodType::Bimestral->value
                            ? 'Archivo IMSS (EBA)'
                            : 'Archivo IMSS (EMA)')
                        ->disk('local')
                        ->directory(fn() => $this->tempUploadDir)
                        ->preserveFilenames()
                        ->acceptedFileTypes($this->excelMimeTypes())
                        ->required(),

                    FileUpload::make('archivo_sua')
                        ->label('Archivo SUA')
                        ->disk('local')
                        ->directory(fn() => $this->tempUploadDir)
                        ->preserveFilenames()
                        ->acceptedFileTypes($this->excelMimeTypes())
                        ->required(),

                    FileUpload::make('archivo_nomina')
                        ->label('Nómina interna')
                        ->disk('local')
                        ->directory(fn() => $this->tempUploadDir)
                        ->preserveFilenames()
                        ->acceptedFileTypes($this->excelMimeTypes())
                        ->required(),
                ]),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function excelMimeTypes(): array
    {
        return [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function periodOptions(?string $tipo): array
    {
        if ($tipo === PeriodType::Bimestral->value) {
            return [
                1 => 'Enero-Febrero',
                2 => 'Marzo-Abril',
                3 => 'Mayo-Junio',
                4 => 'Julio-Agosto',
                5 => 'Septiembre-Octubre',
                6 => 'Noviembre-Diciembre',
            ];
        }

        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }

    protected function periodFieldLabel(?string $tipo): string
    {
        return $tipo === PeriodType::Bimestral->value ? 'Bimestre' : 'Mes';
    }

    // ---------------------------------------------------------------------
    // Creación de la Compulsa
    // ---------------------------------------------------------------------

    public function createCompulsa(array $data): void
    {
        try {
            /** @var Calculation $calculation */
            $calculation = DB::transaction(function () use ($data): Calculation {
                $razonSocial = RazonSocial::findOrFail((int) $data['razon_social_id']);
                $registroPatronal = (string) ($data['registro_patronal'] ?? '');

                if ($registroPatronal !== '' && $razonSocial->registro_patronal !== $registroPatronal) {
                    $razonSocial->update(['registro_patronal' => $registroPatronal]);
                }

                $ejercicio = (int) $data['ejercicio'];
                $prima = round(((float) $data['prima_riesgo']) / 100, 7);

                RiskPremium::updateOrCreate(
                    ['razon_social_id' => $razonSocial->id, 'ejercicio' => $ejercicio],
                    ['prima_riesgo' => $prima],
                );

                $globals = GlobalSetting::forYear($ejercicio);

                return Calculation::create([
                    'razon_social_id' => $razonSocial->id,
                    'user_id' => auth()->id(),
                    'tipo_periodo' => $data['tipo_periodo'],
                    'mes_bimestre' => (int) $data['mes_bimestre'],
                    'ejercicio' => $ejercicio,
                    'estado' => CalculationStatus::Draft->value,
                    'prima_riesgo_aplicada' => $prima,
                    'valor_uma_aplicado' => $globals?->valor_uma,
                    'salario_minimo_aplicado' => $globals?->salario_minimo,
                    'tope_uma_veces_aplicado' => $globals?->tope_uma_veces,
                    'meta' => [
                        'valor_umi_aplicado' => $globals?->valor_umi !== null ? (float) $globals->valor_umi : null,
                        'ema_rates' => $globals?->emaRates() ?? [],
                        'eba_rates' => $globals?->ebaRates() ?? [],
                        'tabla_cesantia_patronal' => $globals?->tabla_cesantia_patronal ?? [],
                        'exencion_cuota_obrera_sm' => $globals?->exencion_cuota_obrera_sm ?? true,
                    ],
                ]);

            });

            // Mover los archivos del temp a compulsa/{id}/ y actualizar rutas
            $paths = $this->moveUploadedFiles($calculation->id, $data);
            $calculation->update($paths);

            // Ingesta síncrona (Fase 4 luego dispara la conciliación)
            ProcessCompulsaFilesJob::dispatchSync($calculation->id);

            $this->activeCalculationId = $calculation->id;

            Notification::make()
                ->title('Compulsa creada')
                ->body(sprintf('Archivos procesados para %s.', $calculation->periodLabel()))
                ->success()
                ->send();
        } catch (Throwable $e) {
            Log::error('Compulsa: fallo al crear', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Notification::make()
                ->title('No se pudo crear la compulsa')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * @return array<string, string>
     */
    private function moveUploadedFiles(int $calculationId, array $data): array
    {
        $disk = Storage::disk('local');
        $targetDir = "compulsa/{$calculationId}";
        $disk->makeDirectory($targetDir);

        $mapping = [
            'archivo_imss' => ['column' => 'archivo_imss_path', 'prefix' => 'imss'],
            'archivo_sua' => ['column' => 'archivo_sua_path', 'prefix' => 'sua'],
            'archivo_nomina' => ['column' => 'archivo_nomina_path', 'prefix' => 'nomina'],
        ];

        $paths = [];
        foreach ($mapping as $formKey => $cfg) {
            $tempPath = $data[$formKey] ?? null;
            if (!$tempPath || !$disk->exists($tempPath)) {
                continue;
            }

            $filename = $cfg['prefix'] . '_' . basename($tempPath);
            $destination = $targetDir . '/' . $filename;
            $disk->move($tempPath, $destination);
            $paths[$cfg['column']] = $destination;
        }

        if ($this->tempUploadDir !== '' && $disk->exists($this->tempUploadDir)) {
            $disk->deleteDirectory($this->tempUploadDir);
        }

        return $paths;
    }

    // ---------------------------------------------------------------------
    // Action: Historial (slide-over)
    // ---------------------------------------------------------------------

    public function historyAction(): Action
    {
        return Action::make('history')
            ->label('Historial')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->slideOver()
            ->modalHeading('Historial de compulsas')
            ->modalWidth('xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(fn() => view('filament.pages.compulsa.partials.history', [
                'calculations' => $this->historyCalculations(),
                'activeId' => $this->activeCalculationId,
            ]));
    }

    protected function historyCalculations()
    {
        return Calculation::query()
            ->with('razonSocial')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();
    }

    public function loadCalculation(int $id): void
    {
        $calculation = Calculation::query()->find($id);
        if ($calculation === null) {
            Notification::make()
                ->title('Compulsa no encontrada')
                ->warning()
                ->send();
            return;
        }

        $this->activeCalculationId = $calculation->id;
        $this->unmountAction();

        Notification::make()
            ->title('Compulsa cargada')
            ->body($calculation->periodLabel())
            ->success()
            ->send();
    }

    public function deleteCalculation(int $id): void
    {
        $calculation = Calculation::query()->find($id);
        if ($calculation === null) {
            return;
        }

        $wasActive = $this->activeCalculationId === $calculation->id;

        // Cascada: records por FK + archivos por CalculationObserver::deleting()
        $calculation->delete();

        if ($wasActive) {
            $this->activeCalculationId = null;
        }

        Notification::make()
            ->title('Compulsa eliminada')
            ->success()
            ->send();
    }

    public function clearActive(): void
    {
        $this->activeCalculationId = null;
    }

    /**
     * Activa el filter toggle "supera_tope_uma" desde el banner de la vista.
     */
    public function filterByTopeUma(): void
    {
        $filters = $this->tableFilters ?? [];
        $filters['supera_tope_uma'] = ['isActive' => true];
        $this->tableFilters = $filters;
        $this->resetPage($this->getTablePaginationPageName());
    }

    /**
     * Cantidad de empleados que superan el tope UMA en el cálculo activo.
     * Cero si no hay cálculo cargado.
     */
    public function getTopeUmaCountProperty(): int
    {
        if (!$this->activeCalculationId) {
            return 0;
        }

        return CalculationRecord::query()
            ->where('calculation_id', $this->activeCalculationId)
            ->where('supera_tope_uma', true)
            ->count();
    }

    // ---------------------------------------------------------------------
    // Tabla de resultados (Fase 5)
    // ---------------------------------------------------------------------

    public function table(Table $table): Table
    {
        return $table
            ->query(fn(): Builder => CalculationRecord::query()
                ->where('calculation_id', $this->activeCalculationId ?: 0))
            ->columns([
                TextColumn::make('nss')
                    ->label('NSS')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->size('sm'),
                TextColumn::make('nombre_completo')
                    ->label('Empleado')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(45),
                TextColumn::make('dias_imss')
                    ->label('Días IMSS')
                    ->alignCenter()
                    ->placeholder('—'),
                TextColumn::make('dias_nomina')
                    ->label('Días Nóm.')
                    ->alignCenter()
                    ->placeholder('—')
                    ->color(fn(CalculationRecord $r): ?string => (
                        $r->dias_imss !== null
                        && $r->dias_nomina !== null
                        && $r->dias_imss !== $r->dias_nomina
                    ) ? 'warning' : null)
                    ->tooltip(fn(CalculationRecord $r): ?string => (
                        $r->dias_imss !== null
                        && $r->dias_nomina !== null
                        && $r->dias_imss !== $r->dias_nomina
                    ) ? 'Posible incapacidad, falta o alta no capturada en SUA' : null),
                TextColumn::make('dias_sua')
                    ->label('Días SUA')
                    ->alignCenter()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sdi_imss')
                    ->label('SDI IMSS')
                    ->alignRight()
                    ->placeholder('—')
                    ->formatStateUsing(fn($state) => $state !== null ? '$ ' . number_format((float) $state, 4) : '—'),
                TextColumn::make('sdi_nomina')
                    ->label('SDI Nóm.')
                    ->alignRight()
                    ->placeholder('—')
                    ->formatStateUsing(fn($state) => $state !== null ? '$ ' . number_format((float) $state, 4) : '—')
                    ->color(fn(CalculationRecord $r): ?string => (
                        $r->sdi_imss !== null
                        && $r->sdi_nomina !== null
                        && abs((float) $r->sdi_imss - (float) $r->sdi_nomina) > 0.01
                    ) ? 'warning' : null)
                    ->tooltip(fn(CalculationRecord $r): ?string => (
                        $r->sdi_imss !== null
                        && $r->sdi_nomina !== null
                        && abs((float) $r->sdi_imss - (float) $r->sdi_nomina) > 0.01
                    ) ? 'El SDI declarado difiere del cobrado por IMSS — revisar aumentos, prima, o topes' : null),
                TextColumn::make('sdi_sua')
                    ->label('SDI SUA')
                    ->alignRight()
                    ->placeholder('—')
                    ->formatStateUsing(fn($state) => $state !== null ? '$ ' . number_format((float) $state, 4) : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cuota_imss')
                    ->label('Cuota IMSS')
                    ->alignRight()
                    ->money('MXN'),
                TextColumn::make('cuota_patron')
                    ->label('Cuota Patrón')
                    ->alignRight()
                    ->money('MXN')
                    ->weight('semibold'),
                TextColumn::make('diferencia_total')
                    ->label('Diferencia')
                    ->alignRight()
                    ->money('MXN')
                    ->weight('semibold')
                    ->color(fn(CalculationRecord $r): string => match (true) {
                        abs((float) $r->diferencia_total) < 0.01 => 'success',
                        (float) $r->diferencia_total > 0 => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('estatus_conciliacion')
                    ->label('Estatus')
                    ->badge()
                    ->tooltip(fn(CalculationRecord $r): ?string => $r->observaciones),
            ])
            ->headerActions([
                TableAction::make('exportSummary')
                    ->label('Exportar resumen')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('gray')
                    ->action(function () {
                        $calc = $this->getActiveCalculationProperty();
                        if ($calc === null) {
                            return null;
                        }
                        return Excel::download(
                            new CompulsaSummaryExport($this->getFilteredTableQuery(), $calc),
                            sprintf('compulsa-resumen-%d-%s.xlsx', $calc->id, now()->format('Ymd-His')),
                        );
                    }),
                TableAction::make('exportFull')
                    ->label('Exportar completo')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('primary')
                    ->action(function () {
                        $calc = $this->getActiveCalculationProperty();
                        if ($calc === null) {
                            return null;
                        }
                        return Excel::download(
                            new CompulsaFullExport($this->getFilteredTableQuery(), $calc),
                            sprintf('compulsa-completo-%d-%s.xlsx', $calc->id, now()->format('Ymd-His')),
                        );
                    }),
            ])
            ->filters([
                SelectFilter::make('estatus_conciliacion')
                    ->label('Estatus')
                    ->options(ReconciliationStatus::tableOptions())
                    ->multiple(),
                Filter::make('con_diferencias')
                    ->label('Solo con diferencias')
                    ->toggle()
                    ->query(fn(Builder $q): Builder => $q->where('estatus_conciliacion', '!=', ReconciliationStatus::Ok->value)),
                Filter::make('supera_tope_uma')
                    ->label('Supera tope UMA')
                    ->toggle()
                    ->query(fn(Builder $q): Builder => $q->where('supera_tope_uma', true)),
            ])
            ->actions([
                TableAction::make('detail')
                    ->label('Detalle')
                    ->icon('heroicon-o-magnifying-glass-plus')
                    ->color('gray')
                    ->slideOver()
                    ->modalWidth('4xl')
                    ->modalHeading(fn(CalculationRecord $record): string => 'Detalle · ' . $record->nombre_completo)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn(CalculationRecord $record) => view(
                        'filament.pages.compulsa.partials.record-detail',
                        ['record' => $record],
                    )),
            ])
            ->defaultSort('nombre_completo', 'asc')
            ->paginated([25, 50, 100, 250])
            ->defaultPaginationPageOption(25)
            ->striped()
            ->emptyStateHeading('Sin registros para mostrar')
            ->emptyStateDescription('Cuando se procesa una compulsa, aquí aparece la conciliación por empleado.')
            ->emptyStateIcon('heroicon-o-users');
    }
}
