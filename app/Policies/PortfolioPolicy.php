<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PortfolioPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any portfolio');
    }

    public function view(User $user): bool
    {
        return $user->can('view portfolio');
    }

    public function create(User $user): bool
    {
        return $user->can('create portfolio');
    }

    public function update(User $user): bool
    {
        return $user->can('update portfolio');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete portfolio');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore portfolio');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force-delete portfolio');
    }
}