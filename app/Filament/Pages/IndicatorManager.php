<?php

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Models\Indicator;
use App\Models\IndicatorProgress;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;

class IndicatorManager extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-m-document-check';
    protected static string $view = 'filament.pages.indicator-manager';
    protected static ?string $navigationLabel = 'Registro de Avance';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Tablero de Control';
    protected ?string $heading = 'Tablero de Control';
    protected ?string $subheading = 'Captura de avance de indicadores';
    public $user;
    public $users;
    public $userToEvaluated;
    public $indicatorProgresses;
    public $data=[];
    //public $formIndicador;
    public $campaignId;
    public $show = false;
    public ?array $progresses = [
        'indicator_id' => null,
        'month' => null,
        'progresse_value' => null,
    ];

    public static function canView(): bool
    {
        //Este Panel solo lo debe de ver los Jefes de Área y el Administrador
        //Se debe de agregar la comprobación de que estpo se cumpla para que solo sea visible para los Jefes de Área
        if (\auth()->user()->hasAnyRole('RH Corp','RH','Supervisor','Administrador','Gerente')) {
            return true;
        }else{
            return false;
        }

    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getForms(): array
    {
        return ['formProgresses'];
    }


    public function mount()
    {

       // $this->campaignId = Campaign::whereStatus('Activa')->first()->id??null;
       // if ($this->campaignId){
            $supervisorId = auth()->user()->position_id;

            $this->users = User::where('status', true)
                ->whereNotNull('department_id')
                ->whereNotNull('position_id')
                ->whereNotNull('sede_id')
                ->whereHas('position', function ($query) use ($supervisorId) {
                    $query->where('supervisor_id', $supervisorId);
                })
                ->get();
      //  }else{
        //    $this->users = collect();
       // }


    }
    public function updatedUser($value): void
    {
        // dd($this->user);
        $this->userToEvaluated = User::find($value);
        if ($this->userToEvaluated) {

            $this->show = true;

            $this->getIndicatorProgressesForUser($this->user);

        } else {
            //Añadir error bags para mostrar mensaje de error
            $this->show = false;
        }
    }
    public function getIndicatorProgressesForUser($userId)
    {

        $this->indicatorProgresses = Indicator::where('user_id', $userId) //Falta acotarlo al año
            ->with('progresses')// Cargar progresos relacionados
            ->get(); // Devuelve los indicadores con progresos

        return $this->indicatorProgresses;
    }
    public function addValue()
    {
        $this->dispatch('open-modal', id: 'add-value');
    }
    public function closeModal()
    {
        //Agregar limpiar el modal
        $this->progresses = [
            'indicator_id' => null,
            'month' => null,
            'progresse_value' => null,
        ];
        $this->dispatch('close-modal', id: 'add-value');
    }
    public function formProgresses(Form $form):Form
    {
        $currentMonth = (int) date('n'); // 'n' devuelve el mes sin ceros iniciales (1-12)
        $previousMonth = $currentMonth - 1;

        // Si el mes anterior es 0 (enero), lo ajustamos a diciembre (12)
        if ($previousMonth < 1) {
            $previousMonth = 12;
        }

        // Crear un array con los meses permitidos
        $allowedMonths = [
            $previousMonth => $this->getMonthName($previousMonth),
            $currentMonth => $this->getMonthName($currentMonth),
        ];
        return $form->
        schema([
            Select::make('indicator_id')
                ->label('Indicador')
                ->options(function () {
                    return Indicator::where('user_id', $this->user)->pluck('name', 'id')->toArray();
                })
                ->preload()
                ->searchable()
                ->required()
                ->placeholder('Indicadores')
                ->statePath('progresses.indicator_id'),
            Select::make('month')
                ->label('Mes')
                ->options($allowedMonths)
                ->statePath('progresses.month')
                ->required(),
            TextInput::make('progresse_value')->type('number')->label('Avance')->required()
                ->statePath('progresses.progresse_value'),
        ])->columns(3);
    }
    protected function getMonthName(int $monthNumber): string
    {
        $months = [
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

        return $months[$monthNumber] ?? 'Mes inválido';
    }
    public function save()
    {
        // dd(now()->year);
        try{
            $newProgress = IndicatorProgress::create([
                'indicator_id' => $this->progresses['indicator_id'],
                'month' => $this->progresses['month'],
                'year' => now()->year,
                'progress_value' =>$this->progresses['progresse_value'],
            ]);
        }catch (Halt $e){
            return;
        }
        $monthName = $this->getMonthName($this->progresses['month']);

        $this->getIndicatorProgressesForUser($this->user);

        Notification::make()
            ->success()
            ->title('Haz registrado el avance del colaborador del mes de ' . $monthName)
            ->send();

        $this->closeModal();
    }
    public function evaluateProgressRange($progressValue, $ranges)
    {

        // Evaluar si está en rango excelente
        if ($this->isInRange($progressValue, $ranges->expression_excellent, $ranges->excellent_threshold, $ranges->excellent_maximum_value ?? null)) {
            return 'excellent';
        }

        // Evaluar si está en rango satisfactorio
        if ($this->isInRange($progressValue, $ranges->expression_satisfactory, $ranges->satisfactory_threshold, $ranges->satisfactory_maximum_value ?? null)) {
            return 'satisfactory';
        }

        // Evaluar si está en rango insatisfactorio
        if ($this->isInRange($progressValue, $ranges->expression_unsatisfactory, $ranges->unsatisfactory_threshold, $ranges->unsatisfactory_maximum_value ?? null)) {
            return 'unsatisfactory';
        }

        // Si no está en ningún rango, devolver insatisfactorio por defecto
        return 'unsatisfactory';
    }

    /**
     * Determina si un valor está dentro del rango especificado
     *
     * @param float $value Valor a evaluar
     * @param int $expression Tipo de expresión (1-6)
     * @param float $threshold Valor umbral
     * @param float|null $maxValue Valor máximo (para expresión 'Entre')
     * @return bool
     */
    private function isInRange($value, $expression, $threshold, $maxValue = null)
    {
        switch ($expression) {
            case '1': // Mayor que
                return $value > $threshold;
            case '2': // Menor que
                return $value < $threshold;
            case '3': // Igual a
                return $value == $threshold;
            case '4': // Mayor o igual que
                return $value >= $threshold;
            case '5': // Menor o igual que
                return $value <= $threshold;
            case '6': // Entre
                return $value >= $threshold && $value <= $maxValue;
            default:
                return false;
        }
    }




}
