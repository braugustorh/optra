<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PsychometricEvaluationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any psychometric-evaluation');
    }

    public function view(User $user): bool
    {
        return $user->can('view psychometric-evaluation');
    }

    public function create(User $user): bool
    {
       return $user->can('create psychometric-evaluation');
    }

    public function update(User $user): bool
    {
       return $user->can('update psychometric-evaluation');
    }

    public function delete(User $user): bool
    {
       return $user->can('delete psychometric-evaluation');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore psychometric-evaluation');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force-delete psychometric-evaluation');
    }
}
