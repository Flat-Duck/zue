@section('styles')
    @vite('resources/sass/print/injury-report.scss')
@endsection
@extends('layouts.app', ['page' => 'clinic'])
@section('title','عرض التقرير')

@section('content')
@php
    /** @var \App\Models\OccupationalInjuryReport $report */
    $r = $report;
@endphp

{{-- ===== Tabler Print Helpers ===== --}}

<div class="page-header print-hide mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h2 class="page-title">@lang('clinic.injury_report_number', ['id' => $r->id])</h2>
      <div class="text-muted mt-1">@lang('clinic.ready_only_view_optimized_for_print')</div>
    </div>
    <div class="col-auto ms-auto d-print-none">
      <a href="{{ route('injury-reports.edit', $r) }}" class="btn btn-warning">@lang('clinic.edit')</a>
      <button data-action="print" class="btn btn-primary">
        @svg('tabler-printer', 'icon') @lang('clinic.print')
      </button>
      <a href="{{ route('injury-reports.index') }}" class="btn btn-light">@lang('clinic.back')</a>
    </div>
  </div>
</div>

<div class="card read-only">
  <div class="card-header">
    <h3 class="card-title">@lang('clinic.t_1_responsible_and_mobilization')</h3>
  </div>
  <div class="card-body">

    {{-- Row 1 --}}
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">@lang('clinic.clinic')</label>
        <input class="form-control" value="{{ $r->clinic }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">@lang('clinic.location')</label>
        <input class="form-control" value="{{ $r->location }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">@lang('clinic.department')</label>
        <input class="form-control" value="{{ $r->department }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">@lang('clinic.section')</label>
        <input class="form-control" value="{{ $r->section }}" readonly>
      </div>

      <div class="col-md-3">
        <label class="form-label">@lang('clinic.company')</label>
        <input class="form-control" value="{{ $r->company }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">@lang('clinic.co_no')</label>
        <input class="form-control" value="{{ $r->company_co_no }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">@lang('clinic.case_no')</label>
        <input class="form-control" value="{{ $r->case_no }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">@lang('clinic.job_title')</label>
        <input class="form-control" value="{{ $r->job_title }}" readonly>
      </div>

      <div class="col-md-4">
        <label class="form-label">@lang('clinic.injured_patient_name')</label>
        <input class="form-control" value="{{ $r->injured_name }}" readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label">@lang('clinic.nationality')</label>
        <input class="form-control" value="{{ $r->nationality }}" readonly>
      </div>
      <div class="col-md-2">
        <label class="form-label">@lang('clinic.date')</label>
        <input class="form-control" value="{{ optional($r->incident_date)->format('d-m-Y') }}" readonly>
      </div>
      <div class="col-md-2">
        <label class="form-label">@lang('clinic.time')</label>
        <input class="form-control" value="{{ $r->incident_time }}" readonly>
      </div>
    </div>

    <div class="mt-4 section-title">@lang('clinic.classification')</div>
    <div class="row g-3 align-items-center">
      <div class="col-md-2">
        <span class="form-label d-block muted">@lang('clinic.case_type')</span>
        <div class="d-flex gap-2 flex-wrap">
          <span class="badge {{ $r->classification==='fatal' ? 'bg-red' : 'bg-secondary' }}">@lang('clinic.fatal')</span>
          <span class="badge {{ $r->classification==='serious' ? 'bg-orange' : 'bg-secondary' }}">@lang('clinic.serious')</span>
          <span class="badge {{ $r->classification==='minor' ? 'bg-green' : 'bg-secondary' }}">@lang('clinic.minor')</span>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-check form-switch mb-0">
          <input class="form-check-input" type="checkbox" @checked($r->is_lost_time_case) disabled>
          <span class="form-check-label">@lang('clinic.is_lost_time_case')</span>
        </label>
      </div>
      <div class="col-md-3">
        <label class="form-label">@lang('clinic.total_days_lost')</label>
        <input class="form-control" value="{{ $r->total_days_lost }}" readonly>
      </div>
    </div>

    <div class="mt-4">
      <label class="form-label">@lang('clinic.injury_nature')</label>
      <textarea class="form-control" rows="2" readonly>{{ $r->injury_nature }}</textarea>
    </div>

    <div class="mt-3">
      <label class="form-label">@lang('clinic.case_of_injury')</label>
      <textarea class="form-control" rows="2" readonly>{{ $r->case_of_injury }}</textarea>
    </div>

    <div class="mt-4 section-title">@lang('clinic.reporting')</div>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="d-flex gap-4 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->reported_at_once) disabled>
            <span class="form-check-label">@lang('clinic.at_once')</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->reported_next_day) disabled>
            <span class="form-check-label">@lang('clinic.next_day')</span>
          </label>
          <div class="ms-2">
            <span class="form-label d-block muted">@lang('clinic.other_specify')</span>
            <input class="form-control" value="{{ $r->reported_other_specify }}" readonly>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <span class="form-label d-block muted">@lang('clinic.method_of_informing')</span>
        <div class="d-flex gap-4 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->method_by_tele) disabled>
            <span class="form-check-label">@lang('clinic.by_tele')</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->method_by_radio) disabled>
            <span class="form-check-label">@lang('clinic.by_radio')</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->method_in_person) disabled>
            <span class="form-check-label">@lang('clinic.in_person')</span>
          </label>
        </div>
      </div>
    </div>

    <div class="mt-4 section-title">@lang('clinic.medical_staff_response')</div>
    <div class="row g-3">
      <div class="col-md-6 d-flex gap-4 flex-wrap">
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->doctor_on_site) disabled>
          <span class="form-check-label">@lang('clinic.doctor_on_site')</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->doctor_out_of_site) disabled>
          <span class="form-check-label">@lang('clinic.doctor_out_of_site')</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->ambulance_dispatched) disabled>
          <span class="form-check-label">@lang('clinic.ambulance_dispatched')</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->company_vehicle_used) disabled>
          <span class="form-check-label">@lang('clinic.company_vehicle_used')</span>
        </label>
      </div>
      <div class="col-md-6 d-flex gap-4 flex-wrap">
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->bring_patient_to_clinic) disabled>
          <span class="form-check-label">@lang('clinic.bring_patient_to_clinic')</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->transport_doctor_to_scene) disabled>
          <span class="form-check-label">@lang('clinic.transport_doctor_to_scene')</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->twin_otter_dispatched) disabled>
          <span class="form-check-label">@lang('clinic.twin_otter_dispatched')</span>
        </label>
      </div>
    </div>

    <div class="mt-5">
      <h3 class="card-title mb-3">@lang('clinic.t_2_injured_patient_personal_records')</h3>
      <div class="row g-3">
        <div class="col-md-6 d-flex gap-4 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->occurred_during_working_hours) disabled>
            <span class="form-check-label">@lang('clinic.during_working_hrs')</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->occurred_outside_working_hours) disabled>
            <span class="form-check-label">@lang('clinic.outside_working_hrs')</span>
          </label>
        </div>
        <div class="col-md-6 d-flex gap-4 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->single_case) disabled>
            <span class="form-check-label">@lang('clinic.single_case')</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->multiple_case) disabled>
            <span class="form-check-label">@lang('clinic.multiple_case')</span>
          </label>
        </div>
        <div class="col-12">
          <label class="form-label">@lang('clinic.past_medical_history')</label>
          <textarea class="form-control" rows="2" readonly>{{ $r->past_medical_history ?: 'NONE' }}</textarea>
        </div>
      </div>
    </div>

    <div class="mt-5">
      <h3 class="card-title mb-3">@lang('clinic.t_3_injury_illness_diagnose_and_treatment')</h3>
      <div class="mb-3">
        <label class="form-label">@lang('clinic.injuries_illness_diagnose')</label>
        <textarea class="form-control" rows="2" readonly>{{ $r->injuries_diagnose }}</textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">@lang('clinic.medical_treatment')</label>
        <textarea class="form-control" rows="2" readonly>{{ $r->medical_treatment }}</textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">@lang('clinic.investigations')</label>
        <textarea class="form-control" rows="2" readonly>{{ $r->investigations }}</textarea>
      </div>

      <div class="row g-3">
        <div class="col-md-6 d-flex gap-4 flex-wrap">
          <span class="form-label muted">@lang('clinic.employee_injury_report')</span>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->employee_report_attached) disabled>
            <span class="form-check-label">@lang('clinic.attached')</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->employee_report_under_completion) disabled>
            <span class="form-check-label">@lang('clinic.under_completion_will_follow')</span>
          </label>
        </div>
        <div class="col-md-6 d-flex gap-4 flex-wrap">
          <span class="form-label muted">@lang('clinic.injured_patient_advised_to')</span>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->advised_light_duties) disabled>
            <span class="form-check-label">@lang('clinic.light_duties')</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->follow_up_on_site) disabled>
            <span class="form-check-label">@lang('clinic.follow_up_on_site')</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->referred_to_hospital) disabled>
            <span class="form-check-label">@lang('clinic.referred_to_hospital')</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->back_to_work) disabled>
            <span class="form-check-label">@lang('clinic.back_to_work')</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->exempt_from_wearing_ppe) disabled>
            <span class="form-check-label">@lang('clinic.exempt_from_wearing_ppe')</span>
          </label>
        </div>
        <div class="col-12">
          <label class="form-label">@lang('clinic.other_specify')</label>
          <input class="form-control" value="{{ $r->other_specify }}" readonly>
        </div>

        <div class="col-12">
          <label class="form-label">@lang('clinic.comments_observations')</label>
          <textarea class="form-control" rows="3" readonly>{{ $r->comments }}</textarea>
        </div>
      </div>
    </div>

    <div class="mt-5">
      <div class="section-title">@lang('clinic.distribution')</div>
      <p class="text-muted"> @lang('clinic.original_lp_mng_c_er_c_field_supt_coord_c') </p>
    </div>

    <div class="row g-3">
      <div class="col-md-5">
        <label class="form-label">@lang('clinic.medical_officer_name')</label>
        <input class="form-control" value="{{ $r->medical_officer_name }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">@lang('clinic.date')</label>
        <input class="form-control" value="{{ optional($r->medical_officer_signed_date)->format('d-m-Y') }}" readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label">@lang('clinic.signature_text_link')</label>
        <input class="form-control" value="{{ $r->medical_officer_signature }}" readonly>
      </div>
    </div>

  </div>

  <div class="card-footer d-print-none">
    <div class="d-flex gap-2">
      <a href="{{ route('injury-reports.index') }}" class="btn btn-light">@lang('clinic.back')</a>
      <a href="{{ route('injury-reports.edit',$r) }}" class="btn btn-warning">@lang('clinic.edit')</a>
      <button data-action="print" class="btn btn-primary">
        @svg('tabler-printer', 'icon') @lang('clinic.print')
      </button>
    </div>
  </div>
</div>
@endsection
