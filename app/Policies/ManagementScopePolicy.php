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
        return $user->checkPermissionTo('list users') || $user->checkPermissionTo('list employees');
    }

    public function view(User $user, ManagementScope $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('update users') || $user->checkPermissionTo('update employees');
    }

    public function update(User $user, ManagementScope $model): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ManagementScope $model): bool
    {
        return $user->checkPermissionTo('delete users') || $user->checkPermissionTo('delete employees');
    }
}
