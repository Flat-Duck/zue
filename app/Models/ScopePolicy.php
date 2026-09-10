<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScopePolicy extends Model
{
    use HasFactory;

    public const MATCH_GLOBAL = 'global';

    public const MATCH_LOCATION = 'location';

    public const MATCH_DEPARTMENT = 'department';

    public const MATCH_CENTER = 'center';

    public const MATCH_EMPLOYEE = 'employee';

    protected $fillable = [
        'name',
        'context',
        'match_type',
        'location_id',
        'department_id',
        'center_id',
        'target_employee_ids',
        'priority',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'target_employee_ids' => 'array',
        'settings' => 'array',
        'is_active' => 'bool',
    ];

    /**
     * @return HasMany<ScopePolicyActor, $this>
     */
    public function actors(): HasMany
    {
        return $this->hasMany(ScopePolicyActor::class, 'policy_id');
    }
}
