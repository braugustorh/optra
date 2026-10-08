<?php

declare(strict_types=1);

namespace App\Models\Compulsa;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GlobalSetting extends Model
{
    use HasFactory;

    protected $table = 'compulsa_global_settings';

    protected $fillable = [
        'ejercicio',
        // Valores base
        'salario_minimo',
        'valor_uma',
        'valor_umi',
        'tope_uma_veces',
        // Cuotas EMA
        'rate_cuota_fija_patronal',
        'rate_excedente_patronal',
        'rate_excedente_obrero',
        'rate_prest_dinero_patronal',
        'rate_prest_dinero_obrero',
        'rate_gmp_patronal',
        'rate_gmp_obrero',
        'rate_iv_patronal',
        'rate_iv_obrero',
        'rate_guarderias_patronal',
        // Cuotas EBA
        'rate_retiro_patronal',
        'rate_cesantia_patronal',
        'tabla_cesantia_patronal',
        'exencion_cuota_obrera_sm',
        'rate_cesantia_obrero',
        'rate_infonavit_patronal',
        // Otros
        'notas',
    ];

    protected $casts = [
        'ejercicio' => 'integer',
        'salario_minimo' => 'decimal:4',
        'valor_uma' => 'decimal:4',
        'valor_umi' => 'decimal:4',
        'tope_uma_veces' => 'decimal:2',
        'rate_cuota_fija_patronal' => 'decimal:4',
        'rate_excedente_patronal' => 'decimal:4',
        'rate_excedente_obrero' => 'decimal:4',
        'rate_prest_dinero_patronal' => 'decimal:4',
        'rate_prest_dinero_obrero' => 'decimal:4',
        'rate_gmp_patronal' => 'decimal:4',
        'rate_gmp_obrero' => 'decimal:4',
        'rate_iv_patronal' => 'decimal:4',
        'rate_iv_obrero' => 'decimal:4',
        'rate_guarderias_patronal' => 'decimal:4',
        'rate_retiro_patronal' => 'decimal:4',
        'rate_cesantia_patronal' => 'decimal:4',
        'tabla_cesantia_patronal' => 'array',
        'exencion_cuota_obrera_sm' => 'boolean',
        'rate_cesantia_obrero' => 'decimal:4',
        'rate_infonavit_patronal' => 'decimal:4',
    ];


    public static function forYear(int $ejercicio): ?self
    {
        return static::query()->where('ejercicio', $ejercicio)->first();
    }

    public function topeSdi(): float
    {
        return (float) $this->valor_uma * (float) $this->tope_uma_veces;
    }

    /**
     * Rates EMA (mensual) como array asociativo.
     * Cada valor es el porcentaje "humano" (ej. 20.400 = 20.4 %).
     *
     * @return array<string, float>
     */
    public function emaRates(): array
    {
        return [
            'cuota_fija_patronal' => (float) $this->rate_cuota_fija_patronal,
            'excedente_patronal' => (float) $this->rate_excedente_patronal,
            'excedente_obrero' => (float) $this->rate_excedente_obrero,
            'prest_dinero_patronal' => (float) $this->rate_prest_dinero_patronal,
            'prest_dinero_obrero' => (float) $this->rate_prest_dinero_obrero,
            'gmp_patronal' => (float) $this->rate_gmp_patronal,
            'gmp_obrero' => (float) $this->rate_gmp_obrero,
            'iv_patronal' => (float) $this->rate_iv_patronal,
            'iv_obrero' => (float) $this->rate_iv_obrero,
            'guarderias_patronal' => (float) $this->rate_guarderias_patronal,
        ];
    }

    /**
     * Rates EBA (bimestral).
     *
     * @return array<string, float>
     */
    public function ebaRates(): array
    {
        return [
            'retiro_patronal' => (float) $this->rate_retiro_patronal,
            'cesantia_patronal' => (float) $this->rate_cesantia_patronal,
            'cesantia_obrero' => (float) $this->rate_cesantia_obrero,
            'infonavit_patronal' => (float) $this->rate_infonavit_patronal,
        ];
    }

