<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompetencePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any competence');
    }

    public function view(User $user): bool
    {
        return $user->can('view competence');
    }

    public function create(User $user): bool
    {
        return $user->can('create competence');
    }

    public function update(User $user): bool
    {
        return $user->can('update competence');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete competence');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore competence');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force-delete competence');
    }
}