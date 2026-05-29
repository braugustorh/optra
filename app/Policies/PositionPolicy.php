<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PositionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any position');
    }

    public function view(User $user): bool
    {
        return $user->can('view position');
    }

    public function create(User $user): bool
    {
        return $user->can('create position');
    }

    public function update(User $user): bool
    {
        return $user->can('update position');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete position');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore position');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force-delete position');
    }
}