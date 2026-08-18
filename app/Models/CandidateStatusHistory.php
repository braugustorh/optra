<?php

namespace App\Models;

use App\Models\Candidate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateStatusHistory extends Model
{
    protected $table = 'candidate_status_history';

    protected $fillable = [
        'candidate_id',
        'from_status',
        'to_status',
        'changed_by',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function fromStatusLabel(): string
    {
        return Candidate::STATUS_LABELS[$this->from_status] ?? ($this->from_status ?? '—');
    }

    public function toStatusLabel(): string
    {
        return Candidate::STATUS_LABELS[$this->to_status] ?? $this->to_status;
    }
}
