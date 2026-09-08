<?php

namespace App\Policies;

use App\Models\TimeSheet;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Permission;

class TimeSheetPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->hasAnyExistingPermission($user, ['list timesheets']);
    }

    public function view(User $user, TimeSheet $model): bool
    {
        return $this->hasAnyExistingPermission($user, ['view timesheets']);
    }

    public function create(User $user): bool
    {
        return $this->hasAnyExistingPermission($user, ['fill timesheets', 'create timesheets']);
    }

    public function update(User $user, TimeSheet $model): bool
    {
        return $this->hasAnyExistingPermission($user, ['revise timesheets', 'update timesheets']);
    }

    public function approve(User $user): bool
    {
        return $this->hasAnyExistingPermission($user, ['approve timesheets', 'update timesheets']);
    }

    public function delete(User $user, TimeSheet $model): bool
    {
        return $this->hasAnyExistingPermission($user, ['delete timesheets']);
    }

    public function deleteAny(User $user): bool
    {
        return $this->hasAnyExistingPermission($user, ['delete timesheets']);
    }

    public function restore(User $user, TimeSheet $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, TimeSheet $model): bool
    {
        return false;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function hasAnyExistingPermission(User $user, array $permissions): bool
    {
        $existingPermissions = Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('name');

        if ($existingPermissions->isEmpty()) {
            return false;
        }

        return $user->hasAnyPermission($existingPermissions->all());
    }
}
