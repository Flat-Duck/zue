<?php

namespace App\Services\TimeSheetAuth;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ActorResolver
{
    public function resolveEmployee(?User $user = null): ?Employee
    {
        $user ??= auth()->user();
        if (!$user) {
            return null;
        }

        $employee = Employee::query()->where('user_id', $user->id)->first();
        if ($employee) {
            return $employee;
        }

        $legacy = Employee::query()->find($user->id);
        if ($legacy && config('timesheet_auth.log_actor_fallback', true)) {
            Log::warning('timesheet_auth_v2_actor_resolution_fallback', [
                'user_id' => $user->id,
                'employee_id' => $legacy->id,
            ]);
        }

        return $legacy;
    }

    public function resolveEmployeeByUserId(int $userId): ?Employee
    {
        return $this->resolveEmployee(User::query()->find($userId));
    }

    public function resolveUserForEmployee(Employee|int|null $employee): ?User
    {
        if (is_null($employee)) {
            return null;
        }

        $employee = is_int($employee)
            ? Employee::query()->find($employee)
            : $employee;

        if (!$employee) {
            return null;
        }

        if (!is_null($employee->user_id)) {
            $user = User::query()->find($employee->user_id);
            if ($user) {
                return $user;
            }
        }

        $legacyUser = User::query()->find($employee->id);
        if ($legacyUser && config('timesheet_auth.log_actor_fallback', true)) {
            Log::warning('timesheet_auth_v2_user_resolution_fallback', [
                'employee_id' => $employee->id,
                'user_id' => $legacyUser->id,
            ]);
        }

        return $legacyUser;
    }
}
