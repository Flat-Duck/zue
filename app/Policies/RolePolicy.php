<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    use HandlesAuthorization;

    public function list(User $user): bool
    {
        return $user->checkPermissionTo('list roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->checkPermissionTo('view roles');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->checkPermissionTo('update roles');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->checkPermissionTo('delete roles');
    }
}
