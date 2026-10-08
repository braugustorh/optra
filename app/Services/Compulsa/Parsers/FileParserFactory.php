<?php

declare(strict_types=1);

namespace App\Services\Compulsa\Parsers;

use App\Enums\FileSource;
use App\Enums\PeriodType;
use App\Services\Compulsa\Contracts\FileParserContract;

class FileParserFactory
{
    public function make(FileSource $source, ?PeriodType $periodType = null): FileParserContract
    {
        return match ($source) {
            FileSource::Imss => (new ImssFileParser())->setPeriodType($periodType),
            FileSource::Sua => new SuaFileParser(),
            FileSource::Nomina => new NominaFileParser(),
        };
    }
}
