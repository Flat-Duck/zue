<?php

namespace App\Models\Appraisals;

use Illuminate\Database\Eloquent\Model;

class AppraisalItem extends Model
{
    protected $fillable = ['key', 'default_section', 'default_label'];
}
