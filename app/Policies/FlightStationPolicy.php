<?php

namespace App\Policies;

use App\Models\FlightStation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Stations are the places a flight calls at. They are referenced by routes and by
 * the frozen legs of flights that have already operated, so they are managed
 * alongside routes.
 */
class FlightStationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }

    public function view(User $user, FlightStation $model): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }

    public function update(User $user, FlightStation $model): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }

    public function delete(User $user, FlightStation $model): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }

    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }
}
