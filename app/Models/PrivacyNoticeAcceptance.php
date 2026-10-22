<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrivacyNoticeAcceptance extends Model
{
    use HasFactory;

    protected $fillable = [
        'privacy_notice_id',
        'user_id',
        'razon_social_id',
        'sede_id',
        'resolved_company_name',
        'resolved_address',
        'ip_address',
        'user_agent',
        'accepted_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
    ];

    public function privacyNotice(): BelongsTo
    {
        return $this->belongsTo(PrivacyNotice::class, 'privacy_notice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function razonSocial(): BelongsTo
    {
        return $this->belongsTo(RazonSocial::class, 'razon_social_id');
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class, 'sede_id');
    }
}
