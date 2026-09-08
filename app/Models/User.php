<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use App\Services\TimeSheetAuth\ActorResolver;
use App\Services\TimeSheetAuthorizationService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use Searchable;

    protected $fillable = ['number', 'name', 'email', 'password'];

    public $incrementing = false;

    protected $keyType = 'int';

    protected $searchableFields = ['*'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->hasOne(Employee::class, 'id', 'id');
        // If you want to use employees.user_id instead, change to:
        // return $this->hasOne(Employee::class, 'user_id', 'id');
    }

    public function timeSheets()
    {
        return $this->hasMany(TimeSheet::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function center(): ?int
    {
        return $this->employee?->center_id;
    }

    public function department(): ?int
    {
        return $this->employee?->department_id;
    }

    public function location(): ?int
    {
        return $this->employee?->location_id;
    }

    public function employee_level(): ?int
    {
        return $this->employee?->employee_level;
    }

    public function management_level(): ?int
    {
        return $this->employee?->management_level;
    }

    public function getSignaturePathAttribute(): ?string
    {
        return $this->signature?->image_path;
    }

    public function signature()
    {
        return $this->hasOne(Signature::class);
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            $user->id = (int) $user->number;
        });

        static::updating(function ($user) {
            if ($user->isDirty('number')) {
                $user->id = (int) $user->number;
            }
        });
    }

    /**
     * Employees managed by this user (via linked employee).
     */
    public function managedEmployees()
    {
        if (config('timesheet_auth.v2_read_enabled', false)) {
            return app(TimeSheetAuthorizationService::class)
                ->managedEmployeesQuery($this, 'time_sheet')
                ->get();
        }

        $employee = app(ActorResolver::class)->resolveEmployee($this);
        if (! $employee) {
            return collect();
        }

        return $employee->managedEmployees();
    }

    /**
     * Query builder for employees managed by this user.
     * Useful for pagination and eager loading.
     */
    public function managedEmployeesQuery($context = 'general')
    {
        if (config('timesheet_auth.v2_read_enabled', false) && $context === 'time_sheet') {
            return app(TimeSheetAuthorizationService::class)->managedEmployeesQuery($this, $context);
        }

        $employee = app(ActorResolver::class)->resolveEmployee($this);
        if (! $employee) {
            return Employee::query()->whereRaw('0 = 1');
        }

        return $employee->managedEmployeesQuery($context);
    }

    /**
     * Users corresponding to the employees this user manages.
     * This assumes user.id == employee.id (like in your booted override).
     */
    public function managedUsers()
    {
        $employeeIds = $this->managedEmployees()->pluck('id');

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        return static::query()
            ->whereIn('id', $employeeIds)
            ->get();
    }

    /**
     * Check if this user can manage another user.
     */
    public function canManageUser(User $target): bool
    {
        $selfEmployee = app(ActorResolver::class)->resolveEmployee($this);
        $targetEmployee = app(ActorResolver::class)->resolveEmployee($target);

        if (! $selfEmployee || ! $targetEmployee) {
            return false;
        }

        return $selfEmployee->canManage($targetEmployee);
    }
}
