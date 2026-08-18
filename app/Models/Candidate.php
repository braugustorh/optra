<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'position_applied',
        'notes',
        'status',
    ];

    /**
     * Estatus del pipeline de selección (Gestión de Candidatos).
     */
    public const STATUS_EN_PROCESO = 'en_proceso';
    public const STATUS_CONTRATADO = 'contratado';
    public const STATUS_BANCO_TALENTO = 'banco_talento';
    public const STATUS_ARCHIVADO = 'archivado';

    public const STATUS_LABELS = [
        self::STATUS_EN_PROCESO    => 'En Proceso',
        self::STATUS_CONTRATADO    => 'Contratado',
        self::STATUS_BANCO_TALENTO => 'Banco de Talento',
        self::STATUS_ARCHIVADO     => 'Archivado',
    ];

    public const STATUS_COLORS = [
        self::STATUS_EN_PROCESO    => 'info',
        self::STATUS_CONTRATADO    => 'success',
        self::STATUS_BANCO_TALENTO => 'warning',
        self::STATUS_ARCHIVADO     => 'gray',
    ];

    public function psychometricEvaluations(): MorphMany
    {
        return $this->morphMany(PsychometricEvaluation::class, 'evaluable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(CandidateComment::class)->latest();
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(CandidateStatusHistory::class)->latest();
    }

    public function reportSnapshots(): HasMany
    {
        return $this->hasMany(CandidateReportSnapshot::class)->latest();
    }

    /**
     * Puesto "vigente" del candidato: usa el campo manual si está declarado,
     * y si no, cae al puesto de su batería de evaluaciones más reciente.
     * Evita que la ficha se vea vacía cuando el candidato se creó desde el
     * flujo rápido del Dashboard (que no captura position_applied).
     */
    public function effectivePosition(): ?string
    {
        return $this->position_applied
            ?? $this->psychometricEvaluations()->latest('assigned_at')->value('puesto');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function statusColor(): string
    {
        return self::STATUS_COLORS[$this->status] ?? 'gray';
    }

    /**
     * Cambia el estatus del candidato dejando registro en la bitácora.
     */
    public function changeStatus(string $newStatus, ?int $userId = null): void
    {
        $oldStatus = $this->status;

        if ($oldStatus === $newStatus) {
            return;
        }

        $this->update(['status' => $newStatus]);

        $this->statusHistory()->create([
            'from_status' => $oldStatus,
            'to_status'   => $newStatus,
            'changed_by'  => $userId ?? auth()->id(),
        ]);
    }
}
