<?php

namespace App\Models\Appraisals;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalReviewScore extends Model
{
    protected $fillable = ['appraisal_review_id', 'form_version_item_id', 'score'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(AppraisalReview::class, 'appraisal_review_id');
    }

    public function formVersionItem(): BelongsTo
    {
        return $this->belongsTo(AppraisalFormVersionItem::class, 'form_version_item_id');
    }
}
