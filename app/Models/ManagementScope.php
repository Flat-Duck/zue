<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagementScope extends Model
{
    protected $guarded = [];

    public const TYPE_GLOBAL     = 'global';     // whole company
    public const TYPE_LOCATION   = 'location';   // specific location/site
    public const TYPE_DEPARTMENT = 'department'; // specific department in a location
    public const TYPE_CENTER     = 'center';     // specific center
    public const TYPE_EMPLOYEE   = 'employee';   // specific employee

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinate(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'subordinate_employee_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class, 'center_id');
    }

    /**
     * Does this scope cover the target employee?
     */
    public function matchesTargetEmployee(Employee $target): bool
    {
        switch ($this->scope_type) {
            case self::TYPE_GLOBAL:
                return true;

            case self::TYPE_LOCATION:
                return !is_null($this->location_id)
                    && $this->location_id === $target->location_id;

            case self::TYPE_DEPARTMENT:
                return !is_null($this->location_id)
                    && !is_null($this->department_id)
                    && $this->location_id === $target->location_id
                    && $this->department_id === $target->department_id;

            case self::TYPE_CENTER:
                return !is_null($this->center_id)
                    && $this->center_id === $target->center_id;

            case self::TYPE_EMPLOYEE:
                return !is_null($this->subordinate_employee_id)
                    && $this->subordinate_employee_id === $target->id;

            default:
                return false;
        }
    }
}

// namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\Relations\BelongsTo;
// class ManagementScope extends Model
// {
//     use HasFactory;
//     protected $guarded = [];

//     public const TYPE_GLOBAL     = 'global';     // whole company
//     public const TYPE_LOCATION   = 'location';   // one location/site
//     public const TYPE_DEPARTMENT = 'department'; // one department in a location
//     public const TYPE_CENTER     = 'center';     // one center
//     public const TYPE_EMPLOYEE   = 'employee';   // one specific employee

//     public function manager(): BelongsTo
//     {
//         return $this->belongsTo(Employee::class, 'manager_id');
//     }

//     public function subordinate(): BelongsTo
//     {
//         return $this->belongsTo(Employee::class, 'subordinate_employee_id');
//     }

//     public function location(): BelongsTo
//     {
//         return $this->belongsTo(Location::class, 'location_id');
//     }

//     public function department(): BelongsTo
//     {
//         return $this->belongsTo(Department::class, 'department_id');
//     }

//     public function center(): BelongsTo
//     {
//         return $this->belongsTo(Center::class, 'center_id');
//     }

//     /**
//      * Does this scope cover the target employee?
//      */
//     public function matchesTargetEmployee(Employee $target): bool
//     {
//         switch ($this->scope_type) {
//             case self::TYPE_GLOBAL:
//                 return true;

//             case self::TYPE_LOCATION:
//                 return !is_null($this->location_id)
//                     && $this->location_id === $target->location_id;

//             case self::TYPE_DEPARTMENT:
//                 return !is_null($this->location_id)
//                     && !is_null($this->department_id)
//                     && $this->location_id === $target->location_id
//                     && $this->department_id === $target->department_id;

//             case self::TYPE_CENTER:
//                 return !is_null($this->center_id)
//                     && $this->center_id === $target->center_id;

//             case self::TYPE_EMPLOYEE:
//                 return !is_null($this->subordinate_employee_id)
//                     && $this->subordinate_employee_id === $target->id;

//             default:
//                 return false;
//         }
//     }
// }
