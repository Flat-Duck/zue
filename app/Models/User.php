<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use App\Models\Scopes\Searchable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRoles;
    use Notifiable;
    use HasFactory;
    use Searchable;
    use HasApiTokens;

    protected $fillable = ['id','number','name', 'email', 'password'];

    protected $searchableFields = ['*'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->hasOne(Employee::class,'id','id');
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

    public function center()
    {
        return $this->employee->center_id;
    }

    public function department()
    {
        return $this->employee->department_id;
    }

    public function location()
    {
        return $this->employee->location_id;
    }

    public function employee_level()
    {
        return $this->employee->employee_level;
    }

    public function management_level()
    {
        return $this->employee->management_level;
    }

    public function getSignaturePathAttribute() {
        return $this->signature->image_path;
    }

    public function signature() {
        return $this->hasOne(Signature::class);
    }

    protected static function booted()
    {
        static::created(function ($user) {
            $user->id = $user->number;
            $user->save();
        });
    }

    /**
     * Employees managed by this user (via linked employee).
     */
    public function managedEmployees()
    {
        if (!$this->employee) {
            return collect();
        }

        return $this->employee->managedEmployees();
    }

    /**
     * Query builder for employees managed by this user.
     * Useful for pagination and eager loading.
     */
    public function managedEmployeesQuery($context = 'general')
    {
        if (!$this->employee) {
            return Employee::query()->whereRaw('0 = 1');
        }

        return $this->employee->managedEmployeesQuery($context);
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
        if (!$this->employee || !$target->employee) {
            return false;
        }

        return $this->employee->canManage($target->employee);
    }
}
