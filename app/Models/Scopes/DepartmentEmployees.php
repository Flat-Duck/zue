<?php

namespace App\Models\Scopes;

use App\Models\Center;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DepartmentEmployees implements Scope
{
    private $center_id;

    public function __construct(int $center)
    {
        $this->center_id = $center;
    }
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model) : void
    {
        $builder->where('center_id', $this->center_id);
    }
}
