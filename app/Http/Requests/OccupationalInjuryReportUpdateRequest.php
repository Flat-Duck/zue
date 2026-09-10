<?php

namespace App\Http\Requests;

class OccupationalInjuryReportUpdateRequest extends OccupationalInjuryReportStoreRequest
{
    // نكرر من Store (نرثه) لأن نفس الـ rules/prepareForValidation مناسبة
}
