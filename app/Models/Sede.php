<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'city',
        'state',
        'address', // 'address' is misspelled
        'cp',
        'status',
        'open_positions',
        'responsible',
        'card_id',
    ];

    public function user()
    {
        return $this->hasMany(User::class);
    }
    public function department()
    {
        return $this->hasMany(Department::class, 'sede_id');
    }
   /* public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'sedes_id');
    }*/
    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_sede');
    }

    public function razonSocials()
    {
        return $this->belongsToMany(RazonSocial::class, 'razon_social_sede');
    }

    public function count_positions($sede)
    {
        return User::where('sede_id', $sede)
            ->where('status', 1)
            ->whereHas('roles', function (Builder $query) {
                $query->whereNotIn('role_id', [1, 2]); // Filtra roles que no sean 1 o 2
            })
            ->count();
    }



}
