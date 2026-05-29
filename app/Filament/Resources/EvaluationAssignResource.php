<?php

namespace App\Filament\Resources;

use App\Exports\TemplateEvaluatedNetwork;
use App\Filament\Resources\EvaluationAssignResource\Pages;
use App\Filament\Resources\EvaluationAssignResource\RelationManagers;
use App\Models\EvaluationAssign;
use App\Models\EvaluationsTypes;
use App\Models\Sede;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
use function Laravel\Prompts\search;
use Illuminate\Database\Eloquent\Model;


class EvaluationAssignResource extends Resource
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = EvaluationAssign::class;

    protected static ?string $navigationIcon = 'mdi-spider-web';
    protected static ?string $label='Red de Evaluados';
    protected static ?string $navigationGroup = 'Configurar Evaluaciones';
    protected static ?int $navigationSort = 6;
    protected static ?string $pluralLabel='Red de Evaluados';

    public static function canViewAny():bool
    {
        return \auth()->user()->hasAnyRole('Administrador','RH Corp');

    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Asignación de Evaluación')
                    ->schema([
                        Forms\Components\Select::make('campaign_id')
                            ->relationship('campaign', 'name', fn (Builder $query) =>
                                $query->where('status', '!=', 'Concluida'))
                            ->label('Campaña')
                            ->required(),

                        Forms\Components\Hidden::make('evaluation_id')
                            ->default(EvaluationsTypes::where('name', '360')->first()->id),

                        Forms\Components\Select::make('sede')
                            ->options(Sede::pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->label('Sede'),

                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name',
                                function (Builder $query) {
                                    return $query->where('id', '!=', 1)
                                        ->where('status', '=', 1)
                                        ->orderByRaw("CONCAT(name, ' ', first_name, ' ', last_name)");
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn (Model $record) =>
                            trim("{$record->name} {$record->first_name} {$record->last_name}")
                            )
                            ->searchable(['name', 'first_name', 'last_name'])
                            ->preload()
                            ->label('Evaluador')
                            ->required(),


                        // Selector de evaluados temporales
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('temp_user_to_evaluate_id')
                                    ->relationship('userToEvaluate', 'name',
                                        function (Builder $query, Forms\Get $get) {
                                            $query->where('id', '!=', 1);

                                            if ($get('sede')) {
                                                $query->where('sede_id', $get('sede'));
                                            }

                                            return $query->orderByRaw("CONCAT(name, ' ', first_name, ' ', last_name)");
                                        }
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (Model $record) =>
                                    trim("{$record->name} {$record->first_name} {$record->last_name}")
                                    )
                                    ->searchable(['name', 'first_name', 'last_name'])
                                    ->label('Seleccionar evaluado')
                                    ->preload(),
                                Forms\Components\Select::make('type')
                                    ->label('Tipo de evaluado')
                                    ->required()
                                    ->options([
                                        'A' => 'Autoevaluación',
                                        'J' => 'Jefe Inmediato',
                                        'S' => 'Subordinado',
                                        'P'=> 'Par',
                                        'C'=> 'Cliente',
                                    ]),

                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('addEvaluado')
                                        ->label('Agregar evaluado')
                                        ->icon('heroicon-o-plus')
                                        ->color('primary')
                                        ->action(function (Forms\Get $get, Forms\Set $set, array $data) {
                                            $evaluadoId = $get('temp_user_to_evaluate_id');
                                            $campaignId = $get('campaign_id');
                                            $userId = $get('user_id');


                                            if (!$evaluadoId || !$campaignId || !$userId) {
                                                return;
                                            }

                                            // Verificar si ya existe este evaluado en la lista
                                            $selectedEvaluados = $get('selected_evaluados') ?? [];

                                            // Verificar si ya está asignado en la base de datos
                                            $exists = EvaluationAssign::where('campaign_id', $campaignId)
                                                ->where('user_id', $userId)
                                                ->where('user_to_evaluate_id', $evaluadoId)
                                                ->exists();

                                            if ($exists) {
                                                Notification::make()
                                                    ->title('Este evaluador ya está asignado para evaluar a este usuario')
                                                    ->danger()
                                                    ->send();
                                                return;
                                            }

                                            // Si no está en la lista, agregarlo
                                            if (!in_array($evaluadoId, array_column($selectedEvaluados, 'id'))) {
                                                $evaluado = User::find($evaluadoId);
                                                $position_id = $evaluado->position_id ?? 0;

                                                $selectedEvaluados[] = [
                                                    'id' => $evaluadoId,
                                                    'name' => $evaluado->name . ' ' . $evaluado->first_name . ' ' . $evaluado->last_name,
                                                    'position_id' => $position_id,
                                                    'type' => $get('type'),
                                                ];

                                                $set('selected_evaluados', $selectedEvaluados);
                                                $set('temp_user_to_evaluate_id', null);
                                            }
                                        }),
                                ])->alignLeft()
                            ]),

                        // Lista de evaluados seleccionados
                        Forms\Components\Hidden::make('selected_evaluados')
                            ->default([])
                        ->dehydrated(true),
                        Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Placeholder::make('evaluados_lista')
                                ->content(function (Forms\Get $get): HtmlString {
                                    $selectedEvaluados = $get('selected_evaluados') ?? [];

                                    if (empty($selectedEvaluados)) {
                                        return new HtmlString('No hay evaluados seleccionados');
                                    }

                                    $html = '<div class="space-y-2">';
                                    foreach ($selectedEvaluados as $index => $evaluado) {
                                        $tipoLabel = match ($evaluado['type']) {
                                            'A' => 'Autoevaluación',
                                            'J' => 'Jefe Inmediato',
                                            'S' => 'Subordinado',
                                            'P' => 'Par',
                                            'C' => 'Cliente',
                                            default => '?',
                                        };

                                        $html .= '
                                            <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg dark:bg-gray-900">
                                                <span><strong>' . $tipoLabel . ':</strong> ' . $evaluado['name'] . '</span>
                                                <button
                                                    type="button"
                                                    data-action="removeEvaluado"
                                                    data-index="' . $index . '"
                                                    class="text-danger-600 hover:text-danger-500"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>';
                                    }
                                    $html .= '</div>';

                                    return new HtmlString($html);
                                })
                                ->helperText('Se pueden agregar hasta 6 evaluados')

                        ])


                    ])
                    ->columns(2)
            ])
            ->statePath('data')
            ->extraAttributes([
                'x-on:click' => '
                if (event.target.matches("[data-action=removeEvaluado]") || event.target.closest("[data-action=removeEvaluado]")) {
                    const index = event.target.closest("[data-action=removeEvaluado]").dataset.index;
                    const selectedEvaluados = $wire.get("data.selected_evaluados");
                    selectedEvaluados.splice(index, 1);
                    $wire.set("data.selected_evaluados", selectedEvaluados);
                }
            ',
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->hidden()
                    ->searchable(),
                Tables\Columns\TextColumn::make('evaluation.name')
                    ->label('Evaluación')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('campaign.name')
                    ->label('Campaña')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Evaluador')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo de Evaluado')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'A' => 'Autoevaluación',
                        'J' => 'Jefe Inmediato',
                        'S' => 'Subordinado',
                        'P' => 'Par',
                        'C' => 'Cliente',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('userToEvaluate.name')
                    ->label('Evaluado')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('userToEvaluate.position.name')
                    ->label('Puesto Evaluado')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campaign_id')
                    ->label('Campaña')
                    ->relationship('campaign', 'name')

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListEvaluationAssigns::route('/'),
            'create' => Pages\CreateEvaluationAssign::route('/create'),
            'edit' => Pages\EditEvaluationAssign::route('/{record}/edit'),
        ];
    }
    public static function downloadTemplate()
    {
        // Obtener los usuarios e indicadores basados en la consulta del supervisor

        $users = User::where('status', true)
            ->whereNotNull('department_id')
            ->whereNotNull('position_id')
            ->whereNotNull('sede_id')
            ->where('id','!=',1)
            ->get();

        // Generar y descargar la plantilla
        return Excel::download(new TemplateEvaluatedNetwork($users), 'Red_de_Evaluados.xlsx');
    }
    public function mount(){
        //Mandar un erro 403 si no tiene el rol de RH Corp y Administrador
        if (!\auth()->user()?->hasAnyRole('RH Corp','Administrador','Super Administrador')) {
            abort(403, 'No tienes permiso para acceder a este recurso.');
        }
    }


}
