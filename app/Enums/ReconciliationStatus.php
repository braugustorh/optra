<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReconciliationStatus: string implements HasLabel, HasColor, HasIcon
{
    case Pendiente = 'pendiente';
    case Ok = 'ok';
    case DiferenciaCuota = 'diferencia_cuota';
    case DiferenciaCredito = 'diferencia_credito';
    case DiferenciaSdi = 'diferencia_sdi';
    case DiferenciaDias = 'diferencia_dias';
    case SuperaTope = 'supera_tope';
    case FaltaSua = 'falta_sua';
    case Faltante = 'faltante';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Ok => 'Correcto',
            self::DiferenciaCuota => 'Dif. cuota',
            self::DiferenciaCredito => 'Dif. crédito Infonavit',
            self::DiferenciaSdi => 'Dif. SDI',
            self::DiferenciaDias => 'Dif. días',
            self::SuperaTope => 'Supera 25 UMAs',
            self::FaltaSua => 'Falta SUA',
            self::Faltante => 'Falta IMSS / Nómina',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::Ok => 'success',
            self::DiferenciaCuota, self::DiferenciaCredito, self::DiferenciaSdi, self::DiferenciaDias, self::FaltaSua => 'warning',
            self::SuperaTope, self::Faltante => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pendiente => 'heroicon-o-clock',
            self::Ok => 'heroicon-o-check-circle',
            self::DiferenciaCuota => 'heroicon-o-currency-dollar',
            self::DiferenciaCredito => 'heroicon-o-home-modern',
            self::DiferenciaSdi => 'heroicon-o-scale',
            self::DiferenciaDias => 'heroicon-o-calendar',
            self::SuperaTope => 'heroicon-o-exclamation-triangle',
            self::FaltaSua => 'heroicon-o-question-mark-circle',
            self::Faltante => 'heroicon-o-x-circle',
        };
    }


    /**
     * Options for SelectFilter (excluye Pendiente porque debería ser transitorio).
     *
     * @return array<string, string>
     */
    public static function tableOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->getLabel()])
            ->all();
    }
}
