<?php

declare(strict_types=1);

namespace App\Exports\Compulsa;

use App\Exports\Compulsa\Sheets\AlertsSheet;
use App\Exports\Compulsa\Sheets\BreakdownSheet;
use App\Exports\Compulsa\Sheets\DetailSheet;
use App\Exports\Compulsa\Sheets\OverviewSheet;
use App\Models\Compulsa\Calculation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte completo: 4 hojas — Resumen · Detalle · Alertas · Desglose por rama.
 * El detalle, alertas y desglose respetan la query filtrada de la tabla.
 */
class CompulsaFullExport implements WithMultipleSheets
{
    public function __construct(
        private readonly Builder $filteredQuery,
        private readonly Calculation $calculation,
    ) {
    }

    public function sheets(): array
    {
        return [
            new OverviewSheet($this->calculation),
            new DetailSheet($this->filteredQuery, $this->calculation),
            new AlertsSheet($this->filteredQuery, $this->calculation),
            new BreakdownSheet($this->filteredQuery, $this->calculation),
        ];
    }
}
