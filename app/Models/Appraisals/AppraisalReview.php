<?php

namespace App\Models\Appraisals;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppraisalReview extends Model
{
    protected $fillable = [
        'appraisal_period_id',
        'employee_id',
        'appraiser_id',
        'appraisal_form_version_id',
        'status',
        'total_score',
        'max_score',
        'percentage'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(AppraisalPeriod::class, 'appraisal_period_id');
    }

    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(AppraisalFormVersion::class, 'appraisal_form_version_id');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(AppraisalReviewScore::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }

    public function appraiser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Employee::class, 'appraiser_id');
    }
}
