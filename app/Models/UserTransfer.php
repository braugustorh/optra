<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'user_id',
        'from_sede_id',
        'to_sede_id',
        'from_department_id',
        'to_department_id',
        'from_position_id',
        'to_position_id',
        'from_razon_social_id',
        'to_razon_social_id',
        'transfer_date',
        'transferred_by',
        'reason',
        'notes',
    ];

    protected $casts = [
        'transfer_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fromSede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'from_sede_id');
    }

    public function toSede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'to_sede_id');
    }

    public function fromDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'from_department_id');
    }

    public function toDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'to_department_id');
    }

    public function fromPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'from_position_id');
    }

    public function toPosition(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'to_position_id');
    }

    public function fromRazonSocial(): BelongsTo
    {
        return $this->belongsTo(RazonSocial::class, 'from_razon_social_id');
    }

    public function toRazonSocial(): BelongsTo
    {
        return $this->belongsTo(RazonSocial::class, 'to_razon_social_id');
    }

    public function transferredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }
}
