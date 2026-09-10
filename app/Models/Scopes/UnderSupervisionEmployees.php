<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UnderSupervisionEmployees implements Scope
{
    private $center_id;

    private $department_id;

    private $location_id;

    private $management_case;

    public function __construct(?int $center, ?int $department, ?int $location)
    {
        $this->center_id = $center;
        $this->department_id = $department;
        $this->location_id = $location;

        if (auth()->user()->management_level() == 2) {
            $this->management_case = 2;
        } elseif (auth()->user()->management_level() == 3) {
            $this->management_case = 3;
        } elseif (auth()->user()->management_level() == 4) {
            $this->management_case = 4;
        }

    }

    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if ($this->management_case == 2) {
            $builder->where('location_id', $this->location_id)
                ->where('department_id', $this->department_id)
                ->where('employee_level', 1);
        } elseif ($this->management_case == 3) {
            $builder->where('location_id', $this->location_id)
                ->where('management_level', 3)
                ->where('employee_level', 2);
        } elseif ($this->management_case == 4) {
            // dd(auth()->user());
            $builder
                ->where('employee_level', '>=', 2)
                ->where('management_level', '=', 4);
        }
    }
}
