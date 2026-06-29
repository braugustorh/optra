<?php

namespace App\Filament\Pages;

use App\Mail\EvaluationAssignedMail;
use App\Models\PsychometricEvaluation;
use App\Models\User;
use App\Models\Candidate;
use App\Models\EvaluationsTypes;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput; // Si necesitas crear el candidato al vuelo
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use Filament\Pages\Page;
use Filament\Forms;
use Illuminate\Support\Facades\DB;
use App\Services\PsychometricScoringService; // <--- IMPORTANTE
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use App\Services\GeneralReportService;

class PsychometricDashboard extends Page implements HasTable
{
    use InteractsWithTable;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.psychometric-dashboard';
    protected static ?string $title = 'Dashboard de Psicometrías';
    protected static ?string $navigationLabel = 'Dashboard Psicometrías';
    protected static ?int $navigationSort = 3;

    // 9=MossWess, 10=Moss, 11=Cleaver, 12=Kostick, 13=Terman-Merril
    // Modelo de Assessment Psicométrico Estratificado (aplica igual a Int/Ext)
    // "op" = opcional → se pre-carga para que RH pueda desmarcar si no aplica
    const PUESTO_EVALUACIONES = [
        'Directivo'      => [13, 11, 12, 10, 9], // Terman + Cleaver + Kostick + Moss + MossWess
        'Mando Medio'    => [13, 11, 12, 10, 9], // Terman + Cleaver + Kostick + Moss + MossWess
        'Supervisor'     => [13, 11],    // Terman + Cleaver + Kostick(op) + Moss(op)
        'Administrativo' => [13, 11],            // Terman + Cleaver
    ];

    /**
     * Matriz de pruebas OBLIGATORIAS por puesto (para validar completitud del reporte).
     * Basada en el Modelo de Assessment Psicométrico Estratificado.
     * Las pruebas marcadas como "opcional" en Supervisión no bloquean la generación del reporte.
     *   9=MossWess, 10=Moss, 11=Cleaver, 12=Kostick, 13=Terman-Merril
     */
    const PUESTO_OBLIGATORIAS = [
        'Directivo'      => [13, 11, 12, 10, 9], // Terman + Cleaver + Kostick + Moss + MossWess
        'Mando Medio'    => [13, 11, 12, 10, 9], // Terman + Cleaver + Kostick + Moss + MossWess
        'Supervisor'     => [13, 11],            // Terman + Cleaver (Kostick/Moss son opcionales)
        'Administrativo' => [13, 11],            // Terman + Cleaver
    ];
    // Propiedades para filtros
    public $statusFilter = '';
    public $typeFilter = '';
    public $evaluableTypeFilter = '';

