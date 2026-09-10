<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use App\Services\TimeSheetAuth\ActorResolver;
use App\Services\TimeSheetAuthorizationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $employee_id
 * @property-read Employee|null $employee
 * @property-read Signature|null $signature
 */
class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use Searchable;

    protected $fillable = ['employee_id', 'name', 'email', 'password'];

    protected $searchableFields = ['*'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The employee this account belongs to.
     *
     * Every user is an employee — the database enforces it — but most
     * employees have no login, which is why the link lives on this side.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function timeSheets(): HasMany
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

    /**
     * The employee number, which lives on the employee record.
     *
     * Kept as an accessor so callers still read `$user->number` without a
     * second copy of the value that could drift from it.
     */
    public function getNumberAttribute(): ?int
    {
        return $this->employee?->number;
    }

    public function signature(): HasOne
    {
        return $this->hasOne(Signature::class);
    }

    /**
     * Employees managed by this user (via linked employee).
     */
    public function managedEmployees(): Collection
    {
        if (config('timesheet_auth.v2_read_enabled', false)) {
            return app(TimeSheetAuthorizationService::class)
                ->managedEmployeesQuery($this, 'general')
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
    /*
     * Eloquent hydrates models directly, so there is no constructor to inject
     * into: the container is reached explicitly here rather than threading
     * services through every call site.
     *
     * The deeper fix is that this authorization logic does not belong on the
     * model at all — see roadmap 2.4.
     */

    public function managedEmployeesQuery(string $context = 'general'): Builder
    {
        if (config('timesheet_auth.v2_read_enabled', false)) {
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
     */
    public function managedUsers(): Collection
    {
        $employeeIds = $this->managedEmployees()->pluck('id');

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        $managedUserIds = Employee::query()
            ->whereIn('id', $employeeIds)
            ->whereNotNull('user_id')
            ->pluck('user_id');

        return static::query()
            ->whereIn('id', $managedUserIds)
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
