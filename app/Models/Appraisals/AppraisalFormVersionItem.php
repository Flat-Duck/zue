<?php

namespace App\Models\Appraisals;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalFormVersionItem extends Model
{
    protected $fillable = [
        'appraisal_form_version_id',
        'item_id',
        'section_override',
        'label_override',
        'max_score_override',
        'sort_order',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsTo<AppraisalFormVersion, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(AppraisalFormVersion::class, 'appraisal_form_version_id');
    }

    /**
     * @return BelongsTo<AppraisalItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(AppraisalItem::class, 'item_id');
    }

    // Resolved fields
    public function getResolvedSectionAttribute(): string
    {
        return $this->section_override ?: $this->item->default_section;
    }

    public function getResolvedLabelAttribute(): string
    {
        return $this->label_override ?: $this->item->default_label;
    }

    public function getResolvedMaxScoreAttribute(): int
    {
        return (int) ($this->max_score_override ?? 0);
    }
}
