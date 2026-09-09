<?php

namespace App\Policies;

use App\Models\Center;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CenterPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the center can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('list centers');
    }

    /**
     * Determine whether the center can view the model.
     */
    public function view(User $user, Center $model): bool
    {
        return $user->checkPermissionTo('view centers');
    }

    /**
     * Determine whether the center can create models.
     */
    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create centers');
    }

    /**
     * Determine whether the center can update the model.
     */
    public function update(User $user, Center $model): bool
    {
        return $user->checkPermissionTo('update centers');
    }

    /**
     * Determine whether the center can delete the model.
     */
    public function delete(User $user, Center $model): bool
    {
        return $user->checkPermissionTo('delete centers');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete centers');
    }

    /**
     * Determine whether the center can restore the model.
     */
    public function restore(User $user, Center $model): bool
    {
        return false;
    }

    /**
     * Determine whether the center can permanently delete the model.
     */
    public function forceDelete(User $user, Center $model): bool
    {
        return false;
    }
}
