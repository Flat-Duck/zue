<?php

namespace App\Policies;

use App\Models\Plane;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Planes were previously readable and writable by any authenticated user.
 *
 * That matters more than it looks: a plane's capacity is the seat limit copied
 * onto every leg of a flight, so being able to edit or delete one changes who
 * can fly.
 */
class PlanePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('list planes');
    }

    public function view(User $user, Plane $model): bool
    {
        return $user->checkPermissionTo('view planes');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('create planes');
    }

    public function update(User $user, Plane $model): bool
    {
        return $user->checkPermissionTo('update planes');
    }

    public function delete(User $user, Plane $model): bool
    {
        return $user->checkPermissionTo('delete planes');
    }

    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('delete planes');
    }

    public function restore(User $user, Plane $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, Plane $model): bool
    {
        return false;
    }
}
