<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskFactorSurvey extends Model
{
    protected $fillable = [
        'sede_id',
        'user_id',           // Colaborador que responde
        'norma_id',          // Proceso relacionado
        'question_id',       // ID de la pregunta respondida
        'response_value',    // Valor de respuesta en escala Likert (1-5)
        'equivalence_response', // Respuesta equivalente de acuerdo a la NOM035 Tabla 2 Valor de Opciones de Respuesta
        'status',            // Estado de la respuesta
    ];

    protected $casts = [
        'response_value' => 'string',
        'equivalence_response' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Relación con la sede
     */
    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación con el proceso
     */
    public function norma(): BelongsTo
    {
        return $this->belongsTo(Nom035Process::class, 'norma_id');
    }

    /**
     * Relación con la pregunta
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
