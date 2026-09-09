<?php

namespace App\Policies;

use App\Models\Administration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdministrationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the administration can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('list administrations');
    }

    /**
     * Determine whether the administration can view the model.
     */
    public function view(User $user, Administration $model): bool
    {
        return $user->checkPermissionTo('view administrations');
    }

    /**
     * Determine whether the administration can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create administrations');
    }

    /**
     * Determine whether the administration can update the model.
     */
    public function update(User $user, Administration $model): bool
    {
        return $user->checkPermissionTo('update administrations');
    }

    /**
     * Determine whether the administration can delete the model.
     */
    public function delete(User $user, Administration $model): bool
    {
        return $user->checkPermissionTo('delete administrations');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete administrations');
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
