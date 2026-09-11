<?php

namespace App\Policies;

use App\Models\Appraisals\AppraisalReview;
use App\Models\Employee;
use App\Models\ScopeContext;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppraisalReviewPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->employee !== null;
    }

    public function create(User $user): bool
    {
        return $user->employee !== null;
    }

    public function view(User $user, AppraisalReview $review): bool
    {
        return (int) $user->employee?->id === (int) $review->appraiser_id;
    }

    public function update(User $user, AppraisalReview $review): bool
    {
        return $this->view($user, $review) && $review->status === 'draft';
    }

    public function submit(User $user, AppraisalReview $review): bool
    {
        return $this->update($user, $review);
    }

    public function reviewEmployee(User $user, Employee $employee): bool
    {
        if ($user->hasAnyRole(['hr', 'admin', 'super-admin'])) {
            return $employee->archived_at === null;
        }

        return $user->managedEmployeesQuery(ScopeContext::GENERAL)
            ->whereKey($employee->id)
            ->exists();
    }
}
