<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicApointment extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'date',
        'diagnosis',
        'prescription',
    ];

}
