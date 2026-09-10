<?php

namespace App\Models\Appraisals;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppraisalOfficial extends Model
{
    protected $table = 'appraisals_official';

    protected $fillable = [
        'appraisal_period_id',
        'employee_id',
        'appraisal_form_version_id',
        'reviews_count',
        'total_score',
        'max_score',
        'percentage',
        'grade',
        'finalized_at',
        'finalized_by',
        'employee_signed_at',
        'manager_user_id',
        'manager_signed_at',
        'hr_user_id',
        'hr_signed_at',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'finalized_at' => 'datetime',
        'employee_signed_at' => 'datetime',
        'manager_signed_at' => 'datetime',
        'hr_signed_at' => 'datetime',
    ];

    /**
     * @return HasMany<AppraisalOfficialScore, $this>
     */
    public function scores(): HasMany
    {
        return $this->hasMany(AppraisalOfficialScore::class, 'appraisals_official_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function hr(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_user_id');
    }

    /**
     * @return BelongsTo<AppraisalPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(AppraisalPeriod::class, 'appraisal_period_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
