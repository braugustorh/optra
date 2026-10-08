<?php

declare(strict_types=1);

namespace App\Models\Compulsa;

use App\Enums\ReconciliationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculationRecord extends Model
{
    use HasFactory;

    protected $table = 'compulsa_calculation_records';

    protected $fillable = [
        'calculation_id',
        'nss',
        'nombre_completo',
        'rfc',
        'curp',
        'dias_imss',
        'dias_sua',
        'dias_nomina',
        'sdi_imss',
        'sdi_sua',
        'sdi_nomina',
        'sdi_topado',
        'cuota_imss',
        'cuota_sua',
        'cuota_patron',
        'amortizacion_imss',
        'amortizacion_sua',
        'numero_credito',
        'diferencia_total',
        'estatus_conciliacion',
        'presente_imss',
        'presente_sua',
        'presente_nomina',
        'supera_tope_uma',
        'alertas_json',
        'desglose_cuotas',
        'desglose_sua',
        'observaciones',
    ];

    protected $casts = [
        'dias_imss' => 'integer',
        'dias_sua' => 'integer',
        'dias_nomina' => 'integer',
        'sdi_imss' => 'decimal:4',
        'sdi_sua' => 'decimal:4',
        'sdi_nomina' => 'decimal:4',
        'sdi_topado' => 'decimal:4',
        'cuota_imss' => 'decimal:2',
        'cuota_sua' => 'decimal:2',
        'cuota_patron' => 'decimal:2',
        'amortizacion_imss' => 'decimal:2',
        'amortizacion_sua' => 'decimal:2',
        'diferencia_total' => 'decimal:2',
        'presente_imss' => 'boolean',
        'presente_sua' => 'boolean',
        'presente_nomina' => 'boolean',
        'supera_tope_uma' => 'boolean',
        'alertas_json' => 'array',
        'desglose_cuotas' => 'array',
        'desglose_sua' => 'array',
        'estatus_conciliacion' => ReconciliationStatus::class,
    ];


    public function calculation(): BelongsTo
    {
        return $this->belongsTo(Calculation::class);
    }

    public function hasDifference(): bool
    {
        return (float) $this->diferencia_total !== 0.0
            || $this->dias_imss !== $this->dias_nomina
            || (float) $this->sdi_imss !== (float) $this->sdi_nomina;
    }
}
