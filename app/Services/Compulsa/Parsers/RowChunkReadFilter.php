<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Parsers;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Filter que restringe la carga de PhpSpreadsheet a un rango de filas.
 * Se usa para leer archivos grandes en chunks sin cargar todo el workbook.
 */
final class RowChunkReadFilter implements IReadFilter
{
    public function __construct(
        private int $startRow,
        private int $endRow,
    ) {
    }

    public function setRange(int $startRow, int $endRow): void
    {
        $this->startRow = $startRow;
        $this->endRow = $endRow;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        return $row >= $this->startRow && $row <= $this->endRow;
    }
}
