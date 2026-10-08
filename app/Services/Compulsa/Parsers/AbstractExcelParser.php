<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Parsers;

use App\Enums\FileSource;
use App\Services\Compulsa\Contracts\FileParserContract;
use App\Services\Compulsa\Dto\EmployeeRow;
use App\Services\Compulsa\Dto\ParseResult;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

abstract class AbstractExcelParser implements FileParserContract
{
    protected int $headerRow = 1;
    protected int $defaultChunkSize = 2000;

    /**
     * Aliases de columnas: canonical => list of accepted human labels (any locale/format).
     * El resolver normaliza (lowercase, sin acentos, sin caracteres especiales) antes de comparar.
     *
     * @return array<string, array<int, string>>
     */
    abstract protected function columnAliases(): array;

    /**
     * Canonical keys que DEBEN existir en el archivo para considerarlo válido.
     *
     * @return array<int, string>
     */
    protected function requiredColumns(): array
    {
        return ['nss', 'nombre', 'sdi'];
    }

    public function parse(string $absolutePath): ParseResult
    {
        $rows = collect();
        $meta = ['file' => basename($absolutePath), 'chunks' => 0];

        $this->parseInChunks($absolutePath, $this->defaultChunkSize, function (Collection $chunk) use ($rows, &$meta): void {
            $rows->push(...$chunk->all());
            $meta['chunks']++;
        });

        return new ParseResult(
            source: $this->source(),
            rows: $rows,
            warnings: [],
            meta: $meta,
        );
    }

    /**
     * Permite seleccionar el índice o nombre de la hoja a procesar.
     */
    protected function selectSheetIndex(Spreadsheet $spreadsheet): int|string
    {
        return 0;
    }

    public function parseInChunks(string $absolutePath, int $chunkSize, callable $onChunk): void
    {
        if (! is_file($absolutePath)) {
            throw new RuntimeException(sprintf('Archivo no encontrado: %s', $absolutePath));
        }

        $reader = IOFactory::createReaderForFile($absolutePath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($absolutePath);
        $sheetSelector = $this->selectSheetIndex($spreadsheet);
        $sheet = is_int($sheetSelector) ? $spreadsheet->getSheet($sheetSelector) : $spreadsheet->getSheetByName($sheetSelector);

        if (! $sheet) {
            $sheet = $spreadsheet->getActiveSheet();
        }

        $sheetName = $sheet->getTitle();
        $highestRow = $sheet->getHighestDataRow();
        $highestColumn = $sheet->getHighestDataColumn();

        // 1) Auto-detectar fila de encabezados en las primeras 25 filas
        $headerInfo = $this->findHeaderRow($sheet, $highestColumn, min(25, $highestRow));
        if ($headerInfo === null) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);
            throw new RuntimeException(sprintf(
                'No se localizó una fila de encabezados válida con columnas requeridas [%s] en el archivo de %s (Hoja: "%s").',
                implode(', ', $this->requiredColumns()),
                $this->source()->value,
                $sheetName,
            ));
        }

