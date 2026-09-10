<?php

namespace App\Models\Appraisals;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppraisalFormVersion extends Model
{
    protected $fillable = [
        'appraisal_form_id',
        'version',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<AppraisalForm, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(AppraisalForm::class, 'appraisal_form_id');
    }

    /**
     * @return HasMany<AppraisalFormVersionItem, $this>
     */
    public function versionItems(): HasMany
    {
        return $this->hasMany(AppraisalFormVersionItem::class)->orderBy('sort_order');
    }

    public function isLocked(): bool
    {
        return AppraisalReview::query()
            ->where('appraisal_form_version_id', $this->id)
            ->exists()
            || AppraisalOfficial::query()
                ->where('appraisal_form_version_id', $this->id)
                ->exists();
    }
}
