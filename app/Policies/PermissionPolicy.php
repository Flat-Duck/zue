<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    use HandlesAuthorization;

    public function list(User $user): bool
    {
        return $user->checkPermissionTo('list permissions');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->checkPermissionTo('view permissions');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create permissions');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->checkPermissionTo('update permissions');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->checkPermissionTo('delete permissions');
    }
}
