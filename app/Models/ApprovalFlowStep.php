<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalFlowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'flow_id',
        'step_order',
        'step_key',
        'required_role',
        'can_fill',
        'can_approve',
        'depends_on_step_order',
    ];

    protected $casts = [
        'can_fill' => 'bool',
        'can_approve' => 'bool',
    ];

    /**
     * @return BelongsTo<ApprovalFlow, $this>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }
}
