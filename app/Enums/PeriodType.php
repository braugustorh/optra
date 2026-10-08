<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PeriodType: string implements HasLabel, HasColor
{
    case Mensual = 'mensual';
    case Bimestral = 'bimestral';

    public function getLabel(): string
    {
        return match ($this) {
            self::Mensual => 'Mensual (EMA)',
            self::Bimestral => 'Bimestral (EBA)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Mensual => 'info',
            self::Bimestral => 'warning',
        };
    }

    /**
     * Rango válido de "mes_bimestre" según el tipo de período.
     * - Mensual: 1..12
     * - Bimestral: 1..6
     */
    public function periodRange(): array
    {
        return match ($this) {
            self::Mensual => range(1, 12),
            self::Bimestral => range(1, 6),
        };
    }
}
