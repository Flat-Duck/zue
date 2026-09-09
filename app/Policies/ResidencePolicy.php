<?php

namespace App\Policies;

use App\Models\Residence;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ResidencePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the residence can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('list residences');
    }

    /**
     * Determine whether the residence can view the model.
     */
    public function view(User $user, Residence $model): bool
    {
        return $user->checkPermissionTo('view residences');
    }

    /**
     * Determine whether the residence can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create residences');
    }

    /**
     * Determine whether the residence can update the model.
     */
    public function update(User $user, Residence $model): bool
    {
        return $user->checkPermissionTo('update residences');
    }

    /**
     * Determine whether the residence can delete the model.
     */
    public function delete(User $user, Residence $model): bool
    {
        return $user->checkPermissionTo('delete residences');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete residences');
    }

    /**
     * Determine whether the residence can restore the model.
     */
    public function restore(User $user, Residence $model): bool
    {
        return false;
    }

    /**
     * Determine whether the residence can permanently delete the model.
     */
    public function forceDelete(User $user, Residence $model): bool
    {
        return false;
    }
}
