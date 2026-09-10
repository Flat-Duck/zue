<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScopePolicyActor extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_id',
        'actor_employee_id',
        'can_fill',
        'can_approve',
        'can_revise',
        'role_hint',
    ];

    protected $casts = [
        'can_fill' => 'bool',
        'can_approve' => 'bool',
        'can_revise' => 'bool',
    ];

    /**
     * @return BelongsTo<ScopePolicy, $this>
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(ScopePolicy::class, 'policy_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function actorEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'actor_employee_id');
    }
}
