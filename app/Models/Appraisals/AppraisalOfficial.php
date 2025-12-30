<?php

namespace App\Models\Appraisals;

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
        'finalized_by'
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'finalized_at' => 'datetime',
    ];

    public function scores(): HasMany
    {
        return $this->hasMany(AppraisalOfficialScore::class, 'appraisals_official_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(AppraisalPeriod::class, 'appraisal_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }
}
