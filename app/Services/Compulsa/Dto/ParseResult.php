<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Dto;

use App\Enums\FileSource;
use Illuminate\Support\Collection;

final class ParseResult
{
    /**
     * @param  Collection<int, EmployeeRow>  $rows
     * @param  array<int, string>  $warnings
     */
    public function __construct(
        public readonly FileSource $source,
        public readonly Collection $rows,
        public readonly array $warnings = [],
        public readonly array $meta = [],
    ) {
    }

    public function count(): int
    {
        return $this->rows->count();
    }

    public function isEmpty(): bool
    {
        return $this->rows->isEmpty();
    }
}
