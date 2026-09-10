<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalFlow extends Model
{
    use HasFactory;

    protected $fillable = [
        'context',
        'name',
        'is_active',
        'applies_to',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'applies_to' => 'array',
    ];

    /**
     * @return HasMany<ApprovalFlowStep, $this>
     */
    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalFlowStep::class, 'flow_id')->orderBy('step_order');
    }
}
