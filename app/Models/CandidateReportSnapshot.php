<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateReportSnapshot extends Model
{
    protected $fillable = [
        'candidate_id',
        'batch_id',
        'puesto_original',
        'puesto_evaluado',
        'ajuste_global',
        'ajuste_relativo',
        'dictamen',
        'competencias_json',
        'competencias_ideal_json',
        'ai_report_json',
        'cleaver_ideal_json',
        'generated_by',
    ];

    protected $casts = [
        'competencias_json'       => 'array',
        'competencias_ideal_json' => 'array',
        'ai_report_json'          => 'array',
        'cleaver_ideal_json'      => 'array',
        'ajuste_global'           => 'float',
        'ajuste_relativo'         => 'float',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Indica si este snapshot usó un puesto distinto al originalmente asignado
     * en la batería de evaluaciones (reevaluación hipotética).
     */
    public function isOverride(): bool
    {
        return $this->puesto_original && $this->puesto_original !== $this->puesto_evaluado;
    }
}
