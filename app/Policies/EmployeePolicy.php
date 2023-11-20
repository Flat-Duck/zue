<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the employee can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list employees');
    }

    /**
     * Determine whether the employee can view the model.
     */
    public function view(User $user, Employee $model): bool
    {
        return $user->hasPermissionTo('view employees');
    }

    /**
     * Determine whether the employee can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create employees');
    }

    /**
     * Determine whether the employee can update the model.
     */
    public function update(User $user, Employee $model): bool
    {
        return $user->hasPermissionTo('update employees');
    }

    /**
     * Determine whether the employee can delete the model.
     */
    public function delete(User $user, Employee $model): bool
    {
        return $user->hasPermissionTo('delete employees');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete employees');
    }

    /**
     * Determine whether the employee can restore the model.
     */
    public function restore(User $user, Employee $model): bool
    {
        return false;
    }

    /**
     * Determine whether the employee can permanently delete the model.
     */
    public function forceDelete(User $user, Employee $model): bool
    {
        return false;
    }
}
