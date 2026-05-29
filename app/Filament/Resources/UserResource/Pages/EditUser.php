<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Helpers\VisorRoleHelper;
use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\Position;
use App\Models\State;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use PhpParser\Node\Scalar\String_;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;

class EditUser extends EditRecord
{
    use EditRecord\Concerns\HasWizard;

    protected static string $resource = UserResource::class;
    protected static ?string $title='Editar Usuarios';
    public $countries=[];
    public $token;
    public String $cp;

    protected function authorizeAccess(): void
    {
        abort_unless(VisorRoleHelper::canEdit(), 403, __('Ups!, no estas autorizado para realizar esta acción.'));
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    protected function getSteps():array
    {
        return [
            Step::make('Información Personal')
                //->description('This is the first step of the wizard.')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->label('Nombre(s)'),
                    TextInput::make('first_name')
                        ->maxLength(100)
                        ->default(null)
                        ->label('Primer Apellido'),
                    TextInput::make('last_name')
                        ->maxLength(100)
                        ->default(null)
                        ->label('Segundo Apellido'),
                    TextInput::make('curp')
                        ->label('CURP')
                        ->required()
                        ->unique('users', 'curp', fn ($record) => $record )
                        ->maxLength(18)
                        ->afterStateHydrated(function (Get $get,Set $set):string{
                            return $set ('curp', strtoupper($get('curp')));
                        }),
                    Select::make('sex')
                        ->label('Sexo')
                        ->options([
                            'Masculino' => 'Masculino',
                            'Femenino' => 'Femenino',
                            'Otro' => 'Otro',
                        ]),
                    Select::make('nationality')
                        ->label('Nacionalidad')
                        ->live()
                        ->options([
                            'Mexicana' => 'Mexicana',
                            'Extranjera' => 'Extranjera',
                        ])
                        ->searchable()
                        ->default(null),
                    DatePicker::make('birthdate')
                        ->label('Fecha de Nacimiento')
                        ->required(),
                    Select::make('birth_country')
                        ->live()
                        ->reactive()
                        ->label('País de Nacimiento')
                        ->options(function(Get $get,Set $set): array{
                            if($get('nationality')==="Mexicana"){
                                $this->countries = [142 => 'Mexico'];
                                $set('birth_country', 142);
                            }
                            return $this->countries;
                        })
                        ->loadingMessage('Cargando Paises...')
                        ->searchable()
                        ->default(null),
                    Select::make('birth_state')
                        ->live()
                        ->reactive()
                        ->searchable()
                        ->label('Estado de Nacimiento')
                        ->options(function(Get $get): array{
                                $country= $get('birth_country');
                                if($country){
                                   return State::where('country_id',$country)
                                       ->pluck('name','id')
                                      ->toArray();
                                }
                            return [];
                        })
                        ->loadingMessage('Cargando Estado...')
                        ->searchingMessage('Buscando Estados...')
                        ->default(null),
                    Select::make('birth_city')
                        ->label('Ciudad de Nacimiento')
                        ->searchable()
                        ->options(
                            function(Get $get): array{
                                $state= $get('birth_state');
                                if($state){
                                    return City::where('state_id',$state)
                                        ->pluck('name','id')
                                        ->toArray();
                                }
                                return [];
                            }
                        )
                        ->loadingMessage('Cargando Municipios...')
                        ->searchingMessage('Buscando Municipios...')
                        ->default(null),
                    Select::make('disability')
                        ->label('Discapacidad')
                        ->options([
                            'Ninguna' => 'Ninguna',
                            'Auditiva' => 'Auditiva',
                            'Visual' => 'Visual',
                            'Motriz' => 'Motriz',
                            'Intelectual' => 'Intelectual',
                            'Múltiple' => 'Múltiple',
                            'Otra' => 'Otra',
                        ]),
                    Select::make('marital_status')
                        ->label('Estado Civil')
                        ->options([
                            'Soltero' => 'Soltero(a)',
                            'Casado' => 'Casado(a)',
                            'Divorciado' => 'Divorciado(a)',
                            'Viudo' => 'Viudo(a)',
                            'Union Libre' => 'Union Libre',
                        ])
                ])->columns(2),
            Step::make('Información de Contacto')
                // ->description('This is the second step of the wizard.')
                ->schema([
                    Select::make('state')
                        ->label('Estado')
                        ->live()
                        // Reemplazo de API por BD Local
                        ->options(fn () => State::where('country_id', 142)->pluck('name', 'name'))
                        ->searchable()
                        ->default(null),

                    Select::make('city')
                        ->label('Ciudad')
                        ->live()
                        ->searchable()
                        ->options(function(Get $get): array{
                            $stateName = $get('state');
                            if (!$stateName) {
                                return [];
                            }

                            // Reemplazo de API por BD Local
                            return City::query()
                                ->whereHas('state', function ($query) use ($stateName) {
                                    $query->where('name', 'like', $stateName);
                                })
                                ->pluck('name', 'name')
                                ->toArray();
                        })
                        ->loadingMessage('Cargando Municipios...')
                        ->default(null),
                    TextInput::make('colony')
                        ->label('Colonia')
                        ->live()
                        ->default(null),
                    TextInput::make('cp')
                        ->label('Código Postal')
                        ->maxLength(5)
                        ->reactive(),
                    TextInput::make('address')
                        ->label('Dirección')
                        ->helperText('Calle y Número')
                        ->maxLength(255)
                        ->default(null)
                        ->columnSpan(2),
                    TextInput::make('phone')
                        ->label('Teléfono')
                        ->helperText('Número de 10 dígitos')
                        ->tel()
                        ->maxLength(10)
                        ->minLength(10)
                        ->default(null),
                    TextInput::make('emergency_name')
                        ->label('Nombre del Contacto de Emergencias')
                        ->maxLength(90)
                        ->default(null),
                    TextInput::make('relationship_contact')
                        ->label('Parentesco')
                        ->maxLength(45)
                        ->default(null),
                    TextInput::make('emergency_phone')
                        ->label('Teléfono de Emergencias')
                        ->helperText('Número de 10 dígitos')
                        ->tel()
                        ->maxLength(10)
                        ->minLength(10)
                        ->default(null),
                ])->columns(2),
            Step::make('Escolaridad')
                //->description('This is the third step of the wizard.')
                ->schema([
                    Select::make('scholarship')
                        ->label('Escolaridad')
                        ->live()
                        ->options([
                            'Sin Estudios' => 'Sin Estudios',
                            'Primaria' => 'Primaria',
                            'Secundaria' => 'Secundaria',
                            'Preparatoria' => 'Preparatoria o bachillerato',
                            'TSU' => 'Técnico Superior',
                            'Licenciatura' => 'Licenciatura',
                            'Maestría' => 'Maestría',
                            'Doctorado' => 'Doctorado',
                            'otro' => 'Sin información',
                        ])
                        ->default(null),
                    Select::make('career')
                        ->label('Área de Estudio')
                        ->disabled(fn (Get $get): bool =>
                            $get('scholarship') === 'Sin Estudios' ||
                            $get('scholarship') === 'Primaria' ||
                            $get('scholarship') === 'Secundaria' ||
                            $get('scholarship') === 'Técnico' ||
                            $get('scholarship') === 'Preparatoria' ||
                            $get('scholarship') === 'otro',
                        )
                        ->options([
                            'Humanidades' => 'Humanidades',
                            'Ciencias Sociales' => 'Ciencias Sociales',
                            'Ciencias Exactas' => 'Ciencias Exactas',
                            'Ingeniería' => 'Ingeniería',
                            'Ciencias de la Salud' => 'Ciencias de la Salud',
                            'Artes' => 'Artes',
                            'Administración' => 'Administración',
                            'Tecnología' => 'Tecnología',
                            'Otra' => 'Otra',
                        ])
                        ->default(null),
                ])->columns(2),
            Step::make('Información Laboral')
                // ->description('This is the fourth step of the wizard.')
                ->schema([
                    TextInput::make('employee_code')
                        ->label('Número de Empleado')
                        ->maxLength(14)
                        ->default(null),
                    Select::make('sede_id')
                        ->label('Sede')
                        ->live()
                        ->searchable()
                        ->relationship('sede', 'name')
                        ->default(null),
                    Select::make('razon_social_id')
                        ->label('Razón Social Operativa')
                        ->options(function (Get $get) {
                            $sedeId = $get('sede_id');
                            if (!$sedeId) {
                                return [];
                            }
                            return \App\Models\Sede::find($sedeId)->razonSocials->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->default(null),
                    Select::make('department_id')
                        ->label('Departamento')
                        ->live()
                        ->searchable()
                        ->options(fn (Get $get): Collection => Department::query()
                            ->where('sede_id', $get('sede_id'))
                            ->pluck('name', 'id'))
                        ->default(null),
                    Group::make([
                        Select::make('position_id')
                            ->label('Puesto')
                            ->options(fn (Get $get): Collection => Position::query()
                                ->where('department_id', $get('department_id'))
                                ->pluck('name', 'id'))
                            ->default(null),
                        Select::make('time_in_position')
                            ->label('Tiempo en el Puesto actual')
                            ->options([
                                'lt_6m' => 'Menos de 6 meses',    // menos de 6 meses
                                '6m_1y' => 'Entre 6 meses y 1 año' ,    // entre 6 meses y 1 año
                                '1_4y' => 'Entre 1 a 4 años',     // entre 1 a 4 años
                                '5_9y'=> 'Entre 5 a 9 años',     // entre 5 a 9 años
                                '10_14y' => 'Entre 10 a 14 años',   // 10 a 14
                                '15_19y'=> 'Entre 15 a 19 años',   // 15 a 19
                                '20_24y'=> 'Entre 20 a 24 años',   // 20 a 24
                                '25_plus'=> '25 años o más',  // 25 años o más
                            ]),
                        Toggle::make('mi')
                            ->label('Pertenece a Marcas Internas?')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(false),
                    ])->columnSpan(2),

                    select::make('staff_type')
                        ->label('Tipo de Contratación')
                        ->options([
                            'Proyecto' => 'Por Obra o Proyecto',
                            'Indeterminado' => 'Tiempo Indeterminado',
                            'Temporal' => 'Por Tiempo Determinado (temporal)',
                            'Honorarios' => 'Honorarios'
                        ])
                        ->default(null),
                    Select::make('contract_type')
                        ->label('Tipo de Personal')
                        ->options([
                            'Confianza' => 'De Confianza',
                            'Sindicalizado' => 'Sindicalizado',
                            'Ninguno' => 'Ninguno',
                        ])
                        ->default(null),
                    Toggle::make('rotates_shifts')
                        ->label('Rota Turnos?')
                        ->onColor('success')
                        ->offColor('danger')
                        ->default(false),
                    Select::make('work_shift')
                        ->label('Turno de Trabajo')
                        ->options([
                            'Diurno' => 'Fijo Diurno entre las 6 y las 20 hs.',
                            'Nocturno' => 'Fijo Nocturno entre las 20 y las 6 hs.',
                            'Mixto' => 'Mixto',
                        ])
                        ->default(null),
                    TextInput::make('experience_years')
                        ->label('Años de Experiencia Laboral')
                        ->integer()
                        ->step(1)
                        ->minValue(0),


                    TextInput::make('rfc')
                        ->label('RFC')
                        ->autocapitalize('characters')
                        ->helperText('Debe de contener 13 caractéres')
                        ->length(13)
                        ->required()
                        ->unique('users', 'rfc', fn ($record) => $record)
                        ->disableAutocomplete()
                        //->mask('/^[A-Z]{4}[0-9]{6}[A-Z0-9]{3}*$/')
                        ->default(null),
                    TextInput::make('imss')
                        ->label('Número de Seguridad Social')
                        ->maxLength(11)
                        ->required()
                        ->unique('users', 'imss', fn ($record) => $record )
                        ->default(null),
                    DatePicker::make('entry_date')
                    ->label('Fecha de Ingreso'),
                ])->columns(2),
            Step::make('Configurar Usuario')
                ->schema([
                    FileUpload::make('profile_photo')
                        ->label('Foto de Perfil')
                        ->disk('sedyco_disk')
                        ->visibility('public')
                        ->image()
                        ->avatar()
                        ->imageEditor()
                        ->circleCropper()
                        ->maxSize('2048')
                        ->maxFiles(1)
                        ->rules('image')
                        ->columnSpan('full'),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique('users', 'email', fn ($record) => $record)
                        ->maxLength(80),
                    TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->revealable()
                        ->disableAutocomplete()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->maxLength(255),
                    Select::make('roles')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->relationship('roles', 'name', function ($query) {
                            $user = auth()->user();
                            // Filtra los roles según el rol del usuario actual
                            if ($user->hasRole('Administrador')) {
                                return $query; // Admin ve todos los roles
                            }elseif($user->hasRole('RH Corp')) {

                                return $query->whereIn('name', ['RH Corp','RH', 'Colaborador', 'Supervisor','Operativo']);
                            }
                            // Ejemplo: solo roles específicos para otros usuarios
                            return $query->whereIn('name', ['Colaborador', 'Supervisor','Operativo']);
                        }),
                ])->columns(2),

        ];
    }
    public function hasSkippableSteps(): bool
    {
        return true;
    }
    public function mount($record):void
    {

        $user = User::find($record);
        $this->countries=Country::all()->pluck('name','id')->toArray();

        parent::mount($record); // TODO: Change the autogenerated stub

    }
}
