<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OccupationalInjuryReportUpdateRequest extends OccupationalInjuryReportStoreRequest
{
    // نكرر من Store (نرثه) لأن نفس الـ rules/prepareForValidation مناسبة
}
