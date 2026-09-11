<?php

namespace App\Policies;

use App\Models\ScopePolicy;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * A management scope decides who somebody may see, so editing one is an
 * authorization change in itself. It is gated on the same permissions as editing
 * the people it covers.
 */
class ManagementScopePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('list users') || $user->checkPermissionTo('list employees');
    }

    public function view(User $user, ScopePolicy $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('update users') || $user->checkPermissionTo('update employees');
    }

    public function update(User $user, ScopePolicy $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ScopePolicy $model): bool
    {
        return $user->checkPermissionTo('delete users') || $user->checkPermissionTo('delete employees');
    }
}
