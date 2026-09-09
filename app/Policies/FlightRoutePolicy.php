<?php

namespace App\Policies;

use App\Models\FlightRoute;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Routes decide which legs a flight has and whether each one counts as coming
 * or leaving, so editing them is a dispatcher-level responsibility rather than
 * ordinary reference-data maintenance.
 */
class FlightRoutePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }

    public function view(User $user, FlightRoute $model): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }

    public function update(User $user, FlightRoute $model): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }

    public function delete(User $user, FlightRoute $model): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }

    public function deleteAny(User $user): bool
    {
        return $user->checkPermissionTo('manage flight routes');
    }
}
