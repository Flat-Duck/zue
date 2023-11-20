<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Passenger;
use Illuminate\Auth\Access\HandlesAuthorization;

class PassengerPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the passenger can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list passengers');
    }

    /**
     * Determine whether the passenger can view the model.
     */
    public function view(User $user, Passenger $model): bool
    {
        return $user->hasPermissionTo('view passengers');
    }

    /**
     * Determine whether the passenger can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create passengers');
    }

    /**
     * Determine whether the passenger can update the model.
     */
    public function update(User $user, Passenger $model): bool
    {
        return $user->hasPermissionTo('update passengers');
    }

    /**
     * Determine whether the passenger can delete the model.
     */
    public function delete(User $user, Passenger $model): bool
    {
        return $user->hasPermissionTo('delete passengers');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete passengers');
    }

    /**
     * Determine whether the passenger can restore the model.
     */
    public function restore(User $user, Passenger $model): bool
    {
        return false;
    }

    /**
     * Determine whether the passenger can permanently delete the model.
     */
    public function forceDelete(User $user, Passenger $model): bool
    {
        return false;
    }
}
