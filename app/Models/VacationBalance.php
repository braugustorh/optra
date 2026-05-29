<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VacationBalance extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'total_days',
        'used_days',
        'pending_days',
        'available_days',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updateAvailableDays(): void
    {
        $this->available_days = $this->total_days - $this->used_days - $this->pending_days;
        $this->save();
    }
}
