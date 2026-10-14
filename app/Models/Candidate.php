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
        'user_id',
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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function psychometricEvaluations(): MorphMany
    {
        return $this->morphMany(PsychometricEvaluation::class, 'evaluable');
    }

    public function originatedEvaluations(): HasMany
    {
        return $this->hasMany(PsychometricEvaluation::class, 'candidate_origin_id');
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
            ?? PsychometricEvaluation::where(function ($q) {
                $q->where('candidate_origin_id', $this->id)
                  ->orWhere(function ($sub) {
                      $sub->where('evaluable_type', self::class)
                          ->where('evaluable_id', $this->id);
                  });
            })->latest('assigned_at')->value('puesto');
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

    /**
     * Procesa la contratación del candidato con opción de vincular a un usuario
     * y transferir sus evaluaciones psicométricas al expediente del colaborador.
     */
    public function matchAndContract(string $newStatus, array $options = [], ?int $changedBy = null): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($newStatus, $options, $changedBy) {
            $userId = $this->user_id;

            if ($newStatus === self::STATUS_CONTRATADO) {
                $mode = $options['colaborador_mode'] ?? 'none';

                if ($mode === 'existing' && !empty($options['user_id'])) {
                    $userId = (int) $options['user_id'];
                } elseif ($mode === 'new') {
                    if (!$userId) {
                        $existingUser = User::where('email', $this->email)->first();
                        if ($existingUser) {
                            $userId = $existingUser->id;
                        } else {
                            $parts = preg_split('/\s+/', trim($this->name));
                            $count = count($parts);
                            if ($count === 1) {
                                $name = $parts[0];
                                $firstName = null;
                                $lastName = null;
                            } elseif ($count === 2) {
                                $name = $parts[0];
                                $firstName = $parts[1];
                                $lastName = null;
                            } elseif ($count === 3) {
                                $name = $parts[0];
                                $firstName = $parts[1];
                                $lastName = $parts[2];
                            } else {
                                $lastName = array_pop($parts);
                                $firstName = array_pop($parts);
                                $name = implode(' ', $parts);
                            }

                            $user = User::create([
                                'name'       => $name,
                                'first_name' => $firstName,
                                'last_name'  => $lastName,
                                'email'      => $this->email,
                                'phone'      => $this->phone,
                                'password'   => bcrypt(\Illuminate\Support\Str::random(16)),
                            ]);
                            if (\Spatie\Permission\Models\Role::where('name', 'Colaborador')->exists()) {
                                $user->assignRole('Colaborador');
                            }
                            $userId = $user->id;
                        }
                    }
                }

                $this->user_id = $userId;
                $this->save();

                // Traspaso de psicometría si se solicita
                if (!empty($options['transfer_psychometrics']) && $userId) {
                    $evaluations = PsychometricEvaluation::where(function ($q) {
                        $q->where('evaluable_type', self::class)
                          ->where('evaluable_id', $this->id);
                    })->get();

                    foreach ($evaluations as $eval) {
                        $eval->update([
                            'candidate_origin_id' => $this->id,
                            'evaluable_type'      => User::class,
                            'evaluable_id'        => $userId,
                        ]);
                    }
                }
            }

            $this->changeStatus($newStatus, $changedBy);
        });
    }
}
