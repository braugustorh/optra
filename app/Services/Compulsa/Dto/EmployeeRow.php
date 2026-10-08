<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Dto;

use App\Enums\FileSource;

final class EmployeeRow
{
    public function __construct(
        public readonly FileSource $source,
        public readonly string $nss,
        public readonly ?string $nombreCompleto = null,
        public readonly ?int $diasLaborados = null,
        public readonly ?float $sdi = null,
        public readonly ?string $rfc = null,
        public readonly ?string $curp = null,
        public readonly array $raw = [],
        public readonly ?float $cuotaTotal = null,
        public readonly array $cuotasDetalle = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'source' => $this->source->value,
            'nss' => $this->nss,
            'nombre_completo' => $this->nombreCompleto,
            'dias_laborados' => $this->diasLaborados,
            'sdi' => $this->sdi,
            'rfc' => $this->rfc,
            'curp' => $this->curp,
            'cuota_total' => $this->cuotaTotal,
            'cuotas_detalle' => $this->cuotasDetalle,
        ];
    }
}
