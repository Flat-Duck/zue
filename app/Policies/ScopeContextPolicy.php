<?php

namespace App\Policies;

use App\Models\ScopeContext;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Contexts are the vocabulary management scopes are written in, so they are
 * gated exactly as the scopes themselves are.
 */
class ScopeContextPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('list users') || $user->checkPermissionTo('list employees');
    }

    public function view(User $user, ScopeContext $context): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('update users') || $user->checkPermissionTo('update employees');
    }

    public function update(User $user, ScopeContext $context): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ScopeContext $context): bool
    {
        return ! $context->isBuiltIn()
            && ($user->checkPermissionTo('delete users') || $user->checkPermissionTo('delete employees'));
    }
}
