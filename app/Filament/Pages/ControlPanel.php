<?php

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Indicator;
use App\Models\IndicatorRange;
use App\Models\IndicatorProgress;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Component;


class ControlPanel extends Page implements HasForms
{
    protected static bool $shouldRegisterNavigation = false;

    use InteractsWithForms;
    public ?array $data = [];
    public ?array $progresses = [
        'indicator_id' => null,
        'month' => null,
        'progresse_value' => null,
    ];
    private array $months = [
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationLabel = 'Registro de Indicadores';
    protected static ?string $navigationGroup = 'Tablero de Control';
    protected ?string $heading = 'Tablero de Control';
    protected ?string $subheading = 'Registro de Indicadores';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.control-panel';

    public $user;
    public $users;
    public $indi;
    public $userToEvaluated;
    public $campaignId;
    public $show = false;
    public $indicator;
   // public $month, $avance, $indicador_mes;
    public $indicatorProgresses;

    public static function canView(): bool
    {
        //Este Panel solo lo debe de ver los Jefes de Área y el Administrador
        //Se debe de agregar la comprobación de que estpo se cumpla para que solo sea visible para los Jefes de Área

        return \auth()->user()?->hasAnyRole('RH','Administrador','Super Administrador','RH Corp','Supervisor','Gerente');

    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getForms(): array
    {
        return [
            'formIndicador'
        ];
    }
    public function mount()
    {
        //Mandar un erro 403 si no tiene el rol de RH Corp y Administrador
        if (!\auth()->user()?->hasAnyRole('RH','Administrador','Super Administrador','RH Corp','Supervisor','Gerente')) {
            abort(403, 'No tienes permiso para acceder a este recurso.');
        }

        $this->campaignId = Campaign::whereStatus('Activa')->first()->id ?? null;
        $supervisorId = auth()->user()->position_id;

        $this->users = User::where('status', true)
            ->whereNotNull('department_id')
            ->whereNotNull('position_id')
            ->whereNotNull('sede_id')
            ->whereHas('position', function ($query) use ($supervisorId) {
                $query->where('supervisor_id', $supervisorId);
            })
            ->get();
        $this->indicator= new Indicator();

    }

    public function formIndicador(Form $form):Form
    {
        return $form
            ->model($this->indicator)
            ->schema([
                Repeater::make('indicators')
                    ->label('Indicadores')
                    ->schema([
                        TextInput::make('name')->label('Nombre del Indicador')->required(),
                        Textarea::make('objective_description')->label('Descripción del Indicador')->required(),
                        TextInput::make('evaluation_formula')->label('Fórmula')->required(),
                        Select::make('indicator_type')->label('Tipo de Indicador')->options([
                            '1' => 'Cuantitativo',
                        ])->required(),
                        //TextInput::make('target_value')->type('number')->label('Objetivo')->required(),
                        Select::make('indicator_category_id')->label('Categoría')->options([
                            '1' => 'Estratégico',
                            '2' => 'Operativo',
                            '3' => 'Táctico',
                        ])->required(),
                        Select::make('indicator_unit_id')->label('Unidad de Medida')->options([
                            '1' => 'Porcentaje',
                            '2' => 'Número',
                            '3' => 'Moneda',
                            '4' => 'Otro',
                        ])->required(),
                        Select::make('periodicity')->label('Periodicidad')->required()->options([
                            '1' => 'Mensual',
                            '3' => 'Trimestral',
                            '5' => 'Anual',
                        ]),
                        DatePicker::make('target_period_start')->label('Fecha de Inicio')->required(),
                        DatePicker::make('target_period_end')->label('Fecha de Cumplimiento')->required(),

                        // Relación indicatorRanges (uno a uno)
                        Section::make('Rangos del Indicador')
                            ->schema([
                                Fieldset::make('indicatorRanges')
                                    ->relationship('indicatorRanges')
                                    ->schema([
                                        Select::make('expression_excellent')
                                            ->label('Rango para excelente')
                                            ->options([
                                                '1' => 'Mayor que',
                                                '2' => 'Menor que',
                                                '3' => 'Igual a',
                                                '4' => 'Mayor o igual que',
                                                '5' => 'Menor o igual que',
                                                '6' => 'Entre',
                                            ])
                                            ->reactive()
                                            ->required()
                                            ->columnSpan(fn ($get) => $get('expression_excellent') === '6' ? 4 : 6),
                                        TextInput::make('excellent_threshold')
                                            ->type('number')
                                            ->label(fn ($get) => $get('expression_excellent') === '6' ? 'Valor Menor' : 'Valor')
                                            ->required()
                                            ->columnSpan(fn ($get) => $get('expression_excellent') === '6' ? 4 : 6),
                                        TextInput::make('excellent_maximum_value')
                                            ->type('number')
                                            ->reactive()
                                            ->label('Valor Mayor')
                                            ->visible(fn ($get): bool => $get('expression_excellent') === '6')
                                            ->required(fn ($get): bool => $get('expression_excellent') === '6')
                                            ->columnSpan(4),

                                        Select::make('expression_satisfactory')
                                            ->label('Rango para Satisfactorio')
                                            ->options([
                                                '1' => 'Mayor que',
                                                '2' => 'Menor que',
                                                '3' => 'Igual a',
                                                '4' => 'Mayor o igual que',
                                                '5' => 'Menor o igual que',
                                                '6' => 'Entre',
                                            ])
                                            ->reactive()
                                            ->columnSpan(fn ($get) => $get('expression_satisfactory') === '6' ? 4 : 6)
                                            ->required(),

                                        TextInput::make('satisfactory_threshold')
                                            ->type('number')
                                            ->label('Valor para satisfactorio')
                                            ->label(fn ($get) => $get('expression_satisfactory') === '6' ? 'Valor Menor' : 'Valor')
                                            ->required()
                                            ->columnSpan(fn ($get) => $get('expression_satisfactory') === '6' ? 4 : 6),
                                        TextInput::make('satisfactory_maximum_value')
                                            ->type('number')
                                            ->reactive()
                                            ->label('Valor Mayor')
                                            ->visible(fn ($get): bool => $get('expression_satisfactory') === '6')
                                            ->required(fn ($get): bool => $get('expression_satisfactory') === '6')
                                            ->columnSpan(4),

                                        Select::make('expression_unsatisfactory')
                                            ->label('Rango para Deficiente')
                                            ->options([
                                                '1' => 'Mayor que',
                                                '2' => 'Menor que',
                                                '3' => 'Igual a',
                                                '4' => 'Mayor o igual que',
                                                '5' => 'Menor o igual que',
                                                '6' => 'Entre',
                                            ])
                                            ->reactive()
                                            ->required()
                                        ->columnSpan(fn ($get) => $get('expression_unsatisfactory') === '6' ? 4 : 6),
                                        TextInput::make('unsatisfactory_threshold')
                                            ->type('number')
                                            ->label(fn ($get) => $get('expression_unsatisfactory') === '6' ? 'Valor Menor' : 'Valor')
                                            ->required()
                                            ->columnSpan(fn ($get) => $get('expression_unsatisfactory') === '6' ? 4 : 6),
                                        TextInput::make('unsatisfactory_maximum_value')
                                            ->type('number')
                                            ->reactive()
                                            ->label('Valor Mayor')
                                            ->visible(fn ($get): bool => $get('expression_unsatisfactory') === '6')
                                            ->required(fn ($get): bool => $get('expression_unsatisfactory') === '6')
                                            ->columnSpan(4),
                                    ])->columns(12)

                            ])
                            ->columns(12)
                    ])
                    ->columns(2)
                    ->collapsed()
                    ->itemLabel(fn($state):?String=> ($state['name'])??null)
                    ->deleteAction(
                        fn (Action $action) => $action
                            ->requiresConfirmation()
                            ->action(function (array $arguments, Repeater $component): void {
                                $items = $component->getState();
                                $activeItem  = $items[$arguments['item']];

                                if (isset($activeItem['id'])) {
                                        //Soy la mera riata
                                    $this->deleteIndicator($activeItem['id']);
                                }
                            })
                    )
            ])->statePath('data');
    }


    public function updatedUser($value): void
    {
        // dd($this->user);
        $this->userToEvaluated = User::find($value);
        if ($this->userToEvaluated) {
            $this->show = true;

            // Cargar indicadores con sus rangos
            $this->indi = Indicator::where('user_id', $this->userToEvaluated->id)
                ->with('indicatorRanges')
                ->get();

            // Preparar los datos en el formato que espera Filament
            $formattedIndicators = $this->indi->map(function($indicator) {
                $data = $indicator->toArray();

                // Anidar los rangos dentro de cada indicador
                if ($indicator->indicatorRanges) {
                    $data['indicatorRanges'] = $indicator->indicatorRanges->toArray();
                }

                return $data;
            })->toArray();

            $this->data = [
                'indicators' => $formattedIndicators
            ];

            $this->formIndicador->fill($this->data);
        } else {
            $this->show = false;
        }


    }

    public function saveIndicador()
    {

        // Validar los datos
        $this->formIndicador->validate();

        // Iniciar una transacción para garantizar integridad de datos
        \DB::beginTransaction();

        try {
            // Iterar sobre los indicadores usando la clave correcta 'indicators'
            foreach ($this->data['indicators'] as $indicador) {
                // Determinar si es actualización o creación
                $indicatorData = [
                    'user_id' => $this->userToEvaluated->id,
                    'evaluated_by' => auth()->id(),
                    'name' => $indicador['name'],
                    'objective_description' => $indicador['objective_description'],
                    'evaluation_formula' => $indicador['evaluation_formula'],
                    'indicator_type' => $indicador['indicator_type'],
                    'target_value' => $indicador['indicatorRanges']['expression_excellent']??0,
                    'type_of_target' => $indicador['indicator_category_id'] ?? null,
                    'indicator_unit_id' => $indicador['indicator_unit_id'],
                    'periodicity' => $indicador['periodicity'],
                    'target_period_start' => $indicador['target_period_start'],
                    'target_period_end' => $indicador['target_period_end'],
                ];

                // Verificar si el indicador ya existe (por ID)
                if (isset($indicador['id'])) {
                    // Actualizar indicador existente
                    $indicator = Indicator::find($indicador['id']);
                    $indicator->update($indicatorData);
                } else {
                    // Crear nuevo indicador
                    $indicator = Indicator::create($indicatorData);
                }

                // Procesar los rangos del indicador
                if (isset($indicador['indicatorRanges'])) {
                    $rangeData = [
                        'expression_excellent' => $indicador['indicatorRanges']['expression_excellent'],
                        'excellent_threshold' => $indicador['indicatorRanges']['excellent_threshold'],
                        'expression_satisfactory' => $indicador['indicatorRanges']['expression_satisfactory'],
                        'satisfactory_threshold' => $indicador['indicatorRanges']['satisfactory_threshold'],
                        'expression_unsatisfactory' => $indicador['indicatorRanges']['expression_unsatisfactory'],
                        'unsatisfactory_threshold' => $indicador['indicatorRanges']['unsatisfactory_threshold'],
                    ];

                    // Añadir valores máximos si existen (para el rango "Entre")
                    if (isset($indicador['indicatorRanges']['excellent_maximum_value'])) {
                        $rangeData['excellent_maximum_value'] = $indicador['indicatorRanges']['maximum_value'];
                    }
                    if (isset($indicador['indicatorRanges']['satisfactory_maximum_value'])) {
                        $rangeData['satisfactory_maximum_value'] = $indicador['indicatorRanges']['satisfactory_maximum_value'];

                    }
                    if (isset($indicador['indicatorRanges']['unsatisfactory_maximum_value'])) {
                        $rangeData['unsatisfactory_maximum_value'] = $indicador['indicatorRanges']['unsatisfactory_maximum_value'];
                    }

                    // Actualizar o crear el rango
                    IndicatorRange::updateOrCreate(
                        ['indicator_id' => $indicator->id],
                        $rangeData
                    );
                }
            }

            // Confirmar la transacción
            \DB::commit();

            // Mostrar notificaciones
            $recipient = $this->userToEvaluated;
            Notification::make()
                ->success()
                ->title('Tienes Indicadores registrados')
                ->body('Te han registrado nuevos indicadores. Puedes revisarlos en tu panel')
                ->sendToDatabase($recipient);

            Notification::make()
                ->success()
                ->title('Indicadores guardados correctamente')
                ->send();

        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            \DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Error al guardar los indicadores')
                ->body($e->getMessage())
                ->send();
        }
    }
    public function deleteIndicator($id)
    {
        try {
            // Busca el indicador y elimínalo junto con sus rangos asociados
            $indicator = Indicator::find($id);

            if ($indicator) {
                // Elimina los rangos asociados (aunque el borrado en cascada debería hacerlo)
                IndicatorRange::where('indicator_id', $id)->delete();

                // Elimina el indicador
                $indicator->delete();

                Notification::make()
                    ->success()
                    ->title('Indicador eliminado con éxito')
                    ->send();

                // Recargar datos
                $this->updatedUser($this->userToEvaluated->id);
            } else {
                Notification::make()
                    ->warning()
                    ->title('El indicador ya no existe')
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error al eliminar el indicador')
                ->body($e->getMessage())
                ->send();
        }
    }


}
