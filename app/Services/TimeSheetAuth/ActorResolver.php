<?php

namespace App\Services\TimeSheetAuth;

use App\Models\Employee;
use App\Models\User;

class ActorResolver
{
    public function resolveEmployee(?User $user = null): ?Employee
    {
        $user ??= auth()->user();
        if (! $user) {
            return null;
        }

        return $user->employee;
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

        if (! $employee) {
            return null;
        }

        return User::query()->where('employee_id', $employee->id)->first();
    }
}
