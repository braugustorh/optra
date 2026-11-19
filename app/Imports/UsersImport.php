<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\City;
use App\Models\Country;
use App\Models\Department;
use App\Models\Position;
use App\Models\RazonSocial;
use App\Models\Sede;
use App\Models\State;
use App\Models\User;
use App\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Spatie\Permission\Models\Role;

class UsersImport implements ToCollection, WithHeadingRow, WithMultipleSheets
{
    private int $createdCount = 0;

    private int $updatedCount = 0;

    /**
     * @var array<int, array{row: int, identifier: string, errors: array<int, string>}>
     */
    private array $failures = [];

    /**
     * @var array<string, bool>
     */
    private array $seenCurps = [];

    /**
     * @var array<string, bool>
     */
    private array $seenEmails = [];

    /**
     * @var array<string, bool>
     */
    private array $seenRfcs = [];

    /**
     * @var array<string, bool>
     */
    private array $seenImss = [];

    /**
     * @var array<string, bool>
     */
    private array $seenEmployeeCodes = [];

    /**
     * @var array<string, Sede|null>
     */
    private array $sedeCache = [];

    /**
     * @var array<int, Sede>
     */
    private array $touchedSedes = [];

    /**
     * @var Collection<int, Country>
     */
    private Collection $countries;

    /**
     * @var Collection<int, State>
     */
    private Collection $states;

    /**
     * @var Collection<int, Collection<int, City>>
     */
    private Collection $citiesByState;

    /**
     * @var array<string, string>
     */
    private array $stateAliases = [
        'CDMX' => 'CIUDAD DE MEXICO',
        'DF' => 'CIUDAD DE MEXICO',
        'D.F.' => 'CIUDAD DE MEXICO',
        'EDO MEX' => 'MEXICO',
        'EDOMEX' => 'MEXICO',
        'ESTADO DE MEXICO' => 'MEXICO',
        'COAHUILA' => 'COAHUILA DE ZARAGOZA',
        'MICHOACAN' => 'MICHOACAN DE OCAMPO',
        'VERACRUZ' => 'VERACRUZ DE IGNACIO DE LA LLAVE',
        'QRO' => 'QUERETARO',
        'GTO' => 'GUANAJUATO',
    ];

    /**
     * @var array<string, string>
     */
    private array $cityAliases = [
        'DOLORES HIDALGO' => 'DOLORES HIDALGO CUNA DE LA INDEPENDENCIA NACIONAL',
        'SILAO' => 'SILAO DE LA VICTORIA',
        'SAN MIGUEL' => 'SAN MIGUEL DE ALLENDE',
        'SMA' => 'SAN MIGUEL DE ALLENDE',
        'TLAQUEPAQUE' => 'SAN PEDRO TLAQUEPAQUE',
        'SANTIAGO DE QUERETARO' => 'QUERÉTARO',
        'QUERETARO' => 'QUERÉTARO',
        'CD GUZMAN' => 'CIUDAD GUZMÁN',
        'GUZMAN' => 'CIUDAD GUZMÁN',
        'CIUDAD DE MEXICO' => 'CIUDAD DE MÉXICO',
        'CDMX' => 'CIUDAD DE MÉXICO',
    ];

    public function __construct(
        private readonly ?User $actor = null,
        private readonly string $mode = 'create_only',
        private readonly bool $autoCreateStructure = false,
        private readonly bool $autoAdjustOpenPositions = true,
    ) {
        $this->countries = Country::all();
        $this->states = State::query()->where('country_id', 142)->get();
        if ($this->states->isEmpty()) {
            $this->states = State::all();
        }
        $this->citiesByState = City::query()
            ->select('id', 'state_id', 'name')
            ->get()
            ->groupBy('state_id');
    }

    public function sheets(): array
    {
        return [
            0 => $this,
        ];
    }

    public function collection(Collection $collection): void
    {
        foreach ($collection as $index => $rawRow) {
            $excelRow = (int) $index + 2;
            $rowArray = is_array($rawRow) ? $rawRow : $rawRow->toArray();

            if ($this->isRowEmpty($rowArray)) {
                continue;
            }

            $data = $this->extractRowData($rowArray);
            $identifier = $this->resolveRowIdentifier($data);
            $errors = [];

            $normalized = $this->normalizeAndValidateRow($data, $errors);

            $existingUser = null;
            if ($normalized['curp'] !== null) {
                $existingUser = User::query()->where('curp', $normalized['curp'])->first();
            }

            if ($this->mode === 'create_only' && $existingUser !== null) {
                $errors[] = "Ya existe un usuario con la CURP {$normalized['curp']}.";
            }

            $this->validateFileDuplicates($normalized, $errors);
            $this->validateDatabaseDuplicates($normalized, $existingUser, $errors);

            $orgIds = $this->resolveOrganization($normalized, $existingUser !== null, $errors);
            $roleName = $this->resolveAndValidateRole($normalized['role'], $existingUser === null, $errors);

            if ($errors !== []) {
                $this->failures[] = [
                    'row' => $excelRow,
                    'identifier' => $identifier,
                    'errors' => array_values(array_unique($errors)),
                ];
                continue;
            }

            try {
                DB::transaction(function () use ($normalized, $existingUser, $orgIds, $roleName): void {
                    $geo = $this->resolveGeography($normalized);
                    $payload = $this->buildUserPayload($normalized, $orgIds, $geo, $existingUser);

                    if ($existingUser !== null) {
                        $existingUser->update($payload);
                        $user = $existingUser->fresh();
                        $this->updatedCount++;
                    } else {
                        $user = User::query()->create($payload);
                        $this->createdCount++;
                    }

                    if ($roleName !== null) {
                        $user->syncRoles([$roleName]);
                    }

                    if ($user->entry_date) {
                        try {
                            VacationService::createOrUpdateBalance($user);
                        } catch (\Throwable $e) {
                            Log::warning("No se pudo inicializar balance de vacaciones para usuario {$user->id}: {$e->getMessage()}");
                        }
                    }
                });

                $this->markSeenValues($normalized);
            } catch (\Throwable $e) {
                Log::error("Error importando fila {$excelRow}: {$e->getMessage()}");
                $this->failures[] = [
                    'row' => $excelRow,
                    'identifier' => $identifier,
                    'errors' => ['Error al guardar en base de datos: ' . $e->getMessage()],
                ];
            }
        }

        if ($this->autoAdjustOpenPositions && $this->touchedSedes !== []) {
            foreach ($this->touchedSedes as $sede) {
                $occupied = $sede->count_positions($sede->id);
                if ($occupied > (int) $sede->open_positions) {
                    $sede->update(['open_positions' => $occupied]);
                }
            }
        }
    }

