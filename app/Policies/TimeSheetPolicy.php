<?php

namespace App\Policies;

use App\Models\User;
use App\Models\TimeSheet;
use Illuminate\Auth\Access\HandlesAuthorization;

class TimeSheetPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the timeSheet can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list timesheets');
    }

    /**
     * Determine whether the timeSheet can view the model.
     */
    public function view(User $user, TimeSheet $model): bool
    {
        return $user->hasPermissionTo('view timesheets');
    }

    /**
     * Determine whether the timeSheet can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create timesheets');
    }

    /**
     * Determine whether the timeSheet can update the model.
     */
    public function update(User $user, TimeSheet $model): bool
    {
        return $user->hasPermissionTo('update timesheets');
    }

    /**
     * Determine whether the timeSheet can delete the model.
     */
    public function delete(User $user, TimeSheet $model): bool
    {
        return $user->hasPermissionTo('delete timesheets');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete timesheets');
    }

    /**
     * Determine whether the timeSheet can restore the model.
     */
    public function restore(User $user, TimeSheet $model): bool
    {
        return false;
    }

    /**
     * Determine whether the timeSheet can permanently delete the model.
     */
    public function forceDelete(User $user, TimeSheet $model): bool
    {
        return false;
    }
}
