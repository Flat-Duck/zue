<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Administration;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdministrationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the administration can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list administrations');
    }

    /**
     * Determine whether the administration can view the model.
     */
    public function view(User $user, Administration $model): bool
    {
        return $user->hasPermissionTo('view administrations');
    }

    /**
     * Determine whether the administration can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create administrations');
    }

    /**
     * Determine whether the administration can update the model.
     */
    public function update(User $user, Administration $model): bool
    {
        return $user->hasPermissionTo('update administrations');
    }

    /**
     * Determine whether the administration can delete the model.
     */
    public function delete(User $user, Administration $model): bool
    {
        return $user->hasPermissionTo('delete administrations');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete administrations');
    }

    /**
     * Determine whether the administration can restore the model.
     */
    public function restore(User $user, Administration $model): bool
    {
        return false;
    }

    /**
     * Determine whether the administration can permanently delete the model.
     */
    public function forceDelete(User $user, Administration $model): bool
    {
        return false;
    }
}