    /**
     * Retorna la tabla oficial del DOF para el ejercicio especificado.
     *
     * @return array<int, array{rango: string, limite_inferior_umas: float, limite_superior_umas: ?float, porcentaje_patronal: float, aplica_sm: bool}>
     */
    public static function defaultCesantiaTable(int $ejercicio): array
    {
        if ($ejercicio === 2025) {
            return [
                ['rango' => '1.00 Salario Mínimo', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.00, 'porcentaje_patronal' => 3.150, 'aplica_sm' => true],
                ['rango' => 'De 1.01 SM a 1.50 UMA', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.50, 'porcentaje_patronal' => 3.575, 'aplica_sm' => false],
                ['rango' => 'De 1.51 a 2.00 UMA', 'limite_inferior_umas' => 1.51, 'limite_superior_umas' => 2.00, 'porcentaje_patronal' => 4.512, 'aplica_sm' => false],
                ['rango' => 'De 2.01 a 2.50 UMA', 'limite_inferior_umas' => 2.01, 'limite_superior_umas' => 2.50, 'porcentaje_patronal' => 5.109, 'aplica_sm' => false],
                ['rango' => 'De 2.51 a 3.00 UMA', 'limite_inferior_umas' => 2.51, 'limite_superior_umas' => 3.00, 'porcentaje_patronal' => 5.506, 'aplica_sm' => false],
                ['rango' => 'De 3.01 a 3.50 UMA', 'limite_inferior_umas' => 3.01, 'limite_superior_umas' => 3.50, 'porcentaje_patronal' => 5.790, 'aplica_sm' => false],
                ['rango' => 'De 3.51 a 4.00 UMA', 'limite_inferior_umas' => 3.51, 'limite_superior_umas' => 4.00, 'porcentaje_patronal' => 6.004, 'aplica_sm' => false],
                ['rango' => 'De 4.01 UMA en adelante', 'limite_inferior_umas' => 4.01, 'limite_superior_umas' => null, 'porcentaje_patronal' => 6.421, 'aplica_sm' => false],
            ];
        }

        // Por defecto 2026 o posteriores (se puede editar en UI)
        return [
            ['rango' => '1.00 Salario Mínimo', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.00, 'porcentaje_patronal' => 3.150, 'aplica_sm' => true],
            ['rango' => 'De 1.01 SM a 1.50 UMA', 'limite_inferior_umas' => 1.00, 'limite_superior_umas' => 1.50, 'porcentaje_patronal' => 3.676, 'aplica_sm' => false],
            ['rango' => 'De 1.51 a 2.00 UMA', 'limite_inferior_umas' => 1.51, 'limite_superior_umas' => 2.00, 'porcentaje_patronal' => 4.851, 'aplica_sm' => false],
            ['rango' => 'De 2.01 a 2.50 UMA', 'limite_inferior_umas' => 2.01, 'limite_superior_umas' => 2.50, 'porcentaje_patronal' => 5.556, 'aplica_sm' => false],
            ['rango' => 'De 2.51 a 3.00 UMA', 'limite_inferior_umas' => 2.51, 'limite_superior_umas' => 3.00, 'porcentaje_patronal' => 6.026, 'aplica_sm' => false],
            ['rango' => 'De 3.01 a 3.50 UMA', 'limite_inferior_umas' => 3.01, 'limite_superior_umas' => 3.50, 'porcentaje_patronal' => 6.361, 'aplica_sm' => false],
            ['rango' => 'De 3.51 a 4.00 UMA', 'limite_inferior_umas' => 3.51, 'limite_superior_umas' => 4.00, 'porcentaje_patronal' => 6.613, 'aplica_sm' => false],
            ['rango' => 'De 4.01 UMA en adelante', 'limite_inferior_umas' => 4.01, 'limite_superior_umas' => null, 'porcentaje_patronal' => 7.513, 'aplica_sm' => false],
        ];
    }

    /**
     * Determina la tasa patronal de cesantía y vejez (como fracción, ej. 0.07513)
     * basándose en la tabla dinámica por UMA o la tasa estática de fallback.
     */
    public function resolveCesantiaPatronalRate(float $sdi, ?float $salarioMinimo = null, ?float $valorUma = null): float
    {
        $tabla = $this->tabla_cesantia_patronal;

        if (empty($tabla) || ! is_array($tabla)) {
            return ((float) $this->rate_cesantia_patronal) / 100.0;
        }

        $sm = $salarioMinimo ?? (float) $this->salario_minimo;
        $uma = $valorUma ?? (float) $this->valor_uma;

        // 1. Si gana salario mínimo (o menos)
        if ($sm > 0 && $sdi <= ($sm + 0.01)) {
            foreach ($tabla as $row) {
                if (! empty($row['aplica_sm'])) {
                    return ((float) $row['porcentaje_patronal']) / 100.0;
                }
            }
        }

        // 2. Por rangos de UMA
        if ($uma > 0) {
            $vecesUma = round($sdi / $uma, 4);

            foreach ($tabla as $row) {
                if (! empty($row['aplica_sm'])) {
                    continue; // Rango exclusivo para SM
                }

                $inf = isset($row['limite_inferior_umas']) ? (float) $row['limite_inferior_umas'] : 0.0;
                $sup = isset($row['limite_superior_umas']) && $row['limite_superior_umas'] !== null && $row['limite_superior_umas'] !== ''
                    ? (float) $row['limite_superior_umas']
                    : null;

                if ($sup === null) {
                    // Último rango: límite superior abierto (ej. 4.01 en adelante)
                    if ($vecesUma >= $inf) {
                        return ((float) $row['porcentaje_patronal']) / 100.0;
                    }
                } else {
                    if ($vecesUma >= $inf && $vecesUma <= ($sup + 0.0001)) {
                        return ((float) $row['porcentaje_patronal']) / 100.0;
                    }
                }
            }
        }

        // Fallback a rate fijo
        return ((float) $this->rate_cesantia_patronal) / 100.0;
    }
}

