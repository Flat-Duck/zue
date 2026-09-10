<?php

namespace App\Models\Appraisals;

use App\Models\Employee;
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
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<AppraisalPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AppraisalPeriod::class, 'appraisal_period_id');
    }

    /**
     * @return BelongsTo<AppraisalFormVersion, $this>
     */
    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(AppraisalFormVersion::class, 'appraisal_form_version_id');
    }

    /**
     * @return HasMany<AppraisalReviewScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(AppraisalReviewScore::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function appraiser(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'appraiser_id');
    }
}
