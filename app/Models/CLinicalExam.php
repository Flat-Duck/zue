<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'number',
        'company',
        'nationality',
        'diagnosis',
        'prescription',
    ];
}
