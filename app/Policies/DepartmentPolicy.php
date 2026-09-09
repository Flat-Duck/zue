<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the department can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('list departments');
    }

    /**
     * Determine whether the department can view the model.
     */
    public function view(User $user, Department $model): bool
    {
        return $user->checkPermissionTo('view departments');
    }

    /**
     * Determine whether the department can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create departments');
    }

    /**
     * Determine whether the department can update the model.
     */
    public function update(User $user, Department $model): bool
    {
        return $user->checkPermissionTo('update departments');
    }

    /**
     * Determine whether the department can delete the model.
     */
    public function delete(User $user, Department $model): bool
    {
        return $user->checkPermissionTo('delete departments');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete departments');
    }

    /**
     * Determine whether the department can restore the model.
     */
    public function restore(User $user, Department $model): bool
    {
        return false;
    }

    /**
     * Determine whether the department can permanently delete the model.
     */
    public function forceDelete(User $user, Department $model): bool
    {
        return false;
    }
}
