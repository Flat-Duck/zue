<?php

namespace App\Policies;

use App\Models\Appraisals\AppraisalOfficial;
use App\Models\Employee;
use App\Models\ScopeContext;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppraisalOfficialPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function manage(User $user): bool
    {
        return $user->hasAnyRole(['hr', 'admin', 'super-admin']);
    }

    public function viewForEmployee(User $user, Employee $employee): bool
    {
        if ($this->manage($user)) {
            return true;
        }

        if ((int) $user->employee?->id === (int) $employee->id) {
            return true;
        }

        return $user->managedEmployeesQuery(ScopeContext::GENERAL)
            ->whereKey($employee->id)
            ->exists();
    }

    public function approveEmployee(User $user, AppraisalOfficial $official): bool
    {
        return (int) $user->employee?->id === (int) $official->employee_id;
    }

    public function approveManager(User $user, AppraisalOfficial $official): bool
    {
        if (! $user->hasAnyRole(['manager', 'supervisor', 'superintendent', 'fieldcoordinator'])) {
            return false;
        }

        return $user->managedEmployeesQuery(ScopeContext::GENERAL)
            ->whereKey($official->employee_id)
            ->exists();
    }

    public function approveHr(User $user, AppraisalOfficial $official): bool
    {
        return $this->manage($user);
    }
}
