<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SedePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any sede');
    }

    public function view(User $user): bool
    {
        return $user->can('view sede');
    }

    public function create(User $user): bool
    {
        return $user->can('create sede');
    }

    public function update(User $user): bool
    {
        return $user->can('update sede');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete sede');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore sede');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force-delete sede');
    }
}