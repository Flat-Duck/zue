<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LocationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the location can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('list locations');
    }

    /**
     * Determine whether the location can view the model.
     */
    public function view(User $user, Location $model): bool
    {
        return $user->checkPermissionTo('view locations');
    }

    /**
     * Determine whether the location can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create locations');
    }

    /**
     * Determine whether the location can update the model.
     */
    public function update(User $user, Location $model): bool
    {
        return $user->checkPermissionTo('update locations');
    }

    /**
     * Determine whether the location can delete the model.
     */
    public function delete(User $user, Location $model): bool
    {
        return $user->checkPermissionTo('delete locations');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete locations');
    }

    /**
     * Determine whether the location can restore the model.
     */
    public function restore(User $user, Location $model): bool
    {
        return false;
    }

    /**
     * Determine whether the location can permanently delete the model.
     */
    public function forceDelete(User $user, Location $model): bool
    {
        return false;
    }
}
