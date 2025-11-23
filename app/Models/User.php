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
}
