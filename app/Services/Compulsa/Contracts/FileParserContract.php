<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Contracts;

use App\Enums\FileSource;
use App\Services\Compulsa\Dto\ParseResult;
use Illuminate\Support\Collection;

interface FileParserContract
{
    public function source(): FileSource;

    /**
     * Parse a file synchronously. Suitable for small files.
     * Uses parseInChunks internally.
     */
    public function parse(string $absolutePath): ParseResult;

    /**
     * Stream a file in chunks; invokes $onChunk with a Collection<int, EmployeeRow>
     * for each chunk. Suitable for large files to keep memory bounded.
     */
    public function parseInChunks(string $absolutePath, int $chunkSize, callable $onChunk): void;
}
