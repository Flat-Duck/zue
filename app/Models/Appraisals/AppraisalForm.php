<?php

namespace App\Models\Appraisals;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppraisalForm extends Model
{
    protected $fillable = ['code', 'name_ar', 'is_active'];

    /**
     * @return HasMany<AppraisalFormVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(AppraisalFormVersion::class);
    }
}
