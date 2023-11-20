<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Flight;
use Illuminate\Auth\Access\HandlesAuthorization;

class FlightPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the flight can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list flights');
    }

    /**
     * Determine whether the flight can view the model.
     */
    public function view(User $user, Flight $model): bool
    {
        return $user->hasPermissionTo('view flights');
    }

    /**
     * Determine whether the flight can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create flights');
    }

    /**
     * Determine whether the flight can update the model.
     */
    public function update(User $user, Flight $model): bool
    {
        return $user->hasPermissionTo('update flights');
    }

    /**
     * Determine whether the flight can delete the model.
     */
    public function delete(User $user, Flight $model): bool
    {
        return $user->hasPermissionTo('delete flights');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete flights');
    }

    /**
     * Determine whether the flight can restore the model.
     */
    public function restore(User $user, Flight $model): bool
    {
        return false;
    }

    /**
     * Determine whether the flight can permanently delete the model.
     */
    public function forceDelete(User $user, Flight $model): bool
    {
        return false;
    }
}
