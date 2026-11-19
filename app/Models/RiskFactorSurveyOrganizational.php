<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskFactorSurveyOrganizational extends Model
{
    protected $table = 'risk_factor_survey_organizationals';

    protected $fillable = [
        'sede_id',
        'user_id',
        'norma_id',
        'question_id',
        'response_value',
        'equivalence_response',
        'status',
    ];

    protected $casts = [
        'response_value' => 'string',
        'equivalence_response' => 'integer',
        'status' => 'boolean',
    ];
    /**
     * Relación con Sede
     */
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
    /**
     * Relación con User (Colaborador)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    /**
     * Relación con Nom035Process (Proceso NOM-035)
     */
    public function norma()
    {
        return $this->belongsTo(Nom035Process::class, 'norma_id');
    }
    /**
     * Relación con Question (Pregunta)
     */
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
