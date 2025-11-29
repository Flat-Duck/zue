<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OccupationalInjuryReportStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // حقول Boolean اللي في الفورم
    protected function booleanFields(): array
    {
        return [
            'is_lost_time_case',
            'reported_at_once','reported_next_day',
            'method_by_tele','method_by_radio','method_in_person',
            'doctor_on_site','doctor_out_of_site','ambulance_dispatched','company_vehicle_used',
            'bring_patient_to_clinic','transport_doctor_to_scene','twin_otter_dispatched',
            'occurred_during_working_hours','occurred_outside_working_hours','single_case','multiple_case',
            'employee_report_attached','employee_report_under_completion',
            'advised_light_duties','follow_up_on_site','referred_to_hospital','back_to_work',
            'exempt_from_wearing_ppe',
        ];
    }

    protected function prepareForValidation(): void
    {
        // أي checkbox مش مُرسل نخليه false
        $toMerge = [];
        foreach ($this->booleanFields() as $field) {
            $toMerge[$field] = $this->has($field) ? (bool) $this->boolean($field) : false;
        }

        $this->merge($toMerge);
    }

    public function rules(): array
    {
        return [
            // Case Identification
            'case_no'              => ['nullable','string','max:100'],
            'location'             => ['nullable','string','max:100'],
            'department'           => ['nullable','string','max:100'],
            'section'              => ['nullable','string','max:100'],
            'clinic'               => ['nullable','string','max:100'],
            'company'              => ['nullable','string','max:150'],
            'company_co_no'        => ['nullable','string','max:150'],
            'injured_name'         => ['required','string','max:255'],
            'nationality'          => ['nullable','string','max:100'],
            'job_title'            => ['nullable','string','max:150'],
            'incident_date'        => ['nullable','date'],
            'incident_time'        => ['nullable','string','max:50'],

            // Classification
            'classification'       => ['nullable','in:fatal,serious,minor'],
            'is_lost_time_case'    => ['boolean'],
            'total_days_lost'      => ['nullable','integer','min:0'],

            // Injury
            'injury_nature'        => ['nullable','string'],
            'case_of_injury'       => ['nullable','string'],

            // Reporting
            'reported_at_once'     => ['boolean'],
            'reported_next_day'    => ['boolean'],
            'reported_other_specify'=> ['nullable','string','max:255'],

            // Method informing
            'method_by_tele'       => ['boolean'],
            'method_by_radio'      => ['boolean'],
            'method_in_person'     => ['boolean'],

            // Medical response
            'doctor_on_site'           => ['boolean'],
            'doctor_out_of_site'       => ['boolean'],
            'ambulance_dispatched'     => ['boolean'],
            'company_vehicle_used'     => ['boolean'],
            'bring_patient_to_clinic'  => ['boolean'],
            'transport_doctor_to_scene'=> ['boolean'],
            'twin_otter_dispatched'    => ['boolean'],

            // Circumstances
            'occurred_during_working_hours' => ['boolean'],
            'occurred_outside_working_hours'=> ['boolean'],
            'single_case'                   => ['boolean'],
            'multiple_case'                 => ['boolean'],
            'past_medical_history'          => ['nullable','string'],

            // Diagnosis & treatment
            'injuries_diagnose'     => ['nullable','string'],
            'medical_treatment'     => ['nullable','string'],
            'investigations'        => ['nullable','string'],

            // Advice / report
            'employee_report_attached'        => ['boolean'],
            'employee_report_under_completion'=> ['boolean'],
            'advised_light_duties'            => ['boolean'],
            'follow_up_on_site'               => ['boolean'],
            'referred_to_hospital'            => ['boolean'],
            'back_to_work'                    => ['boolean'],
            'exempt_from_wearing_ppe'         => ['boolean'],
            'other_specify'                   => ['nullable','string','max:255'],

            // Comments & signature
            'comments'                 => ['nullable','string'],
            'medical_officer_name'     => ['nullable','string','max:150'],
            'medical_officer_signature'=> ['nullable','string','max:255'],
            'medical_officer_signed_date' => ['nullable','date'],
        ];
    }
}
