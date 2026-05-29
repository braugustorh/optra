<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competence extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'evaluations_type_id',
        'status',
        'level',
        'time_limit_minutes',
        'instructions'
    ];
    public function evaluationType()
    {
        return $this->belongsTo(EvaluationsTypes::class, 'evaluations_type_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'competence_id');
    }

    public function climateOrganizationResponses()
    {
        return $this->hasMany(ClimateOrganizationalResponses::class, 'competence_id');
    }

    public function evaluation360responses()
    {
        return $this->hasMany(Evaluation360Response::class, 'competence_id');
    }
}
