<?php

namespace App\Models;

use App\Helpers\TimeSheetBuilder;
use App\Models\Scopes\DepartmentEmployees;
use App\Models\Scopes\Searchable;
use App\Models\Scopes\SoftArchives;
use App\Models\Scopes\SoftArchivingScope;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Employee extends Model
{
    use HasFactory;
    use Searchable;
    use SoftArchives;
    use SoftDeletes;

    public const SPECIAL_WORK_DAYS_THRESHOLD = 20;

    protected $fillable = [
        'number',
        'job',
        'english_name',
        'id_card',
        'id_card_issue_date',
        'passport',
        'passport_issue_date',
        'address',
        'phone',
        'email',
        'user_id',
        'location_id',
        'department_id',
        'center_id',
        'transfered_balance',
        'schedule',
        'start_date',
        'last_date',
        'total_balance',
        'archived_at',
        'management_level',
        'employee_level',
    ];

    protected $appends = [
        'administration_name',
        'department_name',
        'location_name',
        'center_name',
        'start_date',
        'last_date',
        'balance',
        'total_working_days',
        'total_off_days',
        'default_over_time_value',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'id_card_issue_date' => 'date',
        'passport_issue_date' => 'date',
        'start_date' => 'date',
        'last_date' => 'date',
        'archived_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function timeSheets()
    {
        return $this->hasMany(TimeSheet::class);
    }

    public function clinicApointments()
    {
        return $this->hasMany(ClinicApointment::class);
    }

    public function apointments()
    {
        $appointments = $this->clinicApointments
            // ->select(
            //     DB::raw('YEAR(created_at) as year'),
            //     DB::raw('MONTHNAME(created_at) as month'),
            //     DB::raw('COUNT(*) as count'))
            //     ->groupBy('year', 'month')
            //     ->get();

            ->map(function ($appointment) {
                $appointment->year = Carbon::parse($appointment->date)->format('Y');
                $appointment->month = Carbon::parse($appointment->date)->format('F');

                return $appointment;
            });

        // Group appointments by year and month
        return $appointments->groupBy(function ($appointment) {
            return $appointment->year.'-'.$appointment->month;
        });
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function rooms()
    {
        return $this->belongsToMany(Room::class);
    }

    public function sick_leaves()
    {
        return 10;
    }

    public function flights()
    {
        return $this->belongsToMany(Flight::class);
    }

    public function getOwnRoomAttribute()
    {
        return $this->rooms()->where('is_owner', true)->exists();
    }

    public function getAdministrationNameAttribute()
    {
        return $this->department->administration->name;
    }

    public function getDepartmentNameAttribute()
    {
        return $this->department->name;
    }

    public function getLocationNameAttribute()
    {
        return $this->location->name;
    }

    public function getCenterNameAttribute()
    {
        return $this->center->name;
    }

    public function getStartDateAttribute($date)
    {
        return date('Y/m/d', strtotime($date));
    }

    public function getLastDateAttribute($date)
    {
        return date('Y/m/d', strtotime($date));
    }

    public function getBalanceAttribute()
    {
        return $this->total_balance;
    }

    public function calculateBalance()
    {
        $balance = TimeSheetBuilder::calculateBalance($this->id, $this->schedule, $this->transfered_balance);
        $this->total_balance = $balance;
        $this->save();
    }

    public function getTotalWorkingDaysAttribute()
    {
        return $this->timeSheets()->whereIn('value', ['A', 'B', 'Y', 'K'])->count();
    }

    public function getTotalOffDaysAttribute()
    {
        return $this->timeSheets()->whereIn('value', ['F', 'X'])->count();
    }

    public function getDefaultOverTimeValueAttribute()
    {
        return 2;
    }

    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    public function isFieldCoordinator(): bool
    {
        return $this->hasRole('fieldcoordinator');
    }

    public function isSuperintendent(): bool
    {
        return $this->hasRole('superintendent');
    }

    public function isTimekeeper(): bool
    {
        return $this->hasRole('timekeeper');
    }

    public function managementScopes(): BelongsToMany
    {
        return $this->belongsToMany(ManagementScope::class, 'management_scope_manager', 'manager_id', 'management_scope_id');
    }

    /**
     * Legacy owned scopes.
     */
    public function ownedManagementScopes(): HasMany
    {
        return $this->hasMany(ManagementScope::class, 'manager_id');
    }

    public function getFullNameAttribute(): string
    {
        return (string) $this->english_name;
    }

    public function isArchived(): bool
    {
        return ! is_null($this->archived_at);
    }

    /**
     * Query builder for all employees this employee can manage.
     * Use this if you want to paginate, eager-load, etc.
     */
    public function managedEmployeesQuery(?string $context = 'general'): Builder
    {
        $scopes = $this->managementScopes()
            ->where(function ($q) use ($context) {
                if ($context) {
                    $q->where('context', $context);
                }
            })
            ->get();

        // If no scope is defined, this manager manages nobody
        if ($scopes->isEmpty()) {
            return static::query()->whereRaw('0 = 1');
        }

        $query = static::query()
            ->whereNull('archived_at')
            ->where(function (Builder $q) use ($scopes) {
                foreach ($scopes as $scope) {
                    $settings = is_array($scope->settings) ? $scope->settings : [];
                    $jobTitle = $settings['job_title'] ?? null;

                    $applySettings = function (Builder $query) use ($jobTitle) {
                        if ($jobTitle) {
                            $query->where('job', 'like', trim($jobTitle));
                        }
                    };

                    switch ($scope->scope_type) {
                        case ManagementScope::TYPE_GLOBAL:
                            $q->orWhere(function (Builder $q2) use ($applySettings) {
                                $applySettings($q2);
                            });
                            break;

                        case ManagementScope::TYPE_LOCATION:
                            if ($scope->location_id) {
                                $q->orWhere(function (Builder $q2) use ($scope, $applySettings) {
                                    $q2->where('location_id', $scope->location_id);
                                    $applySettings($q2);
                                });
                            }
                            break;

                        case ManagementScope::TYPE_DEPARTMENT:
                            if ($scope->location_id && $scope->department_id) {
                                $q->orWhere(function (Builder $q2) use ($scope, $applySettings) {
                                    $q2
                                        ->where('location_id', $scope->location_id)
                                        ->where('department_id', $scope->department_id);
                                    $applySettings($q2);
                                });
                            }
                            break;

                        case ManagementScope::TYPE_CENTER:
                            if ($scope->center_id) {
                                $q->orWhere(function (Builder $q2) use ($scope, $applySettings) {
                                    $q2->where('center_id', $scope->center_id);
                                    $applySettings($q2);
                                });
                            }
                            break;

                        case ManagementScope::TYPE_EMPLOYEE:
                            // 1. Direct subordinate
                            if ($scope->subordinate_employee_id) {
                                $q->orWhere('id', $scope->subordinate_employee_id);
                            }
                            // 2. Grouped subordinates
                            $targetIds = $settings['target_employee_ids'] ?? [];
                            if (! empty($targetIds)) {
                                $q->orWhereIn('id', $targetIds);
                            }
                            break;
                    }
                }
            });

        if ($context !== 'time_sheet') {
            return $query;
        }

        // Strict ownership for time-sheet context:
        // - hide employees assigned as subordinate/target in another manager's scope
        // - hide employees that are managers in any time_sheet scope
        $candidateIds = (clone $query)->pluck('id')->map(fn ($id) => (int) $id)->values();

        if ($candidateIds->isEmpty()) {
            return static::query()->whereRaw('0 = 1');
        }

        $timeSheetScopes = ManagementScope::query()
            ->where('context', 'time_sheet')
            ->with('managers:id')
            ->get([
                'id',
                'manager_id',
                'subordinate_employee_id',
                'settings',
            ]);

        $disallowedIds = [];
        $managerPoolIds = [];
        $candidateLookup = $candidateIds->flip();
        $myEmployeeScopeAllowedIds = [];

        foreach ($scopes as $myScope) {
            if ($myScope->scope_type !== ManagementScope::TYPE_EMPLOYEE) {
                continue;
            }

            if (! is_null($myScope->subordinate_employee_id)) {
                $myEmployeeScopeAllowedIds[] = (int) $myScope->subordinate_employee_id;
            }

            $myTargetIds = (array) ($myScope->settings['target_employee_ids'] ?? []);
            foreach ($myTargetIds as $myTargetId) {
                $myEmployeeScopeAllowedIds[] = (int) $myTargetId;
            }
        }

        $myEmployeeScopeAllowedLookup = collect(array_unique($myEmployeeScopeAllowedIds))->flip();

        foreach ($timeSheetScopes as $scope) {
            $managerIds = $scope->managers->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (empty($managerIds) && ! is_null($scope->manager_id)) {
                $managerIds = [(int) $scope->manager_id];
            }
            foreach ($managerIds as $managerId) {
                $managerPoolIds[] = (int) $managerId;
            }

            if (in_array((int) $this->id, $managerIds, true)) {
                continue;
            }

            if (! is_null($scope->subordinate_employee_id)) {
                $subordinateId = (int) $scope->subordinate_employee_id;
                if ($candidateLookup->has($subordinateId)) {
                    $disallowedIds[] = $subordinateId;
                }
            }

            $targetIds = (array) ($scope->settings['target_employee_ids'] ?? []);
            foreach ($targetIds as $targetId) {
                $targetId = (int) $targetId;
                if ($candidateLookup->has($targetId)) {
                    $disallowedIds[] = $targetId;
                }
            }
        }

        foreach (array_unique($managerPoolIds) as $managerId) {
            if ($candidateLookup->has($managerId)) {
                // Manager-employees are only visible when explicitly assigned in
                // one of my own employee-type scopes.
                if ($myEmployeeScopeAllowedLookup->has((int) $managerId)) {
                    continue;
                }
                $disallowedIds[] = $managerId;
            }
        }

        $allowedIds = $candidateIds
            ->diff(array_unique($disallowedIds))
            ->values()
            ->all();

        if (empty($allowedIds)) {
            return static::query()->whereRaw('0 = 1');
        }

        return $query->whereIn('id', $allowedIds);
    }

    /**
     * Get all managed employees as a collection.
     */
    public function managedEmployees(?string $context = 'general')
    {
        return $this->managedEmployeesQuery($context)->get();
    }

    /**
     * True/false check if this employee can manage the target.
     */
    public function canManage(Employee $target): bool
    {
        if ($this->id === $target->id) {
            return false;
        }

        if ($this->trashed() || $target->trashed()) {
            return false;
        }

        if ($this->isArchived() || $target->isArchived()) {
            return false;
        }

        return $this
            ->managedEmployeesQuery()
            ->where('id', $target->id)
            ->exists();
    }

    /**
     * High-level check: can this employee manage the target employee?
     */
    // public function canManage(Employee $target): bool
    // {
    //     $scopes = $this->managementScopes()->get();

    //     foreach ($scopes as $scope) {
    //         if ($scope->matchesTargetEmployee($target)) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    protected static function boot()
    {
        parent::boot();
        // if (Auth::check() && auth()->user()->hasRole('supervisor'))
        // {
        //     static::addGlobalScope(new DepartmentEmployees(auth()->user()->center()));
        // }
        // if (Auth::check() && (auth()->user()->hasRole('supervisor')
        //     ||auth()->user()->hasRole('supervisor') ||auth()->user()->hasRole('supervisor')))
        // {
        //     static::addGlobalScope(new UnderSupervisionEmployees(
        //         auth()->user()->center(),
        //         auth()->user()->department(),
        //         auth()->user()->location()
        //         )
        //     );
        // }
        static::addGlobalScope(new SoftArchivingScope);
    }
}