    public function getCreatedCount(): int
    {
        return $this->createdCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getProcessedCount(): int
    {
        return $this->createdCount + $this->updatedCount;
    }

    /**
     * @return array<int, array{row: int, identifier: string, errors: array<int, string>}>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    public function hasFailures(): bool
    {
        return $this->failures !== [];
    }

    /**
     * @param  array<mixed>  $row
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string|int, mixed>  $rawRow
     * @return array<string, mixed>
     */
    private function extractRowData(array $rawRow): array
    {
        $row = [];
        foreach ($rawRow as $key => $value) {
            $slug = Str::slug((string) $key, '_');
            if ($slug !== '') {
                $row[$slug] = $value;
            }
            $row[(string) $key] = $value;
        }

        $pick = static function (array $keys) use ($row): mixed {
            foreach ($keys as $key) {
                if (array_key_exists($key, $row) && $row[$key] !== null && trim((string) $row[$key]) !== '') {
                    return $row[$key];
                }
            }

            return null;
        };

        return [
            'name' => $pick(['nombres', 'nombre', 'nombres_s', 'name']),
            'first_name' => $pick(['primer_apellido', 'apellido_paterno', 'first_name']),
            'last_name' => $pick(['segundo_apellido', 'apellido_materno', 'last_name']),
            'curp' => $pick(['curp']),
            'sex' => $pick(['sexo', 'genero', 'sex']),
            'birthdate' => $pick(['fecha_de_nacimiento_aaaa_mm_dd', 'fecha_de_nacimiento', 'birthdate']),
            'nationality' => $pick(['nacionalidad', 'nationality']),
            'birth_country' => $pick(['pais_de_nacimiento', 'birth_country']),
            'birth_state' => $pick(['estado_de_nacimiento', 'birth_state']),
            'birth_city' => $pick(['ciudad_de_nacimiento', 'municipio_de_nacimiento', 'birth_city']),
            'disability' => $pick(['discapacidad', 'disability']),
            'marital_status' => $pick(['estado_civil', 'marital_status']),
            'state' => $pick(['estado_domicilio', 'estado', 'state']),
            'city' => $pick(['ciudad_domicilio', 'municipio_domicilio', 'ciudad', 'city']),
            'colony' => $pick(['colonia', 'colony']),
            'cp' => $pick(['codigo_postal', 'cp']),
            'address' => $pick(['direccion_calle_y_numero', 'direccion', 'address']),
            'phone' => $pick(['telefono_personal_10_digitos', 'telefono_personal', 'telefono', 'phone']),
            'emergency_name' => $pick(['contacto_de_emergencia', 'nombre_contacto_emergencia', 'emergency_name']),
            'relationship_contact' => $pick(['parentesco_emergencia', 'parentesco', 'relationship_contact']),
            'emergency_phone' => $pick(['telefono_emergencia_10_digitos', 'telefono_emergencia', 'telefono_de_emergencias', 'emergency_phone']),
            'scholarship' => $pick(['escolaridad', 'scholarship']),
            'career' => $pick(['area_de_estudio', 'carrera', 'career']),
            'employee_code' => $pick(['numero_de_empleado', 'no_empleado', 'codigo_empleado', 'employee_code']),
            'sede' => $pick(['sede', 'sede_name']),
            'razon_social' => $pick(['razon_social', 'razon_social_operativa', 'razon_social_name']),
            'department' => $pick(['departamento', 'department', 'department_name']),
            'position' => $pick(['puesto', 'position', 'position_name']),
            'time_in_position' => $pick(['tiempo_en_el_puesto', 'time_in_position']),
            'mi' => $pick(['marcas_internas_sino', 'marcas_internas', 'mi']),
            'staff_type' => $pick(['tipo_de_contratacion', 'staff_type']),
            'contract_type' => $pick(['tipo_de_personal', 'contract_type']),
            'rotates_shifts' => $pick(['rota_turnos_sino', 'rota_turnos', 'rotates_shifts']),
            'work_shift' => $pick(['turno_de_trabajo', 'work_shift']),
            'experience_years' => $pick(['anos_de_experiencia', 'experiencia_anos', 'experience_years']),
            'rfc' => $pick(['rfc']),
            'imss' => $pick(['nss_imss_11_digitos', 'nss_imss', 'nss', 'imss']),
            'entry_date' => $pick(['fecha_de_ingreso_aaaa_mm_dd', 'fecha_de_ingreso', 'entry_date']),
            'email' => $pick(['correo_electronico_acceso', 'correo_electronico', 'correo', 'email']),
            'alternate_email' => $pick(['correo_alterno_personal', 'correo_alterno', 'alternate_email']),
            'password' => $pick(['contrasena_temporal', 'contrasena', 'password']),
            'role' => $pick(['rol', 'role', 'roles']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveRowIdentifier(array $data): string
    {
        $parts = array_filter([
            $this->cleanString($data['curp']),
            trim(implode(' ', array_filter([
                $this->cleanString($data['name']),
                $this->cleanString($data['first_name']),
            ]))),
            $this->cleanString($data['email']),
        ]);

        return $parts !== [] ? implode(' — ', array_slice($parts, 0, 2)) : 'Sin identificador';
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $errors
     * @return array<string, mixed>
     */
    private function normalizeAndValidateRow(array $data, array &$errors): array
    {
        $name = $this->cleanString($data['name']);
        $firstName = $this->cleanString($data['first_name']);
        $lastName = $this->cleanString($data['last_name']);

        if ($name === null) {
            $errors[] = 'El campo Nombre(s) es obligatorio.';
        } elseif (mb_strlen($name) > 100) {
            $errors[] = 'El campo Nombre(s) excede 100 caracteres.';
        }

        if ($firstName === null) {
            $errors[] = 'El campo Primer Apellido es obligatorio.';
        } elseif (mb_strlen($firstName) > 100) {
            $errors[] = 'El campo Primer Apellido excede 100 caracteres.';
        }

        if ($lastName !== null && mb_strlen($lastName) > 100) {
            $errors[] = 'El campo Segundo Apellido excede 100 caracteres.';
        }

        $curp = $this->cleanUpperNoSpaces($data['curp']);
        if ($curp === null) {
            $errors[] = 'La CURP es obligatoria.';
        } elseif (! preg_match('/^[A-Z0-9]{18}$/', $curp)) {
            $errors[] = "CURP inválida ({$curp}): debe tener 18 caracteres alfanuméricos.";
        }

        $birthdate = $this->parseDate($data['birthdate'], 'Fecha de Nacimiento', true, $errors);
        if ($birthdate !== null && Carbon::parse($birthdate)->isFuture()) {
            $errors[] = 'La Fecha de Nacimiento no puede ser futura.';
        }

        $rfc = $this->cleanUpperNoSpaces($data['rfc']);
        if ($rfc === null) {
            $errors[] = 'El RFC es obligatorio.';
        } elseif (! preg_match('/^[A-Z0-9&Ñ]{12,13}$/u', $rfc)) {
            $errors[] = "RFC inválido ({$rfc}): debe tener 12 o 13 caracteres.";
        }

        $imssRaw = $this->cleanString($data['imss']);
        $imss = $imssRaw !== null ? preg_replace('/\D+/', '', $imssRaw) : null;
        if ($imss === null || $imss === '') {
            $errors[] = 'El NSS / IMSS es obligatorio.';
            $imss = null;
        } elseif (! preg_match('/^\d{11}$/', $imss)) {
            $errors[] = "NSS / IMSS inválido ({$imss}): debe contener exactamente 11 dígitos numéricos.";
        }

        $employeeCode = $this->cleanString($data['employee_code']);
        if ($employeeCode === null) {
            $errors[] = 'El Número de Empleado es obligatorio.';
        } elseif (mb_strlen($employeeCode) > 25) {
            $errors[] = 'El Número de Empleado excede 25 caracteres.';
        }

        $email = $this->cleanString($data['email']);
        if ($email !== null) {
            $email = mb_strtolower($email, 'UTF-8');
        }
        if ($email === null) {
            $errors[] = 'El Correo Electrónico es obligatorio.';
        } elseif (mb_strlen($email) > 80 || ! $this->isValidEmailOrInternalLogin($email)) {
            $errors[] = "Correo Electrónico inválido ({$email}).";
        }

        $alternateEmail = $this->cleanString($data['alternate_email']);
        if ($alternateEmail !== null) {
            $alternateEmail = mb_strtolower($alternateEmail, 'UTF-8');
            if (mb_strlen($alternateEmail) > 255 || ! filter_var($alternateEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Correo Alterno inválido ({$alternateEmail}).";
            }
        }

        $phone = $this->normalizePhone($data['phone'], 'Teléfono Personal', $errors);
        $emergencyPhone = $this->normalizePhone($data['emergency_phone'], 'Teléfono Emergencia', $errors);

        $cp = null;
        $cpRaw = $this->cleanString($data['cp']);
        if ($cpRaw !== null) {
            $cpDigits = preg_replace('/\D+/', '', $cpRaw);
            if ($cpDigits === '' || mb_strlen($cpDigits) < 4 || mb_strlen($cpDigits) > 5) {
                $errors[] = "Código Postal inválido ({$cpRaw}): debe tener 5 dígitos.";
            } else {
                $cp = (int) $cpDigits;
            }
        }

        $experienceYears = null;
        $expRaw = $this->cleanString($data['experience_years']);
        if ($expRaw !== null) {
            if (! is_numeric($expRaw) || (int) $expRaw < 0) {
                $errors[] = "Años de Experiencia inválido ({$expRaw}): debe ser entero mayor o igual a 0.";
            } else {
                $experienceYears = (int) $expRaw;
            }
        }

        $entryDate = $this->parseDate($data['entry_date'], 'Fecha de Ingreso', false, $errors);

        $sex = $this->mapCatalog($data['sex'], [
            'MASCULINO' => 'Masculino',
            'M' => 'Masculino',
            'HOMBRE' => 'Masculino',
            'FEMENINO' => 'Femenino',
            'F' => 'Femenino',
            'MUJER' => 'Femenino',
            'OTRO' => 'Otro',
        ], 'Sexo', $errors);

        $nationality = $this->mapCatalog($data['nationality'], [
            'MEXICANA' => 'Mexicana',
            'MEXICO' => 'Mexicana',
            'MEXICANO' => 'Mexicana',
            'EXTRANJERA' => 'Extranjera',
            'EXTRANJERO' => 'Extranjera',
        ], 'Nacionalidad', $errors);

        $disability = $this->mapCatalog($data['disability'], [
            'NINGUNA' => 'Ninguna',
            'NO' => 'Ninguna',
            'SIN DISCAPACIDAD' => 'Ninguna',
            'AUDITIVA' => 'Auditiva',
            'VISUAL' => 'Visual',
            'MOTRIZ' => 'Motriz',
            'INTELECTUAL' => 'Intelectual',
            'MULTIPLE' => 'Múltiple',
            'OTRA' => 'Otra',
        ], 'Discapacidad', $errors);

        $maritalStatus = $this->mapCatalog($data['marital_status'], [
            'SOLTERO' => 'Soltero',
            'SOLTERA' => 'Soltero',
            'SOLTERO(A)' => 'Soltero',
            'CASADO' => 'Casado',
            'CASADA' => 'Casado',
            'CASADO(A)' => 'Casado',
            'DIVORCIADO' => 'Divorciado',
            'DIVORCIADA' => 'Divorciado',
            'DIVORCIADO(A)' => 'Divorciado',
            'VIUDO' => 'Viudo',
            'VIUDA' => 'Viudo',
            'VIUDO(A)' => 'Viudo',
            'UNION LIBRE' => 'Union Libre',
        ], 'Estado Civil', $errors);

        $scholarship = $this->mapCatalog($data['scholarship'], [
            'SIN ESTUDIOS' => 'Sin Estudios',
            'PRIMARIA' => 'Primaria',
            'SECUNDARIA' => 'Secundaria',
            'PREPARATORIA' => 'Preparatoria',
            'PREPARATORIA O BACHILLERATO' => 'Preparatoria',
            'BACHILLERATO' => 'Preparatoria',
            'TSU' => 'TSU',
            'TECNICO SUPERIOR' => 'TSU',
            'TECNICO' => 'TSU',
            'LICENCIATURA' => 'Licenciatura',
            'INGENIERIA' => 'Licenciatura',
            'MAESTRIA' => 'Maestría',
            'DOCTORADO' => 'Doctorado',
            'OTRO' => 'otro',
            'SIN INFORMACION' => 'otro',
        ], 'Escolaridad', $errors);

        $career = $this->mapCatalog($data['career'], [
            'HUMANIDADES' => 'Humanidades',
            'CIENCIAS SOCIALES' => 'Ciencias Sociales',
            'CIENCIAS EXACTAS' => 'Ciencias Exactas',
            'INGENIERIA' => 'Ingeniería',
            'CIENCIAS DE LA SALUD' => 'Ciencias de la Salud',
            'ARTES' => 'Artes',
            'ADMINISTRACION' => 'Administración',
            'TECNOLOGIA' => 'Tecnología',
            'TECONOLOGIA' => 'Tecnología',
            'OTRA' => 'Otra',
        ], 'Área de Estudio', $errors);

        $timeInPosition = $this->mapCatalog($data['time_in_position'], [
            'LT_6M' => 'lt_6m',
            'MENOS DE 6 MESES' => 'lt_6m',
            '6M_1Y' => '6m_1y',
            'ENTRE 6 MESES Y 1 ANO' => '6m_1y',
            '1_4Y' => '1_4y',
            'ENTRE 1 A 4 ANOS' => '1_4y',
            '5_9Y' => '5_9y',
            'ENTRE 5 A 9 ANOS' => '5_9y',
            '10_14Y' => '10_14y',
            'ENTRE 10 A 14 ANOS' => '10_14y',
            '15_19Y' => '15_19y',
            'ENTRE 15 A 19 ANOS' => '15_19y',
            '20_24Y' => '20_24y',
            'ENTRE 20 A 24 ANOS' => '20_24y',
            '25_PLUS' => '25_plus',
            '25 ANOS O MAS' => '25_plus',
        ], 'Tiempo en el Puesto', $errors);

        $staffType = $this->mapCatalog($data['staff_type'], [
            'PROYECTO' => 'Proyecto',
            'POR OBRA O PROYECTO' => 'Proyecto',
            'INDETERMINADO' => 'Indeterminado',
            'TIEMPO INDETERMINADO' => 'Indeterminado',
            'TEMPORAL' => 'Temporal',
            'POR TIEMPO DETERMINADO' => 'Temporal',
            'POR TIEMPO DETERMINADO (TEMPORAL)' => 'Temporal',
            'HONORARIOS' => 'Honorarios',
        ], 'Tipo de Contratación', $errors);

        $contractType = $this->mapCatalog($data['contract_type'], [
            'CONFIANZA' => 'Confianza',
            'DE CONFIANZA' => 'Confianza',
            'SINDICALIZADO' => 'Sindicalizado',
            'NINGUNO' => 'Ninguno',
        ], 'Tipo de Personal', $errors);

        $workShift = $this->mapCatalog($data['work_shift'], [
            'DIURNO' => 'Diurno',
            'FIJO DIURNO' => 'Diurno',
            'FIJO DIURNO ENTRE LAS 6 Y LAS 20 HS.' => 'Diurno',
            'NOCTURNO' => 'Nocturno',
            'FIJO NOCTURNO' => 'Nocturno',
            'FIJO NOCTURNO ENTRE LAS 20 Y LAS 6 HS.' => 'Nocturno',
            'MIXTO' => 'Mixto',
        ], 'Turno de Trabajo', $errors);

        $sedeName = $this->cleanString($data['sede']);
        $departmentName = $this->cleanString($data['department']);
        $positionName = $this->cleanString($data['position']);

        if ($sedeName === null) {
            $errors[] = 'La Sede es obligatoria.';
        }
        if ($departmentName === null) {
            $errors[] = 'El Departamento es obligatorio.';
        }
        if ($positionName === null) {
            $errors[] = 'El Puesto es obligatorio.';
        }

        return [
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'curp' => $curp,
            'sex' => $sex,
            'birthdate' => $birthdate,
            'nationality' => $nationality,
            'birth_country' => $this->cleanString($data['birth_country']),
            'birth_state' => $this->cleanString($data['birth_state']),
            'birth_city' => $this->cleanString($data['birth_city']),
            'disability' => $disability,
            'marital_status' => $maritalStatus,
            'state' => $this->cleanString($data['state']),
            'city' => $this->cleanString($data['city']),
            'colony' => $this->limitString($this->cleanString($data['colony']), 100),
            'cp' => $cp,
            'address' => $this->limitString($this->cleanString($data['address']), 255),
            'phone' => $phone,
            'emergency_name' => $this->limitString($this->cleanString($data['emergency_name']), 100),
            'relationship_contact' => $this->limitString($this->cleanString($data['relationship_contact']), 100),
            'emergency_phone' => $emergencyPhone,
            'scholarship' => $scholarship,
            'career' => $career,
            'employee_code' => $employeeCode,
            'sede' => $sedeName,
            'razon_social' => $this->cleanString($data['razon_social']),
            'department' => $departmentName,
            'position' => $positionName,
            'time_in_position' => $timeInPosition,
            'mi' => $this->parseBoolean($data['mi']),
            'staff_type' => $staffType,
            'contract_type' => $contractType,
            'rotates_shifts' => $this->parseBoolean($data['rotates_shifts']),
            'work_shift' => $workShift,
            'experience_years' => $experienceYears,
            'rfc' => $rfc,
            'imss' => $imss,
            'entry_date' => $entryDate,
            'email' => $email,
            'alternate_email' => $alternateEmail,
            'password' => $this->cleanString($data['password']),
            'role' => $this->cleanString($data['role']),
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @param  array<int, string>  $errors
     */
    private function validateFileDuplicates(array $normalized, array &$errors): void
    {
        if ($normalized['curp'] !== null && isset($this->seenCurps[$normalized['curp']])) {
            $errors[] = "CURP duplicada dentro del archivo ({$normalized['curp']}).";
        }
        if ($normalized['email'] !== null && isset($this->seenEmails[$normalized['email']])) {
            $errors[] = "Correo duplicado dentro del archivo ({$normalized['email']}).";
        }
        if ($normalized['rfc'] !== null && isset($this->seenRfcs[$normalized['rfc']])) {
            $errors[] = "RFC duplicado dentro del archivo ({$normalized['rfc']}).";
        }
        if ($normalized['imss'] !== null && isset($this->seenImss[$normalized['imss']])) {
            $errors[] = "NSS / IMSS duplicado dentro del archivo ({$normalized['imss']}).";
        }
        if ($normalized['employee_code'] !== null && isset($this->seenEmployeeCodes[$normalized['employee_code']])) {
            $errors[] = "Número de Empleado duplicado dentro del archivo ({$normalized['employee_code']}).";
        }
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @param  array<int, string>  $errors
     */
    private function validateDatabaseDuplicates(array $normalized, ?User $existingUser, array &$errors): void
    {
        $ignoreId = $existingUser?->id;

        if ($normalized['email'] !== null) {
            $q = User::query()->where('email', $normalized['email']);
            if ($ignoreId !== null) {
                $q->where('id', '!=', $ignoreId);
            }
            if ($q->exists()) {
                $errors[] = "El correo {$normalized['email']} ya pertenece a otro usuario.";
            }
        }

        if ($normalized['rfc'] !== null) {
            $q = User::query()->where('rfc', $normalized['rfc']);
            if ($ignoreId !== null) {
                $q->where('id', '!=', $ignoreId);
            }
            if ($q->exists()) {
                $errors[] = "El RFC {$normalized['rfc']} ya pertenece a otro usuario.";
            }
        }

        if ($normalized['imss'] !== null) {
            $q = User::query()->where('imss', $normalized['imss']);
            if ($ignoreId !== null) {
                $q->where('id', '!=', $ignoreId);
            }
            if ($q->exists()) {
                $errors[] = "El NSS / IMSS {$normalized['imss']} ya pertenece a otro usuario.";
            }
        }

        if ($normalized['employee_code'] !== null) {
            $q = User::query()->where('employee_code', $normalized['employee_code']);
            if ($ignoreId !== null) {
                $q->where('id', '!=', $ignoreId);
            }
            if ($q->exists()) {
                $errors[] = "El Número de Empleado {$normalized['employee_code']} ya pertenece a otro usuario.";
            }
        }
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @param  array<int, string>  $errors
     * @return array{sede_id: ?int, razon_social_id: ?int, department_id: ?int, position_id: ?int}
     */
    private function resolveOrganization(array $normalized, bool $isUpdate, array &$errors): array
    {
        $result = [
            'sede_id' => null,
            'razon_social_id' => null,
            'department_id' => null,
            'position_id' => null,
        ];

        $sedeName = $normalized['sede'];
        if ($sedeName === null) {
            return $result;
        }

        $sede = $this->findSedeByName($sedeName);
        if ($sede === null) {
            $errors[] = "No se encontró la Sede '{$sedeName}'.";
            return $result;
        }

        if ($this->isLocalRhActor() && (int) $sede->id !== (int) $this->actor?->sede_id) {
            $errors[] = "No tienes permisos para importar colaboradores en la sede '{$sede->name}'.";
            return $result;
        }

        if (! $isUpdate && $this->isLocalRhActor()) {
            $occupied = $sede->count_positions($sede->id);
            if ($occupied >= (int) $sede->open_positions) {
                $errors[] = "Se alcanzó el límite de posiciones abiertas ({$sede->open_positions}) para la sede '{$sede->name}'.";
                return $result;
            }
        }

        $result['sede_id'] = $sede->id;
        $this->touchedSedes[$sede->id] = $sede;

        $canAutoCreate = $this->autoCreateStructure && ! $this->isLocalRhActor();

        $department = null;
        if ($normalized['department'] !== null) {
            $department = $this->findDepartmentByName($sede->id, $normalized['department']);
            if ($department === null) {
                if ($canAutoCreate) {
                    $department = Department::query()->create([
                        'sede_id' => $sede->id,
                        'name' => $normalized['department'],
                        'status' => true,
                    ]);
                } else {
                    $errors[] = "El departamento '{$normalized['department']}' no existe en la sede '{$sede->name}'.";
                }
            }
        }

        if ($department !== null) {
            $result['department_id'] = $department->id;

            if ($normalized['position'] !== null) {
                $position = $this->findPositionByName($department->id, $normalized['position']);
                if ($position === null) {
                    if ($canAutoCreate) {
                        $position = Position::query()->create([
                            'department_id' => $department->id,
                            'name' => $normalized['position'],
                            'status' => true,
                            'order' => 1,
                            'evaluation_grades' => 360,
                        ]);
                    } else {
                        $errors[] = "El puesto '{$normalized['position']}' no existe en el departamento '{$department->name}'.";
                    }
                }

                $result['position_id'] = $position?->id;
            }
        }

        if ($normalized['razon_social'] !== null) {
            $razon = $this->findRazonSocialByName($normalized['razon_social']);
            if ($razon === null) {
                $errors[] = "No se encontró la Razón Social '{$normalized['razon_social']}'.";
            } else {
                $result['razon_social_id'] = $razon->id;
            }
        } else {
            $result['razon_social_id'] = $sede->razonSocials()->first()?->id;
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function resolveAndValidateRole(?string $requestedRole, bool $isNewUser, array &$errors): ?string
    {
        if ($requestedRole === null) {
            if (! $isNewUser) {
                return null;
            }

            return Role::query()->where('name', 'Colaborador')->exists() ? 'Colaborador' : null;
        }

        $role = Role::query()
            ->get()
            ->first(fn (Role $r) => $this->sanitize($r->name) === $this->sanitize($requestedRole));

        if ($role === null) {
            $errors[] = "El rol '{$requestedRole}' no existe en el sistema.";
            return null;
        }

        $allowedRoles = $this->allowedRoleNames();
        if ($allowedRoles !== null && ! in_array($role->name, $allowedRoles, true)) {
            $errors[] = "No tienes permisos para asignar el rol '{$role->name}'.";
            return null;
        }

        return $role->name;
    }

    /**
     * @return array<int, string>|null
     */
    private function allowedRoleNames(): ?array
    {
        if ($this->actor === null) {
            return null;
        }

        if ($this->actor->hasAnyRole(['Administrador', 'Super Administrador'])) {
            return null;
        }

        if ($this->actor->hasRole('RH Corp')) {
            return ['RH Corp', 'RH', 'Colaborador', 'Supervisor', 'Operativo', 'Compulsa', 'Apoyo Expedientes', 'Reclutador Jr', 'Reclutador'];
        }

        return ['Colaborador', 'Supervisor', 'Operativo'];
    }

    private function isLocalRhActor(): bool
    {
        return $this->actor !== null
            && $this->actor->hasRole('RH')
            && ! $this->actor->hasAnyRole(['RH Corp', 'Administrador', 'Super Administrador']);
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @return array{birth_country: ?string, birth_state: ?string, birth_city: ?string, state: ?string, city: ?string}
     */
    private function resolveGeography(array $normalized): array
    {
        $birthCountry = $normalized['birth_country'];
        if ($birthCountry === null && $normalized['nationality'] === 'Mexicana') {
            $birthCountry = '142';
        } elseif ($birthCountry !== null) {
            if (is_numeric($birthCountry) && $this->countries->contains('id', (int) $birthCountry)) {
                $birthCountry = (string) ((int) $birthCountry);
            } else {
                $sanitizedCountry = $this->sanitize($birthCountry);
                if (in_array($sanitizedCountry, ['MEXICO', 'MEXICANA'], true)) {
                    $birthCountry = '142';
                } else {
                    $matchedCountry = $this->countries->first(
                        fn (Country $c) => $this->sanitize($c->name) === $sanitizedCountry
                    );
                    $birthCountry = $matchedCountry ? (string) $matchedCountry->id : $this->limitString($birthCountry, 100);
                }
            }
        }

        $birthState = $normalized['birth_state'];
        $birthStateModel = null;
        if ($birthState !== null) {
            if (is_numeric($birthState)) {
                $birthStateModel = $this->states->firstWhere('id', (int) $birthState);
                $birthState = $birthStateModel ? (string) $birthStateModel->id : $birthState;
            } else {
                $birthStateModel = $this->findState($birthState);
                $birthState = $birthStateModel ? (string) $birthStateModel->id : $this->limitString($birthState, 100);
            }
        }

        $birthCity = $normalized['birth_city'];
        if ($birthCity !== null) {
            if (is_numeric($birthCity) && $birthStateModel !== null) {
                $cityCollection = $this->citiesByState->get($birthStateModel->id) ?? collect();
                $matchedCity = $cityCollection->firstWhere('id', (int) $birthCity);
                $birthCity = $matchedCity ? (string) $matchedCity->id : $birthCity;
            } elseif ($birthStateModel !== null) {
                $cityCollection = $this->citiesByState->get($birthStateModel->id) ?? collect();
                $matchedCity = $this->findCity($birthCity, $cityCollection);
                $birthCity = $matchedCity ? (string) $matchedCity->id : $this->limitString($birthCity, 100);
            } else {
                $birthCity = $this->limitString($birthCity, 100);
            }
        }

        $homeState = $normalized['state'];
        $homeStateModel = null;
        if ($homeState !== null) {
            $homeStateModel = $this->findState($homeState);
            $homeState = $homeStateModel ? $homeStateModel->name : $this->limitString($homeState, 100);
        }

        $homeCity = $normalized['city'];
        if ($homeCity !== null) {
            if ($homeStateModel !== null) {
                $cityCollection = $this->citiesByState->get($homeStateModel->id) ?? collect();
                $matchedHomeCity = $this->findCity($homeCity, $cityCollection);
                $homeCity = $matchedHomeCity ? $matchedHomeCity->name : $this->limitString($homeCity, 100);
            } else {
                $homeCity = $this->limitString($homeCity, 100);
            }
        }

        return [
            'birth_country' => $birthCountry,
            'birth_state' => $birthState,
            'birth_city' => $birthCity,
            'state' => $homeState,
            'city' => $homeCity,
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     * @param  array{sede_id: ?int, razon_social_id: ?int, department_id: ?int, position_id: ?int}  $orgIds
     * @param  array{birth_country: ?string, birth_state: ?string, birth_city: ?string, state: ?string, city: ?string}  $geo
     * @return array<string, mixed>
     */
    private function buildUserPayload(array $normalized, array $orgIds, array $geo, ?User $existingUser): array
    {
        $payload = [
            'name' => $normalized['name'],
            'first_name' => $normalized['first_name'],
            'last_name' => $normalized['last_name'],
            'curp' => $normalized['curp'],
            'sex' => $normalized['sex'],
            'nationality' => $normalized['nationality'],
            'birthdate' => $normalized['birthdate'],
            'birth_country' => $geo['birth_country'],
            'birth_state' => $geo['birth_state'],
            'birth_city' => $geo['birth_city'],
            'disability' => $normalized['disability'],
            'marital_status' => $normalized['marital_status'],
            'state' => $geo['state'],
            'city' => $geo['city'],
            'colony' => $normalized['colony'],
            'cp' => $normalized['cp'],
            'address' => $normalized['address'],
            'phone' => $normalized['phone'],
            'emergency_name' => $normalized['emergency_name'],
            'relationship_contact' => $normalized['relationship_contact'],
            'emergency_phone' => $normalized['emergency_phone'],
            'scholarship' => $normalized['scholarship'],
            'career' => $normalized['career'],
            'employee_code' => $normalized['employee_code'],
            'employee_number' => mb_substr((string) $normalized['employee_code'], 0, 10),
            'sede_id' => $orgIds['sede_id'],
            'razon_social_id' => $orgIds['razon_social_id'],
            'department_id' => $orgIds['department_id'],
            'position_id' => $orgIds['position_id'],
            'time_in_position' => $normalized['time_in_position'],
            'mi' => $normalized['mi'],
            'staff_type' => $normalized['staff_type'],
            'contract_type' => $normalized['contract_type'],
            'rotates_shifts' => $normalized['rotates_shifts'],
            'work_shift' => $normalized['work_shift'],
            'experience_years' => $normalized['experience_years'],
            'rfc' => $normalized['rfc'],
            'imss' => $normalized['imss'],
            'entry_date' => $normalized['entry_date'],
            'email' => $normalized['email'],
            'alternate_email' => $normalized['alternate_email'],
        ];

        if ($existingUser === null) {
            $rawPassword = $normalized['password'] ?? "#Optra{$normalized['employee_code']}.";
            $payload['password'] = Hash::make($rawPassword);
            $payload['must_change_password'] = true;
            $payload['status'] = true;
            $payload['email_verified_at'] = now();
        } elseif ($normalized['password'] !== null) {
            $payload['password'] = Hash::make($normalized['password']);
            $payload['must_change_password'] = true;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    private function markSeenValues(array $normalized): void
    {
        if ($normalized['curp'] !== null) {
            $this->seenCurps[$normalized['curp']] = true;
        }
        if ($normalized['email'] !== null) {
            $this->seenEmails[$normalized['email']] = true;
        }
        if ($normalized['rfc'] !== null) {
            $this->seenRfcs[$normalized['rfc']] = true;
        }
        if ($normalized['imss'] !== null) {
            $this->seenImss[$normalized['imss']] = true;
        }
        if ($normalized['employee_code'] !== null) {
            $this->seenEmployeeCodes[$normalized['employee_code']] = true;
        }
    }

    private function findSedeByName(string $name): ?Sede
    {
        $sanitizedTarget = $this->sanitize($name);
        if (array_key_exists($sanitizedTarget, $this->sedeCache)) {
            return $this->sedeCache[$sanitizedTarget];
        }

        $sede = Sede::query()
            ->get()
            ->first(fn (Sede $item) => $this->sanitize($item->name) === $sanitizedTarget);

        $this->sedeCache[$sanitizedTarget] = $sede;

        return $sede;
    }

    private function findDepartmentByName(int $sedeId, string $name): ?Department
    {
        $sanitizedTarget = $this->sanitize($name);

        return Department::query()
            ->where('sede_id', $sedeId)
            ->get()
            ->first(fn (Department $item) => $this->sanitize($item->name) === $sanitizedTarget);
    }

    private function findPositionByName(int $departmentId, string $name): ?Position
    {
        $sanitizedTarget = $this->sanitize($name);

        return Position::query()
            ->where('department_id', $departmentId)
            ->get()
            ->first(fn (Position $item) => $this->sanitize($item->name) === $sanitizedTarget);
    }

    private function findRazonSocialByName(string $name): ?RazonSocial
    {
        $sanitizedTarget = $this->sanitize($name);

        return RazonSocial::query()
            ->get()
            ->first(fn (RazonSocial $item) => $this->sanitize($item->name) === $sanitizedTarget);
    }

    private function findState(string $input): ?State
    {
        $inputUpper = mb_strtoupper(trim($input), 'UTF-8');
        $inputSanitized = $this->sanitize($inputUpper);

        if (isset($this->stateAliases[$inputSanitized])) {
            $aliasName = $this->stateAliases[$inputSanitized];
            $match = $this->states->first(fn (State $s) => $this->sanitize($s->name) === $this->sanitize($aliasName));
            if ($match !== null) {
                return $match;
            }
        }

        foreach ($this->states as $state) {
            if ($this->sanitize($state->name) === $inputSanitized) {
                return $state;
            }
        }

        foreach ($this->states as $state) {
            similar_text($inputSanitized, $this->sanitize($state->name), $percent);
            if ($percent > 85) {
                return $state;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, City>  $cities
     */
    private function findCity(string $input, Collection $cities): ?City
    {
        $inputSanitized = $this->sanitize($input);

        if (isset($this->cityAliases[$inputSanitized])) {
            $aliasName = $this->cityAliases[$inputSanitized];
            $match = $cities->first(fn (City $c) => $this->sanitize($c->name) === $this->sanitize($aliasName));
            if ($match !== null) {
                return $match;
            }
        }

        foreach ($cities as $city) {
            if ($this->sanitize($city->name) === $inputSanitized) {
                return $city;
            }
        }

        foreach ($cities as $city) {
            similar_text($inputSanitized, $this->sanitize($city->name), $percent);
            if ($percent > 90) {
                return $city;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function parseDate(mixed $raw, string $label, bool $required, array &$errors): ?string
    {
        if ($raw === null || trim((string) $raw) === '') {
            if ($required) {
                $errors[] = "El campo {$label} es obligatorio.";
            }

            return null;
        }

        if (is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d');
            } catch (\Throwable) {
                $errors[] = "{$label} inválida ({$raw}).";
                return null;
            }
        }

        $value = trim((string) $raw);
        foreach (['!Y-m-d', '!d/m/Y', '!d-m-Y', '!Y/m/d'] as $format) {
            $dt = \DateTimeImmutable::createFromFormat($format, $value);
            $lastErrors = \DateTimeImmutable::getLastErrors();
            $hasIssues = is_array($lastErrors) && ($lastErrors['warning_count'] > 0 || $lastErrors['error_count'] > 0);
            if ($dt !== false && ! $hasIssues) {
                return $dt->format('Y-m-d');
            }
        }

        $errors[] = "{$label} inválida ({$value}): use el formato AAAA-MM-DD o DD/MM/AAAA.";

        return null;
    }

    /**
     * @param  array<int, string>  $errors
     */
    private function normalizePhone(mixed $raw, string $label, array &$errors): ?string
    {
        $str = $this->cleanString($raw);
        if ($str === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $str);
        if (! preg_match('/^\d{10}$/', (string) $digits)) {
            $errors[] = "{$label} inválido ({$str}): debe contener exactamente 10 dígitos numéricos.";
            return null;
        }

        return (string) $digits;
    }

    /**
     * @param  array<string, string>  $map
     * @param  array<int, string>  $errors
     */
    private function mapCatalog(mixed $raw, array $map, string $label, array &$errors): ?string
    {
        $str = $this->cleanString($raw);
        if ($str === null) {
            return null;
        }

        $sanitized = $this->sanitize($str);
        if (isset($map[$sanitized])) {
            return $map[$sanitized];
        }

        $errors[] = "Valor no válido para {$label} ('{$str}').";

        return null;
    }

    private function parseBoolean(mixed $raw): bool
    {
        if (is_bool($raw)) {
            return $raw;
        }

        if (is_int($raw) || is_float($raw)) {
            return (int) $raw === 1;
        }

        $str = $this->cleanString($raw);
        if ($str === null) {
            return false;
        }

        return in_array($this->sanitize($str), ['SI', '1', 'TRUE', 'YES', 'S'], true);
    }

    private function isValidEmailOrInternalLogin(string $email): bool
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            return true;
        }

        // El sistema admite correos corporativos internos sin TLD (ej. nombre.apellido@optra).
        return preg_match('/^[^\s@]+@[^\s@]+$/', $email) === 1;
    }

    private function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_float($value) && floor($value) === $value) {
            $value = number_format($value, 0, '', '');
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function cleanUpperNoSpaces(mixed $value): ?string
    {
        $str = $this->cleanString($value);
        if ($str === null) {
            return null;
        }

        return mb_strtoupper((string) preg_replace('/\s+/', '', $str), 'UTF-8');
    }

    private function limitString(?string $value, int $maxLength): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_strlen($value) > $maxLength ? mb_substr($value, 0, $maxLength) : $value;
    }

    private function sanitize(string $value): string
    {
        $upper = mb_strtoupper(trim($value), 'UTF-8');
        $replaced = str_replace(
            ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'],
            ['A', 'E', 'I', 'O', 'U', 'U', 'N'],
            $upper
        );

        return (string) preg_replace('/\s+/', ' ', $replaced);
    }
}
