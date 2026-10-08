<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum FileSource: string implements HasLabel, HasColor, HasIcon
{
    case Imss = 'imss';
    case Sua = 'sua';
    case Nomina = 'nomina';

    public function getLabel(): string
    {
        return match ($this) {
            self::Imss => 'IMSS (EMA / EBA)',
            self::Sua => 'SUA',
            self::Nomina => 'Nómina interna',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Imss => 'primary',
            self::Sua => 'warning',
            self::Nomina => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Imss => 'heroicon-o-building-library',
            self::Sua => 'heroicon-o-cpu-chip',
            self::Nomina => 'heroicon-o-briefcase',
        };
    }
}
