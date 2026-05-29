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
}
