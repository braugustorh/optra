<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RazonSocial extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'rfc',
        'registro_patronal',
        'fiscal_address',
        'status'
    ];

    public function sedes()
    {
        return $this->belongsToMany(Sede::class, 'razon_social_sede');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function riskPremiums(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Compulsa\RiskPremium::class);
    }

    public function calculations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Compulsa\Calculation::class);
    }

    public function riskPremiumFor(int $ejercicio): ?\App\Models\Compulsa\RiskPremium
    {
        return $this->riskPremiums()
            ->where('ejercicio', $ejercicio)
            ->first();
    }
}