        $detectedHeaderRow = $headerInfo['row'];
        $columns = $headerInfo['columns'];

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $sheet);

        // 2) Leer datos en chunks desde detectedHeaderRow + 1
        $filter = new RowChunkReadFilter($detectedHeaderRow + 1, $detectedHeaderRow + 1);
        $reader->setReadFilter($filter);

        $startRow = $detectedHeaderRow + 1;
        while ($startRow <= $highestRow) {
            $endRow = min($startRow + $chunkSize - 1, $highestRow);
            $filter->setRange($startRow, $endRow);

            $spreadsheet = $reader->load($absolutePath);
            $sheet = is_int($sheetSelector) ? $spreadsheet->getSheet($sheetSelector) : $spreadsheet->getSheetByName($sheetSelector);
            if (! $sheet) {
                $sheet = $spreadsheet->getActiveSheet();
            }

            $data = $sheet->rangeToArray(
                'A' . $startRow . ':' . $highestColumn . $endRow,
                null,
                false,
                false,
            );
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet, $sheet);

            $data = array_values(array_filter($data, fn ($r) => $this->rowHasContent($r)));

            if ($data !== []) {
                $chunk = collect($data)
                    ->map(fn ($row) => $this->mapRow($row, $columns))
                    ->filter()
                    ->values();

                if ($chunk->isNotEmpty()) {
                    $onChunk($chunk);
                }
            }

            if ($endRow >= $highestRow) {
                break;
            }
            $startRow += $chunkSize;
        }
    }

    /**
     * Escanea las primeras N filas para encontrar la cabecera que satisfaga requiredColumns.
     *
     * @return array{row: int, headers: array<int, mixed>, columns: array<string, int|null>}|null
     */
    protected function findHeaderRow(Worksheet $sheet, string $highestColumn, int $maxSearchRows = 25): ?array
    {
        for ($r = 1; $r <= $maxSearchRows; $r++) {
            $rowValues = $sheet->rangeToArray("A{$r}:{$highestColumn}{$r}", null, false, false)[0] ?? [];
            if (! $this->rowHasContent($rowValues)) {
                continue;
            }

            try {
                $columns = $this->resolveColumns($rowValues);
                return [
                    'row' => $r,
                    'headers' => $rowValues,
                    'columns' => $columns,
                ];
            } catch (\Throwable $e) {
                // Sigue buscando en la siguiente fila
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int|null>  $columns
     */
    protected function mapRow(array $row, array $columns): ?EmployeeRow
    {
        $nssRaw = $columns['nss'] !== null ? ($row[$columns['nss']] ?? null) : null;
        $nss = $this->cleanNss($nssRaw);

        if ($nss === null) {
            return null;
        }

        return new EmployeeRow(
            source: $this->source(),
            nss: $nss,
            nombreCompleto: $this->cleanString($this->pick($row, $columns, 'nombre')),
            diasLaborados: $this->cleanInt($this->pick($row, $columns, 'dias')),
            sdi: $this->cleanFloat($this->pick($row, $columns, 'sdi')),
            rfc: $this->cleanString($this->pick($row, $columns, 'rfc')),
            curp: $this->cleanString($this->pick($row, $columns, 'curp')),
            raw: [],
        );
    }

    /**
     * @param  array<int, mixed>  $row
     * @param  array<string, int|null>  $columns
     */
    protected function pick(array $row, array $columns, string $key): mixed
    {
        $idx = $columns[$key] ?? null;
        if ($idx === null) {
            return null;
        }
        return $row[$idx] ?? null;
    }

    /**
     * @param  array<int, mixed>  $headers
     * @return array<string, int|null>
     */
    protected function resolveColumns(array $headers): array
    {
        $normalized = array_map(fn ($h) => $this->normalizeHeader((string) ($h ?? '')), $headers);

        $columns = [];
        foreach ($this->columnAliases() as $canonical => $aliases) {
            $columns[$canonical] = null;
            foreach ($aliases as $alias) {
                $needle = $this->normalizeHeader($alias);
                $idx = array_search($needle, $normalized, true);
                if ($idx !== false) {
                    $columns[$canonical] = $idx;
                    break;
                }
            }
        }

        foreach ($this->requiredColumns() as $canonical) {
            if (($columns[$canonical] ?? null) === null) {
                throw new RuntimeException(sprintf(
                    'No se localizó la columna requerida "%s" en el archivo de %s. Encabezados detectados: [%s]',
                    $canonical,
                    $this->source()->value,
                    implode(' | ', array_filter(array_map('strval', $headers))),
                ));
            }
        }

        return $columns;
    }

    protected function normalizeHeader(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'ñ' => 'n', 'ü' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
            'Ñ' => 'n', 'Ü' => 'u',
        ]);
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
        return trim(preg_replace('/\s+/', ' ', $value));
    }

    /**
     * @param  array<int, mixed>  $row
     */
    protected function rowHasContent(array $row): bool
    {
        foreach ($row as $v) {
            if ($v !== null && trim((string) $v) !== '') {
                return true;
            }
        }
        return false;
    }

    protected function cleanNss(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $value);
        if ($digits === null || $digits === '') {
            return null;
        }
        if (strlen($digits) === 10) {
            $digits = '0' . $digits;
        }
        if (strlen($digits) !== 11) {
            return null;
        }
        return $digits;
    }

    protected function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        return $s === '' ? null : $s;
    }

    protected function cleanInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $normalized = str_replace([',', ' '], ['', ''], (string) $value);
        if (! is_numeric($normalized)) {
            return null;
        }
        return (int) round((float) $normalized);
    }

    protected function cleanFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $normalized = str_replace([',', ' '], ['', ''], (string) $value);
        if (! is_numeric($normalized)) {
            return null;
        }
        return (float) $normalized;
    }
}
