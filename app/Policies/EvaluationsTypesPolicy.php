<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EvaluationsTypesPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any evaluations-types');
    }

    public function view(User $user): bool
    {
        return $user->can('view evaluations-types');
    }

    public function create(User $user): bool
    {
        return $user->can('create evaluations-types');
    }

    public function update(User $user): bool
    {
        return $user->can('update evaluations-types');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete evaluations-types');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore evaluations-types');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force-delete evaluations-types');
    }
}