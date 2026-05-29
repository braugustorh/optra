<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;


class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    public function canAccessPanel(Panel $panel): bool
    {
        //return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
        return true;
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'curp',
        'sex',
        'nationality',
        'birthdate',
        'birth_country',
        'birth_state',
        'birth_city',
        'disability',
        'phone',
        'email',
        'password',
        'scholarship',
        'career',
        'razon_social_id',
        'sede_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function sede()
    {
        return $this->belongsTo(Sede::class,'sede_id');
    }
    public function position(): belongsTo
    {
        return $this->belongsTo(Position::class, 'position_id');
    }
    public function department(): belongsTo
    {
        return $this->belongsTo(Department::class);
    }
    public function portfolio()
    {
        return $this->hasOne(Portfolio::class, 'user_id');
    }
    public function razonSocial(): BelongsTo
    {
        return $this->belongsTo(RazonSocial::class, 'razon_social_id');
    }
    public function psychometry(): hasMany{
    return $this->hasMany(Psychometry::class);
    }
    public function evaluationResponses360User(): hasMany{
        return $this->hasMany(Evaluation360Response::class, 'user_id');
    }
    public function evaluationResponses360Evaluated(): hasMany{
        return $this->hasMany(Evaluation360Response::class, 'evaluated_user_id');
    }
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->morphToMany(Role::class, 'model', 'model_has_roles', 'model_id', 'role_id');
    }
    public function indicators(): hasMany
    {
        return $this->hasMany(Indicator::class);
    }
    public function climaResponses(): hasMany
    {
        return $this->hasMany(ClimateOrganizationalResponses::class);
    }
    public function identifiedCollaborators(): hasMany
    {
        return $this->hasMany(IdentifiedCollaborator::class, 'user_id');
    }
    public function traumaticEvents(): hasMany
    {
        return $this->hasMany(TraumaticEvent::class, 'user_id');
    }
    public function traumaticEventSurveys(): hasMany
    {
        return $this->hasMany(TraumaticEventSurvey::class, 'user_id');
    }

    public function riskFactorSurveys(): hasMany{
        return $this->hasMany(RiskFactorSurvey::class, 'user_id');
    }
    public function riskFactorSurveyOrganizations(): hasMany
    {
        return $this->hasMany(RiskFactorSurveyOrganizational::class, 'user_id');
    }



// Nueva relación para el módulo de psicometrías
    public function psychometricEvaluations(): MorphMany
    {
        return $this->morphMany(PsychometricEvaluation::class, 'evaluable');
    }

    public function termination(): HasOne
    {
        return $this->hasOne(UserTermination::class);
    }

    // Evaluaciones psicométricas asignadas por este usuario (como RH o supervisor)
    public function assignedPsychometricEvaluations(): HasMany
    {
        return $this->hasMany(PsychometricEvaluation::class, 'assigned_by');
    }



    public function getProfilePhotoUrlAttribute(): string
    {
        if (! $this->profile_photo) {
            return asset('images/default-avatar.png');
        }

        $path = $this->profile_photo;

        if (str_starts_with($path, 'http')) {
            return $path; // Ya es URL completa almacenada
        }

        // Público:
        return Storage::disk('sedyco_disk')->url($path);

        // Privado (alternativa):
        // return Storage::disk('sedyco_disk')->temporaryUrl($path, now()->addMinutes(10));
    }




}
