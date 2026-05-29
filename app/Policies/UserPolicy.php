<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any user');
    }

    public function view(User $user): bool
    {
        return $user->can('view user');
    }

    public function create(User $user): bool
    {
        return $user->can('create user');
    }

    public function update(User $user): bool
    {
        return $user->can('update user');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete user');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore user');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force-delete user');
    }
}