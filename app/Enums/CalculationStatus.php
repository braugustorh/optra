<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CalculationStatus: string implements HasLabel, HasColor, HasIcon
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Archived = 'archived';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Processing => 'Procesando',
            self::Completed => 'Completado',
            self::Failed => 'Con errores',
            self::Archived => 'Archivado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
            self::Archived => 'warning',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-pencil-square',
            self::Processing => 'heroicon-o-arrow-path',
            self::Completed => 'heroicon-o-check-circle',
            self::Failed => 'heroicon-o-exclamation-triangle',
            self::Archived => 'heroicon-o-archive-box',
        };
    }
}
