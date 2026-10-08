<?php

declare(strict_types=1);

namespace App\Models\Compulsa;

use App\Models\RazonSocial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskPremium extends Model
{
    use HasFactory;

    protected $table = 'compulsa_risk_premiums';

    protected $fillable = [
        'razon_social_id',
        'ejercicio',
        'prima_riesgo',
        'vigencia_desde',
        'vigencia_hasta',
    ];

    protected $casts = [
        'ejercicio' => 'integer',
        'prima_riesgo' => 'decimal:7',
        'vigencia_desde' => 'date',
        'vigencia_hasta' => 'date',
    ];

    public function razonSocial(): BelongsTo
    {
        return $this->belongsTo(RazonSocial::class);
    }
}
