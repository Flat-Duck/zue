<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeSheetApprovalStep extends Model
{
    use HasFactory;

    protected $table = 'timesheet_approval_steps';

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'flow_id',
        'step_order',
        'step_key',
        'approved_by_employee_id',
        'approved_at',
        'meta',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'meta' => 'array',
    ];

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    /**
     * @return BelongsTo<ApprovalFlow, $this>
     */
    public function flow(): BelongsTo
    {
        return $this->belongsTo(ApprovalFlow::class, 'flow_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function approverEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_employee_id');
    }
}
