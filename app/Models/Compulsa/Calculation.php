<?php

declare(strict_types=1);

namespace App\Models\Compulsa;

use App\Enums\CalculationStatus;
use App\Enums\PeriodType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calculation extends Model
{
    use HasFactory;

    protected $table = 'compulsa_calculations';

    protected $fillable = [
        'razon_social_id',
        'user_id',
        'tipo_periodo',
        'mes_bimestre',
        'ejercicio',
        'estado',
        'prima_riesgo_aplicada',
        'valor_uma_aplicado',
        'salario_minimo_aplicado',
        'tope_uma_veces_aplicado',
        'total_empleados',
        'empleados_con_diferencias',
        'total_cuota_imss',
        'total_cuota_patron',
        'total_diferencia',
        'archivo_imss_path',
        'archivo_sua_path',
        'archivo_nomina_path',
        'meta',
        'procesado_en',
    ];

    protected $casts = [
        'tipo_periodo' => PeriodType::class,
        'estado' => CalculationStatus::class,
        'mes_bimestre' => 'integer',
        'ejercicio' => 'integer',
        'prima_riesgo_aplicada' => 'decimal:7',
        'valor_uma_aplicado' => 'decimal:4',
        'salario_minimo_aplicado' => 'decimal:4',
        'tope_uma_veces_aplicado' => 'decimal:2',
        'total_empleados' => 'integer',
        'empleados_con_diferencias' => 'integer',
        'total_cuota_imss' => 'decimal:2',
        'total_cuota_patron' => 'decimal:2',
        'total_diferencia' => 'decimal:2',
        'meta' => 'array',
        'procesado_en' => 'datetime',
    ];

    public function razonSocial(): BelongsTo
    {
        return $this->belongsTo(\App\Models\RazonSocial::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(CalculationRecord::class);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('estado', CalculationStatus::Completed->value);
    }

    public function scopeForPeriod(Builder $query, PeriodType $tipo, int $ejercicio, int $mesBimestre): Builder
    {
        return $query
            ->where('tipo_periodo', $tipo->value)
            ->where('ejercicio', $ejercicio)
            ->where('mes_bimestre', $mesBimestre);
    }

    public function periodLabel(): string
    {
        $tipo = $this->tipo_periodo instanceof PeriodType
            ? $this->tipo_periodo
            : PeriodType::from((string) $this->tipo_periodo);

        return $tipo === PeriodType::Mensual
            ? sprintf('Mes %02d / %d', $this->mes_bimestre, $this->ejercicio)
            : sprintf('Bimestre %d / %d', $this->mes_bimestre, $this->ejercicio);
    }
}
