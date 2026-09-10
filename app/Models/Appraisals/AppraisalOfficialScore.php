<?php

namespace App\Models\Appraisals;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppraisalOfficialScore extends Model
{
    protected $fillable = ['appraisals_official_id', 'form_version_item_id', 'avg_score', 'score_override'];

    /**
     * @return BelongsTo<AppraisalOfficial, $this>
     */
    public function official(): BelongsTo
    {
        return $this->belongsTo(AppraisalOfficial::class, 'appraisals_official_id');
    }

    /**
     * @return BelongsTo<AppraisalFormVersionItem, $this>
     */
    public function formVersionItem(): BelongsTo
    {
        return $this->belongsTo(AppraisalFormVersionItem::class, 'form_version_item_id');
    }
}
