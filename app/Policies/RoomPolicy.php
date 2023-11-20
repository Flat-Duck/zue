<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RoomPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the room can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list rooms');
    }

    /**
     * Determine whether the room can view the model.
     */
    public function view(User $user, Room $model): bool
    {
        return $user->hasPermissionTo('view rooms');
    }

    /**
     * Determine whether the room can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create rooms');
    }

    /**
     * Determine whether the room can update the model.
     */
    public function update(User $user, Room $model): bool
    {
        return $user->hasPermissionTo('update rooms');
    }

    /**
     * Determine whether the room can delete the model.
     */
    public function delete(User $user, Room $model): bool
    {
        return $user->hasPermissionTo('delete rooms');
    }

    /**
     * Determine whether the user can delete multiple instances of the model.
     */
    public function deleteAny(User $user): bool
    {
        return $user->hasPermissionTo('delete rooms');
    }

    /**
     * Determine whether the room can restore the model.
     */
    public function restore(User $user, Room $model): bool
    {
        return false;
    }

    /**
     * Determine whether the room can permanently delete the model.
     */
    public function forceDelete(User $user, Room $model): bool
    {
        return false;
    }
}
