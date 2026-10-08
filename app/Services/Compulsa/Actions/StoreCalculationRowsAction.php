<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Actions;

use App\Enums\FileSource;
use App\Models\Compulsa\Calculation;
use App\Models\Compulsa\CalculationRecord;
use App\Services\Compulsa\Dto\EmployeeRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Persiste un chunk de filas parseadas dentro de compulsa_calculation_records
 * usando upsert por (calculation_id, nss). Cada fuente actualiza sus columnas
 * específicas (dias_imss/sdi_imss, dias_sua/sdi_sua, dias_nomina/sdi_nomina)
 * sin sobrescribir las de las demás fuentes.
 */
class StoreCalculationRowsAction
{
    /**
     * @param  Collection<int, EmployeeRow>  $rows
     * @return int  Cantidad de filas afectadas.
     */
    public function execute(Calculation $calculation, Collection $rows): int
    {
        if ($rows->isEmpty()) {
            return 0;
        }

        /** @var FileSource $source */
        $source = $rows->first()->source;
        $now = now();

        $payload = $rows->map(function (EmployeeRow $row) use ($calculation, $source, $now): array {
            return array_merge(
                [
                    'calculation_id' => $calculation->id,
                    'nss' => $row->nss,
                    'nombre_completo' => $row->nombreCompleto ?? 'Sin nombre',
                    'rfc' => $row->rfc,
                    'curp' => $row->curp,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $this->sourceColumns($row, $source),
            );
        })->all();

        return DB::transaction(function () use ($payload, $source) {
            return CalculationRecord::upsert(
                $payload,
                ['calculation_id', 'nss'],
                $this->updatableColumns($source),
            );
        });
    }

    /**
     * Columnas específicas a insertar/actualizar por fuente.
     *
     * @return array<string, mixed>
     */
    private function sourceColumns(EmployeeRow $row, FileSource $source): array
    {
        return match ($source) {
            FileSource::Imss => [
                'dias_imss' => $row->diasLaborados,
                'sdi_imss' => $row->sdi,
                'cuota_imss' => $row->cuotaTotal ?? 0.0,
                'desglose_cuotas' => ! empty($row->cuotasDetalle) ? json_encode(['imss' => $row->cuotasDetalle]) : null,
                'amortizacion_imss' => (float) ($row->cuotasDetalle['desglose']['amortizacion'] ?? 0.0),
                'numero_credito' => $row->cuotasDetalle['desglose']['numero_credito'] ?? null,
                'presente_imss' => true,
            ],
            FileSource::Sua => [
                'dias_sua' => $row->diasLaborados,
                'sdi_sua' => $row->sdi,
                'cuota_sua' => $row->cuotaTotal ?? 0.0,
                'desglose_sua' => ! empty($row->cuotasDetalle) ? json_encode($row->cuotasDetalle) : null,
                'amortizacion_sua' => (float) ($row->cuotasDetalle['amortizacion'] ?? ($row->cuotasDetalle['desglose']['amortizacion'] ?? 0.0)),
                'numero_credito' => $row->cuotasDetalle['numero_credito'] ?? ($row->cuotasDetalle['desglose']['numero_credito'] ?? null),
                'presente_sua' => true,
            ],
            FileSource::Nomina => [
                'dias_nomina' => $row->diasLaborados,
                'sdi_nomina' => $row->sdi,
                'presente_nomina' => true,
            ],
        };
    }

    /**
     * Columnas que el upsert puede actualizar cuando la fila ya existe.
     * NO se listan las de otras fuentes para evitar sobrescribirlas con NULL.
     *
     * @return array<int, string>
     */
    private function updatableColumns(FileSource $source): array
    {
        $common = ['nombre_completo', 'rfc', 'curp', 'updated_at'];

        return match ($source) {
            FileSource::Imss => [...$common, 'dias_imss', 'sdi_imss', 'cuota_imss', 'desglose_cuotas', 'amortizacion_imss', 'numero_credito', 'presente_imss'],
            FileSource::Sua => [...$common, 'dias_sua', 'sdi_sua', 'cuota_sua', 'desglose_sua', 'amortizacion_sua', 'numero_credito', 'presente_sua'],
            FileSource::Nomina => [...$common, 'dias_nomina', 'sdi_nomina', 'presente_nomina'],
        };
    }
}
