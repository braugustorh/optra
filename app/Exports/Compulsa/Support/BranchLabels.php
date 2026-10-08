<?php

declare(strict_types=1);

namespace App\Exports\Compulsa\Support;

/**
 * Labels legibles para las ramas de cuota (EMA + EBA).
 * Se usa en las hojas de exportación y en la vista de detalle.
 */
final class BranchLabels
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return [
            // EMA
            'cuota_fija' => 'Cuota Fija',
            'excedente_patronal' => 'Excedente (patronal)',
            'excedente_obrero' => 'Excedente (obrero)',
            'prest_dinero_patronal' => 'Prest. en dinero (patronal)',
            'prest_dinero_obrero' => 'Prest. en dinero (obrero)',
            'gmp_patronal' => 'Gastos Méd. Pens. (patronal)',
            'gmp_obrero' => 'Gastos Méd. Pens. (obrero)',
            'riesgos_trabajo' => 'Riesgos de Trabajo',
            'iv_patronal' => 'Invalidez y Vida (patronal)',
            'iv_obrero' => 'Invalidez y Vida (obrero)',
            'guarderias_patronal' => 'Guarderías',
            // EBA
            'retiro' => 'Retiro',
            'cesantia_patronal' => 'Cesantía y Vejez (patronal)',
            'cesantia_obrero' => 'Cesantía y Vejez (obrero)',
            'infonavit' => 'INFONAVIT',
        ];
    }

    public static function for(string $key): string
    {
        return self::all()[$key] ?? ucwords(str_replace('_', ' ', $key));
    }
}
