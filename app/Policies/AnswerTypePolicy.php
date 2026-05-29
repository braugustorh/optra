<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AnswerTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view-any answer-type');
    }

    public function view(User $user): bool
    {
        return $user->can('view answer-type');
    }

    public function create(User $user): bool
    {
        return $user->can('create answer-type');
    }

    public function update(User $user): bool
    {
        return $user->can('update answer-type');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete answer-type');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore answer-type');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force-delete answer-type');
    }
}