    //Poner función para que solo sea visible para RH Corp
    public static function canView(): bool
    {
        return \auth()->user()->hasAnyRole(['Administrador','RH Corp','RH']);

    }
    public static function shouldRegisterNavigation(): bool
    {
        // Esto controla la visibilidad en la navegación.
        return static::canView();

    }
    // --- AQUÍ CONSTRUIMOS LA TABLA ---
    public function table(Table $table): Table
    {
        return $table
            ->query(
                PsychometricEvaluation::query()->with(['evaluable', 'evaluationType'])
                    ->latest('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('evaluable.name')
                    ->label('Evaluado')
                    ->searchable()
                    ->sortable()
                    // Agrega el subtítulo (Candidato vs Colaborador)
                    ->description(fn (PsychometricEvaluation $record): string =>
                    $record->evaluable_type === \App\Models\User::class ? 'Colaborador Interno' : 'Candidato Externo'
                    )
                    // Pone un ícono distintivo
                    ->icon(fn (PsychometricEvaluation $record): string =>
                    $record->evaluable_type === \App\Models\User::class ? 'heroicon-m-identification' : 'heroicon-m-user-circle'
                    )
                    // Le da color ÚNICAMENTE al ícono (dejando el texto en color por defecto)
                    ->iconColor(fn (PsychometricEvaluation $record): string =>
                    $record->evaluable_type === \App\Models\User::class ? 'info' : 'success'
                    ),

                Tables\Columns\TextColumn::make('evaluationType.name')
                    ->label('Prueba')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Moss' => 'success',
                        'Cleaver' => 'warning',
                        'Kostick' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'assigned' => 'warning',
                        'started' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('puesto')
                    ->label('Puesto')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Directivo'      => 'danger',
                        'Mando Medio'    => 'warning',
                        'Supervisor'     => 'info',
                        'Administrativo' => 'success',
                        default          => 'gray',
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                // Filtro por Estado
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'assigned' => 'Asignada',
                        'started' => 'Iniciada',
                        'completed' => 'Completada',
                    ]),
                // Filtro por Tipo
                Tables\Filters\SelectFilter::make('evaluations_type_id')
                    ->label('Tipo de Prueba')
                    ->relationship('evaluationType', 'name'),
            ])
            ->actions([
                // ACCIÓN 1: VER RESULTADOS (MODAL)
                /*
                Tables\Actions\ViewAction::make('results')
                    ->label('Resultados')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    // Solo visible si está completada
                    ->visible(fn (PsychometricEvaluation $record) => $record->status === 'completed')
                    ->modalHeading('Resultados de Evaluación')
                    ->modalSubmitAction(false) // Ocultar botón "Guardar"
                    ->modalContent(function (PsychometricEvaluation $record) {
                        // AQUÍ LLAMAMOS A TU CEREBRO
                        $service = new PsychometricScoringService();
                        $results = $service->calculate($record);

                        // Retornamos la vista parcial que creamos en el Paso 1
                        return view('filament.pages.partials.results-modal', [
                            'results' => $results
                        ]);
                    }),
                    */
                // ACCIÓN 2: DESCARGAR PDF (Placeholder por ahora)
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->visible(fn (PsychometricEvaluation $record) => $record->status === 'completed')
                    ->action(fn (PsychometricEvaluation $record) => $this->downloadPdf($record))
                    ->openUrlInNewTab(),
            ]);
    }

    public function getHeaderActions(): array
    {
        return [
            // ---------------------------------------------------------------
            // ACCIÓN: Asignar a Colaboradores (Internos)
            // ---------------------------------------------------------------
            Action::make('assign_internal')
                ->label('Asignar a Colaborador')
                ->icon('heroicon-o-identification')
                ->color('primary')
                ->form([
                    // 1. Selector de Sede (Visible y editable solo para Admin/RH Corp)
                    Forms\Components\Select::make('sede_id')
                        ->label('Sede')
                        ->options(\App\Models\Sede::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn () => auth()->user()->hasAnyRole(['Administrador', 'RH Corp']))
                        ->required(fn () => auth()->user()->hasAnyRole(['Administrador', 'RH Corp'])),

                    // 2. Selector de Colaborador (Dependiente de la sede)
                    Forms\Components\Select::make('user_id')
                        ->label('Colaborador')
                        ->options(function (Forms\Get $get) {
                            if (auth()->user()->hasAnyRole(['Administrador', 'RH Corp'])) {
                                $sedeId = $get('sede_id');
                            } else {
                                $sedeId = auth()->user()->sede_id;
                            }
                            if (! $sedeId) return [];
                            return \App\Models\User::where('sede_id', $sedeId)
                                ->get()
                                ->mapWithKeys(fn ($user) => [
                                    $user->id => trim($user->name . ' ' . $user->first_name . ' ' . $user->last_name)
                                ]);
                        })
                        ->searchable()
                        ->required()
                        ->loadingMessage('Cargando colaboradores...'),


                    // 3. Puesto
                    Forms\Components\Select::make('puesto')
                        ->label('Puesto / Nivel')
                        ->options([
                            'Directivo'      => 'Directivo',
                            'Mando Medio'    => 'Mando Medio (Gerencia / Jefatura)',
                            'Supervisor'     => 'Supervisor',
                            'Administrativo' => 'Administrativo',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            $set('evaluation_type_ids', self::PUESTO_EVALUACIONES[$state] ?? []);
                        })
                        ->helperText('Al seleccionar el puesto se pre-cargan las evaluaciones correspondientes.'),

                    // 4. Selección de Evaluaciones (se pre-llena según puesto)
                    Select::make('evaluation_type_ids')
                        ->label('Batería de Evaluaciones')
                        ->options(EvaluationsTypes::whereIN('id', [9, 10, 11, 12,13])->pluck('name', 'id'))
                        ->multiple()
                        ->preload()
                        ->required()
                        ->helperText('Puedes ajustar manualmente las evaluaciones pre-cargadas.'),
                ])
                ->action(function (array $data) {
                    $user = User::find($data['user_id']);
                    $batchId = (string) Str::uuid();

                    foreach ($data['evaluation_type_ids'] as $typeId) {
                        PsychometricEvaluation::create([
                            'evaluations_type_id' => $typeId,
                            'evaluable_id'        => $user->id,
                            'evaluable_type'      => User::class,
                            'assigned_by'         => auth()->id(),
                            'batch_id'            => $batchId,
                            'puesto'              => $data['puesto'] ?? null,
                            'status'              => 'assigned',
                            'assigned_at'         => now(),
                        ]);
                    }

                    Notification::make()
                        ->title('Evaluaciones Asignadas')
                        ->body("La batería fue asignada correctamente a {$user->name}.")
                        ->success()
                        ->send();

                    Notification::make()
                        ->title('Nueva Evaluación Psicométrica')
                        ->body('Se te ha asignado una prueba. Por favor, revisa tu sección de Psicometría en el menú.')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->success()
                        ->sendToDatabase($user);
                }),

            // ---------------------------------------------------------------
            // ACCIÓN 2: Asignar a Candidatos (Externos)
            // ---------------------------------------------------------------
            Action::make('evaluate_candidate')
                ->label('Asignar a Candidato')
                ->icon('heroicon-o-user-plus')
                ->color('success') // Color diferente para distinguir
                ->form([
                    // Seleccionar al Candidato (Candidate)
                    Select::make('candidate_id')
                        ->label('Candidato')
                        ->options(Candidate::all()->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')->required()->label('Nombre Completo'),
                            TextInput::make('email')->email()->required()->unique('candidates','email')->label('Correo'),
                            TextInput::make('phone')->tel()->label('Teléfono'),
                        ])
                        ->createOptionUsing(function (array $data) {
                            return Candidate::create($data)->id;
                        }),

                    // Puesto
                    Forms\Components\Select::make('puesto')
                        ->label('Puesto / Nivel')
                        ->options([
                            'Directivo'      => 'Directivo',
                            'Mando Medio'    => 'Mando Medio (Gerencia / Jefatura)',
                            'Supervisor'     => 'Supervisor',
                            'Administrativo' => 'Administrativo',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            $set('evaluation_type_ids', self::PUESTO_EVALUACIONES[$state] ?? []);
                        })
                        ->helperText('Al seleccionar el puesto se pre-cargan las evaluaciones correspondientes.'),

                    // Selección Múltiple de Exámenes (se pre-llena según puesto)
                    Select::make('evaluation_type_ids')
                        ->label('Batería de Evaluaciones')
                        ->options(EvaluationsTypes::whereIN('id', [9, 10, 11, 12,13])->pluck('name', 'id'))
                        ->multiple()
                        ->preload()
                        ->required()
                        ->helperText('El candidato recibirá un único enlace para todas estas pruebas.'),

                    Forms\Components\DatePicker::make('expires_at')
                        ->label('Fecha de Vencimiento')
                        ->helperText('Fecha límite para completar todas las evaluaciones')
                        ->required()
                        ->default(now()->addDays(14)),
                ])
                ->action(function (array $data) {
                    $this->createBatchEvaluations(
                        evaluableId: $data['candidate_id'],
                        evaluableType: Candidate::class,
                        evaluationTypeIds: $data['evaluation_type_ids'],
                        puesto: $data['puesto'] ?? null,
                    );
                }),

            // ---------------------------------------------------------------
            // ACCIÓN 3: Generar Reporte General por Batch
            // ---------------------------------------------------------------
            Action::make('generate_report')
                ->label('Generar Reporte General')
                ->icon('heroicon-o-document-chart-bar')
                ->color('warning')
                ->slideOver()
                ->modalHeading('Generar Reporte General')
                ->modalDescription('Selecciona la persona y la batería de evaluaciones para generar el reporte consolidado.')
                ->modalWidth('2xl')
                ->form([
                    // 1. Tipo de persona
                    Forms\Components\Select::make('evaluable_type')
                        ->label('Tipo de persona')
                        ->options([
                            'user'      => '👥 Colaborador Interno',
                            'candidate' => '🎯 Candidato Externo',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set) {
                            $set('evaluable_id', null);
                            $set('batch_id', null);
                        }),

                    // 2. Selector de persona (depende del tipo)
                    Forms\Components\Select::make('evaluable_id')
                        ->label('Persona')
                        ->options(function (Forms\Get $get) {
                            $type = $get('evaluable_type');
                            if (! $type) return [];
                            if ($type === 'user') {
                                return User::orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn ($u) => [
                                        $u->id => trim("{$u->name} {$u->first_name} {$u->last_name}"),
                                    ]);
                            }
                            return Candidate::orderBy('name')->pluck('name', 'id');
                        })
                        ->searchable()
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('batch_id', null))
                        ->visible(fn (Forms\Get $get) => (bool) $get('evaluable_type'))
                        ->placeholder('Buscar persona...'),

                    // 3. Selector de batería (agrupa evaluaciones por batch_id)
                    Forms\Components\Select::make('batch_id')
                        ->label('Batería de evaluaciones')
                        ->hint('Cada batería representa una sesión de asignación')
                        ->options(function (Forms\Get $get) {
                            $type = $get('evaluable_type');
                            $id   = $get('evaluable_id');
                            if (! $type || ! $id) return [];

                            $modelClass = $type === 'user' ? User::class : Candidate::class;

                            return PsychometricEvaluation::where('evaluable_type', $modelClass)
                                ->where('evaluable_id', $id)
                                ->with('evaluationType')
                                ->orderByDesc('assigned_at')
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
                        ->live()

                        ->required()
                        ->visible(fn (Forms\Get $get) => (bool) $get('evaluable_id'))
                        ->placeholder('Seleccionar batería...'),

                    // 4. Panel de vista previa dinámica
                    Forms\Components\Placeholder::make('preview')
                        ->label('')
                        ->content(function (Forms\Get $get): HtmlString {
                            $batchId = $get('batch_id');
                            if (! $batchId) {
                                return new HtmlString('');
                            }
                            $data = $this->loadBatchPreview($batchId);
                            if (empty($data)) {
                                return new HtmlString(
                                    '<div style="text-align:center;padding:16px;color:#9ca3af;font-size:13px;">
                                        No se encontró información para esta batería.
                                    </div>'
                                );
                            }
                            return new HtmlString(
                                view('filament.pages.partials.batch-preview', ['batchData' => $data])->render()
                            );
                        })
                        ->visible(fn (Forms\Get $get) => (bool) $get('batch_id')),
                ])
                ->modalSubmitActionLabel('Generar Reporte')
                ->action(function (array $data) {
                    $batchId = $data['batch_id'] ?? null;
                    if (! $batchId) {
                        Notification::make()->title('Selecciona una batería')->warning()->send();
                        return;
                    }

                    $preview = $this->loadBatchPreview($batchId);

                    if (! ($preview['can_generate'] ?? false)) {
                        $missing = implode(', ', $preview['missing_required'] ?? []);
                        Notification::make()
                            ->title('Evaluaciones incompletas')
                            ->body("Faltan las siguientes pruebas requeridas: {$missing}")
                            ->warning()
                            ->send();
                        return;
                    }

                    // ─── Comunicación entre servicios ───────────────────────
                    // GeneralReportService calcula los resultados psicométricos,
                    // luego llama a DeepSeekService para el análisis IA.
                    $generalService = new GeneralReportService();
                    $deepSeekService = app(\App\Services\DeepSeekService::class);

                    $output = $generalService->generateAiReport($batchId, $deepSeekService);
                    // ────────────────────────────────────────────────────────

                    if (isset($output['error'])) {
                        Notification::make()
                            ->title('Error al generar reporte')
                            ->body($output['error'])
                            ->danger()
                            ->send();
                        return;
                    }

                    // ─── Notificar si la IA falló (pero continuar con los resultados psicométricos) ──
                    if (isset($output['ai_error'])) {
                        $aiErrMsg = $output['ai_error']['message'] ?? 'Error desconocido';
                        $aiErrCode = $output['ai_error']['code'] ?? '';

                        $friendlyMsg = match(true) {
                            str_contains(strtolower($aiErrMsg), 'insufficient balance')
                            => 'La cuenta de DeepSeek no tiene saldo suficiente. Recarga en platform.deepseek.com y vuelve a intentarlo.',
                            str_contains(strtolower($aiErrMsg), 'rate limit')
                            => 'Se alcanzó el límite de solicitudes de DeepSeek. Intenta en unos minutos.',
                            default => "Error de IA [{$aiErrCode}]: {$aiErrMsg}",
                        };

                        Notification::make()
                            ->title('⚠️ Análisis de IA no disponible')
                            ->body($friendlyMsg . "\n\nEl reporte con resultados psicométricos se descargará de todas formas.")
                            ->warning()
                            ->duration(8000)
                            ->send();
                    }

                    // ─── Guardar reporte en Cache (1 hora) y redirigir a previsualización ─────────────────────────
                    $reportKey = (string) Str::uuid();

                    $name   = $output['consolidated']['evaluable']->name ?? 'candidato';
                    $puesto = $output['consolidated']['puesto'] ?? 'general';

                    $analisisIa = $output['ai_report'] ? $output['ai_report'] : null;

                    $reportDataToCache = [
                        'meta' => [
                            'candidato'      => $name,
                            'puesto'         => $puesto,
                            'batch_id'       => $batchId,
                            'tiempo_total'   => $output['consolidated']['total_elapsed_formatted'],
                            'generado_en'    => now()->toDateTimeString(),
                        ],
                        'candidate_data' => [
                            'name'   => $name,
                            'puesto' => $puesto,
                        ],
                        'psychometric_results' => collect($output['consolidated']['tests'])
                            ->map(fn ($t) => [
                                'prueba'     => $t['test_name'],
                                'tiempo'     => $t['elapsed_formatted'],
                                'resultados' => $t['results'],
                            ])->values()->toArray(),
                        'competencias' => $output['competencias'] ?? [],
                        'competencias_ideal' => $output['competencias_ideal'] ?? [],
                        'cleaver_ideal' => $output['cleaver_ideal'] ?? ['D' => 50, 'I' => 50, 'S' => 50, 'C' => 50],
                        'ai_report' => $analisisIa,
                        'ai_error'           => $output['ai_error'] ?? null,
                        'ajuste_global'      => $output['ajuste_global'] ?? 0,
                        'dictamen_calculado' => $output['dictamen_calculado'] ?? 'Pendiente'
                    ];

                    \Illuminate\Support\Facades\Cache::put("psych_report_{$reportKey}", $reportDataToCache, now()->addHours(1));

                    // Redirigir a la nueva ventana de preview
                    return redirect()->route('psychometric.report.preview', $reportKey);
                }),

            Action::make('configuration')
                ->label('Configuración')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->form([
                    Forms\Components\Section::make('Configuración del Sistema')
                        ->schema([
                            Forms\Components\Toggle::make('auto_reminders')
                                ->label('Recordatorios Automáticos')
                                ->helperText('Enviar recordatorios automáticos antes del vencimiento'),

                            Forms\Components\TextInput::make('reminder_days')
                                ->label('Días antes del vencimiento para recordatorio')
                                ->numeric()
                                ->default(3)
                                ->minValue(1)
                                ->maxValue(30),

                            Forms\Components\Select::make('default_evaluation_duration')
                                ->label('Duración por defecto de evaluaciones')
                                ->options([
                                    7 => '7 días',
                                    14 => '14 días',
                                    21 => '21 días',
                                    30 => '30 días',
                                ])
                                ->default(14),

                            Forms\Components\Toggle::make('allow_candidate_self_register')
                                ->label('Permitir auto-registro de candidatos')
                                ->helperText('Los candidatos pueden registrarse usando un enlace público'),

                            Forms\Components\Textarea::make('default_instructions')
                                ->label('Instrucciones por defecto')
                                ->rows(3)
                                ->placeholder('Instrucciones que aparecerán por defecto en las evaluaciones...'),
                        ]),

                    Forms\Components\Section::make('Notificaciones')
                        ->schema([
                            Forms\Components\Toggle::make('email_notifications')
                                ->label('Notificaciones por Email')
                                ->default(true),

                            Forms\Components\Toggle::make('system_notifications')
                                ->label('Notificaciones del Sistema')
                                ->default(true),

                            Forms\Components\Select::make('notification_recipients')
                                ->label('Destinatarios de notificaciones')
                                ->multiple()
                                ->options(User::whereHas('roles', function($query) {
                                    $query->where('name', 'RH Corp'); // Si usas Spatie Permission
                                })->pluck('name', 'id')->toArray()),
                        ]),
                ])
                ->action(function (array $data) {
                    // Aquí guardarías la configuración en settings o config
                    Notification::make()
                        ->title('Configuración guardada correctamente')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getStats(): array
    {
        $totalUsers = User::count();
        $totalCandidates = Candidate::count();

        return [
            [
                'label' => 'Evaluaciones Activas',
                'value' => PsychometricEvaluation::whereIn('status', ['assigned', 'started', 'in_progress'])->count(),
                'description' => "{$totalUsers} Colaboradores | {$totalCandidates} Candidatos",
                'color' => 'primary',
            ],
            [
                'label' => 'Completadas Hoy',
                'value' => PsychometricEvaluation::where('status', 'completed')
                    ->whereDate('completed_at', today())->count(),
                'color' => 'success',
            ],
            [
                'label' => 'Pendientes',
                'value' => PsychometricEvaluation::where('status', 'assigned')->count(),
                'color' => 'warning',
            ],
            [
                'label' => 'Total Personas',
                'value' => $totalUsers + $totalCandidates,
                'color' => 'info',
            ],
        ];
    }

    public function getEvaluationTypes(): array
    {
        return EvaluationsTypes::withCount(['psychometricEvaluations' => function($query) {
            $query->whereIn('status', ['assigned', 'started', 'in_progress']);
        }])->whereIn('id',[9,10,11,12])
            ->get()->map(function($type) {
                return [
                    'name' => $type->name,
                    'count' => $type->psychometric_evaluations_count,
                ];
            })->toArray();
    }

    public function getFilteredEvaluations()
    {
        $query = PsychometricEvaluation::with(['evaluable', 'evaluationType'])
            ->whereIn('status', ['assigned', 'started', 'in_progress', 'completed'])
            ->latest();

        // Aplicar filtros
        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->typeFilter) {
            $query->where('evaluations_type_id', $this->typeFilter);
        }

        if ($this->evaluableTypeFilter) {

            $query->where('evaluable_type','like','%'.$this->evaluableTypeFilter.'%');
        }

        return $query->limit(15)->get();
    }

    public function applyStatusFilter($status)
    {
        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
    }

    public function applyTypeFilter($type)
    {
        $this->evaluableTypeFilter = $this->evaluableTypeFilter === $type ? '' : $type;
    }

    public function clearFilters()
    {
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->evaluableTypeFilter = '';
    }
    public function mount(){
        //Mandar un erro 403 si no tiene el rol de RH Corp y Administrador
        if (!\auth()->user()?->hasAnyRole('RH Corp','Administrador','Super Administrador')) {
            abort(403, 'No tienes permiso para acceder a este recurso.');
        }
    }

    // -----------------------------------------------------------------------
    // HELPER: Carga la vista previa de un batch para el modal de reporte
    // -----------------------------------------------------------------------
    protected function loadBatchPreview(string $batchId): array
    {
        $evaluations = PsychometricEvaluation::where('batch_id', $batchId)
            ->with(['evaluable', 'evaluationType'])
            ->get();

        if ($evaluations->isEmpty()) {
            return [];
        }

        $first           = $evaluations->first();
        $puesto          = $first->puesto;
        $requiredTypeIds = self::PUESTO_OBLIGATORIAS[$puesto] ?? [];

        $evalData = $evaluations->map(function ($eval) use ($requiredTypeIds) {
            return [
                'test_name'       => $eval->evaluationType->name ?? 'Desconocido',
                'type_id'         => $eval->evaluations_type_id,
                'status'          => $eval->status,
                'elapsed_seconds' => max(0, (int) ($eval->elapsed_seconds ?? 0)),
                'completed_at'    => $eval->completed_at,
                'is_required'     => in_array($eval->evaluations_type_id, $requiredTypeIds),
            ];
        })->sortBy('test_name')->values()->toArray();

        $completedRequiredIds = $evaluations
            ->where('status', 'completed')
            ->whereIn('evaluations_type_id', $requiredTypeIds)
            ->pluck('evaluations_type_id')
            ->toArray();

        $missingIds   = array_diff($requiredTypeIds, $completedRequiredIds);
        $missingNames = EvaluationsTypes::whereIn('id', $missingIds)->pluck('name')->toArray();

        return [
            'evaluable_name'   => $first->evaluable->name ?? 'Desconocido',
            'puesto'           => $puesto ?? 'Sin puesto',
            'batch_id'         => $batchId,
            'assigned_at'      => $first->assigned_at,
            'total_elapsed'    => (int) $evaluations->sum('elapsed_seconds'),
            'evaluations'      => $evalData,
            'can_generate'     => empty($missingIds),
            'missing_required' => $missingNames,
        ];
    }

    // -----------------------------------------------------------------------
    // FUNCIÓN AUXILIAR (Para no repetir código)
    // -----------------------------------------------------------------------
    // Esta función encapsula la lógica "difícil" para que tus acciones queden limpias
    protected function createBatchEvaluations($evaluableId, $evaluableType, $evaluationTypeIds, $puesto = null)
    {
        $batchId = Str::uuid();
        $accessToken = Str::random(40);

        DB::transaction(function () use ($evaluableId, $evaluableType, $evaluationTypeIds, $batchId, $accessToken, $puesto) {
            foreach ($evaluationTypeIds as $typeId) {
                PsychometricEvaluation::create([
                    'evaluations_type_id' => $typeId,
                    'evaluable_id'        => $evaluableId,
                    'evaluable_type'      => $evaluableType,
                    'assigned_by'         => auth()->id(),
                    'batch_id'            => $batchId,
                    'access_token'        => $accessToken,
                    'puesto'              => $puesto,
                    'status'              => 'assigned',
                    'assigned_at'         => now(),
                ]);
            }
        });
        // 2. Recuperar al usuario/candidato para obtener su email y nombre
        $recipient = $evaluableType::find($evaluableId);

        // 3. ENVÍO DEL CORREO AL USUARIO
        if ($recipient && $recipient->email) {
            \Mail::to($recipient->email)->send(new EvaluationAssignedMail($recipient, $accessToken));

            $msg = 'Evaluaciones asignadas y correo enviado exitosamente.';
        } else {
            $msg = 'Evaluaciones creadas, pero no se pudo enviar el correo (sin email).';
        }
        Notification::make()
            ->title('Batería asignada correctamente')
            ->body( $msg) //
            ->success()
            ->send();

        // Aquí podrías llamar a tu Job de envío de correos
        // SendEvaluationLinkJob::dispatch($evaluableId, $evaluableType, $accessToken);
    }
    public function downloadPdf(PsychometricEvaluation $record)
    {
        // 1. Calculamos los resultados con tu cerebro psicométrico
        $service = new PsychometricScoringService();
        $results = $service->calculate($record);
        // dd($results);
        // 2. Preparamos los datos para la vista del PDF
        $candidateName = $record->evaluable->name ?? 'Candidato';
        $testName = $results['test_name'] ?? 'Evaluacion';
        $date = \Carbon\Carbon::parse($record->updated_at)->locale('es')->isoFormat('D [de] MMMM, YYYY');

        // 3. Renderizamos el HTML (Crearemos esta vista en el Paso 2)
        $html = view('pdf.psychometric_report', [
            'record' => $record,
            'results' => $results,
            'candidateName' => $candidateName,
            'date' => $date,
        ])->render();

        // 4. Forzar codificación UTF-8
        $html = mb_convert_encoding($html, 'UTF-8', 'UTF-8');

        // 5. Payload para PDFShift
        $payload = [
            'source'    => $html,
            'landscape' => false,
            'use_print' => true, // <-- Recomendado en true para que Tailwind aplique estilos de impresión
            'margin'    => [
                'top'    => '20px',
                'bottom' => '20px',
                'left'   => '15px',
                'right'  => '15px',
            ],
        ];

        // 6. Llamada a la API
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-Key'    => config('services.pdfshift.api_key'),
        ])
            ->withBody(json_encode($payload, JSON_UNESCAPED_UNICODE), 'application/json')
            ->post('https://api.pdfshift.io/v3/convert/pdf');

        // 7. Retorno / Descarga
        if ($response->successful()) {
            $pdfContent = $response->body();

            // Nombre del archivo dinámico
            $fileName = 'Reporte_' . Str::slug($testName) . '_' . Str::slug($candidateName) . '.pdf';

            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, $fileName);

        } else {
            Notification::make()
                ->title('Error al generar PDF')
                ->body('No se pudo generar el PDF con PDFShift.')
                ->danger()
                ->send();
        }
    }

}
