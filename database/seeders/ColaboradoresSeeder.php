<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ColaboradoresSeeder extends Seeder
{
    private const ColaboradorRoleId = 15;

    private const UserModelType = 'App\\Models\\User';

    private const ExpectedRecordCount = 757;

    /**
     * @var array<string, array<int, object>>
     */
    private array $lookupCache = [];

    /**
     * @var array<string, string>
     */
    private array $normalizedNameExpressions = [];

    public function run(): void
    {
        $this->validateSchema();
        $this->validateRole();

        $records = require __DIR__.'/data/colaboradores.php';
        $this->validateData($records);

        DB::transaction(function () use ($records): void {
            foreach ($records as $record) {
                $this->insertIfNew($record);
            }
        });
    }

    private function validateSchema(): void
    {
        $requiredColumns = [
            'users' => [
                'name', 'first_name', 'last_name', 'curp', 'sex', 'nationality',
                'birthdate', 'birth_country', 'birth_state', 'birth_city', 'disability',
                'email', 'alternate_email', 'phone', 'emergency_name', 'emergency_phone',
                'relationship_contact', 'address', 'colony', 'cp', 'state', 'city',
                'scholarship', 'career', 'employee_code', 'employee_number', 'sede_id',
                'department_id', 'position_id', 'razon_social_id', 'rfc', 'imss',
                'contract_type', 'entry_date', 'password', 'must_change_password',
                'marital_status', 'staff_type', 'work_shift', 'rotates_shifts',
                'time_in_position', 'experience_years', 'mi', 'status',
                'email_verified_at', 'created_at', 'updated_at',
            ],
            'sedes' => ['id', 'name'],
            'departments' => ['id', 'sede_id', 'name'],
            'positions' => ['id', 'department_id', 'name'],
            'razon_socials' => ['id', 'name'],
            'roles' => ['id'],
            'model_has_roles' => ['role_id', 'model_type', 'model_id'],
        ];

        foreach ($requiredColumns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Falta la tabla requerida [{$table}].");
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    throw new RuntimeException("Falta la columna requerida [{$table}.{$column}].");
                }
            }
        }
    }

    private function validateRole(): void
    {
        if (! DB::table('roles')->where('id', self::ColaboradorRoleId)->exists()) {
            throw new RuntimeException('No existe el rol Colaborador con id 15.');
        }
    }

    /**
     * @param array<int, array<string, mixed>> $records
     */
    private function validateData(array $records): void
    {
        if (count($records) !== self::ExpectedRecordCount) {
            throw new RuntimeException('El archivo auxiliar debe contener exactamente '.self::ExpectedRecordCount.' colaboradores.');
        }

        $seenCurps = [];
        $seenEmails = [];
        $seenEmployeeCodes = [];

        foreach ($records as $record) {
            $sourceRow = $record['source_row'] ?? 'desconocida';

            foreach (['curp', 'email', 'employee_code', 'sede_name', 'department_name', 'position_name', 'razon_social_name'] as $required) {
                if (($record[$required] ?? null) === null || trim((string) $record[$required]) === '') {
                    throw new RuntimeException("Dato requerido [{$required}] ausente en fila fuente {$sourceRow}.");
                }
            }

            $curp = (string) $record['curp'];
            $email = (string) $record['email'];
            $employeeCode = (string) $record['employee_code'];

            if (mb_strlen($curp) !== 18) {
                throw new RuntimeException("CURP con longitud distinta de 18 en fila fuente {$sourceRow}.");
            }

            $this->validateFinalColumnLengths($record, $sourceRow);

            foreach (['phone', 'emergency_phone'] as $phoneField) {
                $phone = $record[$phoneField] ?? null;
                if ($phone !== null && ! preg_match('/^\d{10}$/D', (string) $phone)) {
                    throw new RuntimeException("Teléfono [{$phoneField}] no normalizado a 10 dígitos en fila fuente {$sourceRow}.");
                }
            }

            if (! preg_match('/^\d{11}$/D', (string) $record['imss'])) {
                throw new RuntimeException("IMSS no normalizado a 11 dígitos en fila fuente {$sourceRow}.");
            }

            if (isset($seenCurps[$curp])) {
                throw new RuntimeException("CURP duplicada en datos auxiliares: {$curp}.");
            }

            if (isset($seenEmails[$email])) {
                throw new RuntimeException("Correo de acceso duplicado en datos auxiliares: {$email}.");
            }

            if (isset($seenEmployeeCodes[$employeeCode])) {
                throw new RuntimeException("employee_code duplicado en datos auxiliares: {$employeeCode}.");
            }

            $seenCurps[$curp] = true;
            $seenEmails[$email] = true;
            $seenEmployeeCodes[$employeeCode] = true;
        }
    }

    /**
     * Comprueba los límites explícitos de la migración sobre los valores finales.
     *
     * @param array<string, mixed> $record
     */
    private function validateFinalColumnLengths(array $record, int|string $sourceRow): void
    {
        $limits = [
            'name' => 100,
            'first_name' => 100,
            'last_name' => 100,
            'curp' => 18,
            'nationality' => 255,
            'birth_country' => 100,
            'birth_state' => 100,
            'birth_city' => 100,
            'disability' => 65,
            'email' => 80,
            'alternate_email' => 255,
            'phone' => 10,
            'emergency_name' => 100,
            'emergency_phone' => 100,
            'relationship_contact' => 100,
            'address' => 255,
            'colony' => 100,
            'state' => 100,
            'city' => 100,
            'scholarship' => 100,
            'career' => 100,
            'employee_code' => 25,
            'employee_number' => 10,
            'rfc' => 13,
            'imss' => 11,
        ];

        foreach ($limits as $field => $limit) {
            $value = $record[$field] ?? null;
            if ($value !== null && mb_strlen((string) $value) > $limit) {
                throw new RuntimeException("El valor final [{$field}] excede {$limit} caracteres en fila fuente {$sourceRow}.");
            }
        }
    }

    /**
     * @param array<string, mixed> $record
     */
    private function insertIfNew(array $record): void
    {
        $curp = (string) $record['curp'];

        if (DB::table('users')->where('curp', $curp)->exists()) {
            return;
        }

        $sedeId = $this->resolveUniqueId('sedes', ['name' => $record['sede_name']], $record);
        $departmentId = $this->resolveUniqueId('departments', [
            'sede_id' => $sedeId,
            'name' => $record['department_name'],
        ], $record);
        $positionId = $this->resolveUniqueId('positions', [
            'department_id' => $departmentId,
            'name' => $record['position_name'],
        ], $record);
        $razonSocialId = $this->resolveUniqueId('razon_socials', [
            'name' => $record['razon_social_name'],
        ], $record);
        $now = now();

        $userId = DB::table('users')->insertGetId([
            'name' => $record['name'],
            'first_name' => $record['first_name'],
            'last_name' => $record['last_name'],
            'curp' => $curp,
            'sex' => $record['sex'],
            'nationality' => $record['nationality'],
            'birthdate' => $record['birthdate'],
            'birth_country' => $record['birth_country'],
            'birth_state' => $record['birth_state'],
            'birth_city' => $record['birth_city'],
            'disability' => $record['disability'],
            'email' => $record['email'],
            'alternate_email' => $record['alternate_email'],
            'phone' => $record['phone'],
            'emergency_name' => $record['emergency_name'],
            'emergency_phone' => $record['emergency_phone'],
            'relationship_contact' => $record['relationship_contact'],
            'address' => $record['address'],
            'colony' => $record['colony'],
            'cp' => $record['cp'],
            'state' => $record['state'],
            'city' => $record['city'],
            'scholarship' => $record['scholarship'],
            'career' => $record['career'],
            'employee_code' => $record['employee_code'],
            'employee_number' => $record['employee_number'],
            'sede_id' => $sedeId,
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'razon_social_id' => $razonSocialId,
            'rfc' => $record['rfc'],
            'imss' => $record['imss'],
            'contract_type' => null,
            'entry_date' => $record['entry_date'],
            'password' => Hash::make("#Optra{$record['employee_code']}."),
            'must_change_password' => true,
            'marital_status' => null,
            'staff_type' => null,
            'work_shift' => null,
            'rotates_shifts' => false,
            'time_in_position' => null,
            'experience_years' => null,
            'mi' => false,
            'status' => (bool) $record['status'],
            'email_verified_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('model_has_roles')->insert([
            'role_id' => self::ColaboradorRoleId,
            'model_type' => self::UserModelType,
            'model_id' => $userId,
        ]);
    }

    /**
     * @param array<string, int|string> $constraints
     * @param array<string, mixed> $record
     */
    private function resolveUniqueId(string $table, array $constraints, array $record): int
    {
        $cacheKey = $table.'|'.json_encode($constraints, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        if (isset($this->lookupCache[$cacheKey])) {
            return (int) $this->lookupCache[$cacheKey][0]->id;
        }

        $query = DB::table($table);

        foreach ($constraints as $column => $value) {
            if (is_string($value)) {
                $expression = $this->normalizedNameExpression($column);
                $query->whereRaw("{$expression} = ?", [$this->normalizeLookupName($value)]);
                continue;
            }

            $query->where($column, $value);
        }

        $matches = $query->select('id')->limit(2)->get()->all();
        $sourceRow = $record['source_row'] ?? 'desconocida';

        if (count($matches) === 0) {
            throw new RuntimeException("No se pudo resolver [{$table}] para la fila fuente {$sourceRow}.");
        }

        if (count($matches) > 1) {
            throw new RuntimeException("La resolución de [{$table}] es ambigua para la fila fuente {$sourceRow}.");
        }

        $this->lookupCache[$cacheKey] = $matches;

        return (int) $matches[0]->id;
    }

    private function normalizedNameExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();
        $cacheKey = "{$driver}|{$column}";

        if (isset($this->normalizedNameExpressions[$cacheKey])) {
            return $this->normalizedNameExpressions[$cacheKey];
        }

        $wrappedColumn = DB::connection()->getQueryGrammar()->wrap($column);
        $expression = $driver === 'pgsql'
            ? "UPPER(BTRIM({$wrappedColumn}))"
            : "UPPER(TRIM({$wrappedColumn}))";
        $this->normalizedNameExpressions[$cacheKey] = $expression;

        return $expression;
    }

    private function normalizeLookupName(string $value): string
    {
        return mb_strtoupper(trim($value), 'UTF-8');
    }
}
