<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QuestionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any question');
    }

    public function view(User $user): bool
    {
        return $user->can('view question');
    }

    public function create(User $user): bool
    {
        return $user->can('create question');
    }

    public function update(User $user): bool
    {
        return $user->can('update question');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete question');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore question');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force-delete question');
    }
}