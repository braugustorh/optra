<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder; // Importante agregar esto


class PsychometricEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluations_type_id',
        'evaluable_id',
        'evaluable_type',
        'batch_id',      // Para las evaluaciones
        'access_token',  // Para la seguridad y el uso de la aplicación
        'assigned_by',
        'status',
        'progress',
        'assigned_at',
        'started_at',
        'completed_at',
        'expires_at',
        'instructions',
        'manual_notes',
        'response_summary',
        'result_document_url',
        'interpretation_document_url',
        'elapsed_seconds',
        'puesto',
        'current_series_id',
        'current_series_started_at',
        'is_invalidated',
        'invalidated_reason'
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
        'response_summary' => 'array',
        'current_series_started_at' => 'datetime',
        'is_invalidated' => 'boolean',
    ];

    // Relaciones con estructura existente
    public function evaluationType(): BelongsTo
    {
        return $this->belongsTo(EvaluationsTypes::class, 'evaluations_type_id');
    }

    public function evaluable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // Relación con respuestas existentes usando el sistema actual
    public function responses(): HasMany
    {
        return $this->hasMany(Response::class, 'psychometric_evaluation_id');
    }

    // Obtener preguntas a través del tipo de evaluación
    public function questions()
    {
        return Question::where('evaluations_type_id', $this->evaluations_type_id)
            ->with('competence', 'answers')
            ->orderBy('order')
            ->get();
    }

    // Obtener competencias de esta evaluación
    public function competences()
    {
        return Competence::where('evaluations_type_id', $this->evaluations_type_id)->get();
    }

    // Métodos de ayuda
    public function isForEmployee(): bool
    {
        return $this->evaluable_type === User::class;
    }

    public function isForCandidate(): bool
    {
        return $this->evaluable_type === Candidate::class;
    }

    public function getEvaluatedName(): string
    {
        return $this->evaluable->name ?? 'N/A';
    }

    public function getEvaluatedEmail(): string
    {
        return $this->evaluable->email ?? 'N/A';
    }

    public function getStatusBadgeColor(): string
    {
        return match($this->status) {
            'assigned' => 'gray',
            'started', 'in_progress' => 'info',
            'completed' => 'success',
            'expired' => 'danger',
            default => 'gray'
        };
    }
    // --- NUEVAS FUNCIONES DE LÓGICA DE NEGOCIO ---

    /**
     * Suma los elapsed_seconds de todas las evaluaciones completadas del mismo token/batch.
     * Útil para inicializar el timer acumulado al comenzar una nueva prueba.
     */
    public function getAccumulatedSecondsByToken(): int
    {
        return (int) self::query()
            ->where('access_token', $this->access_token)
            ->where('status', 'completed')
            ->sum('elapsed_seconds');
    }

    /**
     * Busca la siguiente evaluación pendiente para un token dado.
     * Esta es la función cerebro del "Loop" de exámenes.
     */
    public static function getNextPendingByToken(string $token): ?self
    {
        return self::query()
            ->where('access_token', $token)
            ->where('status', '!=', 'completed') // Busca lo que no esté terminado
            ->orderBy('id', 'asc') // Mantiene el orden en que fueron asignadas
            ->with('evaluationType') // Eager loading para optimizar
            ->first();
    }

    /**
     * Cuenta cuántas evaluaciones faltan en este batch.
     * Útil para mostrar "Te faltan 2 de 5 exámenes".
     */
    public static function countPendingByToken(string $token): int
    {
        return self::query()
            ->where('access_token', $token)
            ->where('status', '!=', 'completed')
            ->count();
    }

    /**
     * Scope para facilitar búsquedas por token
     */
    public function scopeByToken(Builder $query, string $token): void
    {
        $query->where('access_token', $token);
    }
    // Calcular progreso basado en respuestas
    public function calculateProgress(): int
    {
        $totalQuestions = Question::where('evaluations_type_id', $this->evaluations_type_id)->count();
        $answeredQuestions = $this->responses()->count();

        if ($totalQuestions === 0) return 0;

        return round(($answeredQuestions / $totalQuestions) * 100);
    }

    // Actualizar progreso
    public function updateProgress(): void
    {
        $progress = $this->calculateProgress();
        $this->update(['progress' => $progress]);

        if ($progress === 100 && $this->status !== 'completed') {
            $this->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
        }
    }

    // Generar resumen de respuestas por competencia
    public function generateResponseSummary(): array
    {
        $summary = [];

        foreach ($this->competences() as $competence) {
            $competenceResponses = $this->responses()
                ->whereHas('question', function($query) use ($competence) {
                    $query->where('competence_id', $competence->id);
                })
                ->with('question.answers')
                ->get();

            $scores = $competenceResponses->pluck('response_value')->filter();

            $summary[$competence->name] = [
                'total_questions' => $competence->questions()->count(),
                'answered_questions' => $competenceResponses->count(),
                'average_score' => $scores->count() > 0 ? round($scores->avg(), 2) : 0,
                'max_score' => $scores->max() ?? 0,
                'min_score' => $scores->min() ?? 0,
            ];
        }

        $this->update(['response_summary' => $summary]);
        return $summary;
    }

}
