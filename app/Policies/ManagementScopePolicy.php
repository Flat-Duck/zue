<?php

namespace App\Policies;

use App\Models\ManagementScope;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ManagementScopePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list users') || $user->hasPermissionTo('list employees');
    }

    public function view(User $user, ManagementScope $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('update users') || $user->hasPermissionTo('update employees');
    }

    public function update(User $user, ManagementScope $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ManagementScope $model): bool
    {
        return $user->hasPermissionTo('delete users') || $user->hasPermissionTo('delete employees');
    }
}
