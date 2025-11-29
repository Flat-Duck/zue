<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OccupationalInjuryReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_no','location','department','section','clinic','company','company_co_no',
        'injured_name','nationality','job_title','incident_date','incident_time',
        'classification','is_lost_time_case','total_days_lost',
        'injury_nature','case_of_injury',
        'reported_at_once','reported_next_day','reported_other_specify',
        'method_by_tele','method_by_radio','method_in_person',
        'doctor_on_site','doctor_out_of_site','ambulance_dispatched','company_vehicle_used',
        'bring_patient_to_clinic','transport_doctor_to_scene','twin_otter_dispatched',
        'occurred_during_working_hours','occurred_outside_working_hours','single_case','multiple_case',
        'past_medical_history',
        'injuries_diagnose','medical_treatment','investigations',
        'employee_report_attached','employee_report_under_completion',
        'advised_light_duties','follow_up_on_site','referred_to_hospital','back_to_work',
        'exempt_from_wearing_ppe','other_specify',
        'comments','medical_officer_name','medical_officer_signature','medical_officer_signed_date',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'medical_officer_signed_date' => 'date',
        'is_lost_time_case' => 'boolean',
        'reported_at_once' => 'boolean',
        'reported_next_day' => 'boolean',
        'method_by_tele' => 'boolean',
        'method_by_radio' => 'boolean',
        'method_in_person' => 'boolean',
        'doctor_on_site' => 'boolean',
        'doctor_out_of_site' => 'boolean',
        'ambulance_dispatched' => 'boolean',
        'company_vehicle_used' => 'boolean',
        'bring_patient_to_clinic' => 'boolean',
        'transport_doctor_to_scene' => 'boolean',
        'twin_otter_dispatched' => 'boolean',
        'occurred_during_working_hours' => 'boolean',
        'occurred_outside_working_hours' => 'boolean',
        'single_case' => 'boolean',
        'multiple_case' => 'boolean',
        'employee_report_attached' => 'boolean',
        'employee_report_under_completion' => 'boolean',
        'advised_light_duties' => 'boolean',
        'follow_up_on_site' => 'boolean',
        'referred_to_hospital' => 'boolean',
        'back_to_work' => 'boolean',
        'exempt_from_wearing_ppe' => 'boolean',
    ];
}
