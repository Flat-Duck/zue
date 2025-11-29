<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Plane;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlanePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the plane can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the plane can view the model.
     */
    public function view(User $user, Plane $model): bool
    {
        return true;
    }

    /**
     * Determine whether the plane can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the plane can update the model.
     */
    public function update(User $user, Plane $model): bool
    {
        return true;
    }

    /**
     * Determine whether the plane can delete the model.
     */
    public function delete(User $user, Plane $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the plane can restore the model.
     */
    public function restore(User $user, Plane $model): bool
    {
        return false;
    }

    /**
     * Determine whether the plane can permanently delete the model.
     */
    public function forceDelete(User $user, Plane $model): bool
    {
        return false;
    }
}
