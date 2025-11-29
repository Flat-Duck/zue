@extends('layouts.app', ['page' => 'clinic'])
@section('title','عرض التقرير')

@section('content')
@php
    /** @var \App\Models\OccupationalInjuryReport $report */
    $r = $report;
@endphp

{{-- ===== Tabler Print Helpers ===== --}}
<style>
  /* لو عندك Tabler مركّب عبر Vite/CDN خلاص؛
     هنا بس تحسينات للطباعة والقراءة فقط */
  .read-only input.form-control[readonly],
  .read-only textarea.form-control[readonly]{
      background: #f8f9fa;
      color:#111;
      border-color:#e9ecef;
  }
  .read-only .form-check-input:disabled{
      border-color:#cbd5e1;
      background:#fff;
  }
  .section-title{
      font-weight:700; font-size:1rem; color:#0f172a;
      border-bottom:1px solid #e9ecef; padding-bottom:.4rem; margin-bottom:1rem;
  }
  .muted{ color:#6b7280; }
  @media print{
      nav, .navbar, .btn, .card-footer, .print-hide{ display:none !important; }
      body{ background:#fff; }
      .card{ box-shadow:none !important; border:0 !important; }
      .container{ max-width: 100% !important; }
      @page { size: A4; margin: 12mm; }
  }
</style>

<div class="page-header print-hide mb-3">
  <div class="row align-items-center">
    <div class="col">
      <h2 class="page-title">Injury Report #{{ $r->id }}</h2>
      <div class="text-muted mt-1">Ready-only view · optimized for print</div>
    </div>
    <div class="col-auto ms-auto d-print-none">
      <a href="{{ route('injury-reports.edit', $r) }}" class="btn btn-warning">Edit</a>
      <button onclick="window.print()" class="btn btn-primary">
        @svg('tabler-printer', 'icon') Print
      </button>
      <a href="{{ route('injury-reports.index') }}" class="btn btn-light">Back</a>
    </div>
  </div>
</div>

<div class="card read-only">
  <div class="card-header">
    <h3 class="card-title">1. Responsible and Mobilization</h3>
  </div>
  <div class="card-body">

    {{-- Row 1 --}}
    <div class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Clinic</label>
        <input class="form-control" value="{{ $r->clinic }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Location</label>
        <input class="form-control" value="{{ $r->location }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Department</label>
        <input class="form-control" value="{{ $r->department }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Section</label>
        <input class="form-control" value="{{ $r->section }}" readonly>
      </div>

      <div class="col-md-3">
        <label class="form-label">Company</label>
        <input class="form-control" value="{{ $r->company }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Co. No</label>
        <input class="form-control" value="{{ $r->company_co_no }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Case No.</label>
        <input class="form-control" value="{{ $r->case_no }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Job Title</label>
        <input class="form-control" value="{{ $r->job_title }}" readonly>
      </div>

      <div class="col-md-4">
        <label class="form-label">Injured / Patient Name</label>
        <input class="form-control" value="{{ $r->injured_name }}" readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label">Nationality</label>
        <input class="form-control" value="{{ $r->nationality }}" readonly>
      </div>
      <div class="col-md-2">
        <label class="form-label">Date</label>
        <input class="form-control" value="{{ optional($r->incident_date)->format('d-m-Y') }}" readonly>
      </div>
      <div class="col-md-2">
        <label class="form-label">Time</label>
        <input class="form-control" value="{{ $r->incident_time }}" readonly>
      </div>
    </div>

    <div class="mt-4 section-title">Classification</div>
    <div class="row g-3 align-items-center">
      <div class="col-md-2">
        <span class="form-label d-block muted">Case type</span>
        <div class="d-flex gap-2 flex-wrap">
          <span class="badge {{ $r->classification==='fatal' ? 'bg-red' : 'bg-secondary' }}">Fatal</span>
          <span class="badge {{ $r->classification==='serious' ? 'bg-orange' : 'bg-secondary' }}">Serious</span>
          <span class="badge {{ $r->classification==='minor' ? 'bg-green' : 'bg-secondary' }}">Minor</span>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-check form-switch mb-0">
          <input class="form-check-input" type="checkbox" @checked($r->is_lost_time_case) disabled>
          <span class="form-check-label">Is Lost Time Case</span>
        </label>
      </div>
      <div class="col-md-3">
        <label class="form-label">Total days lost</label>
        <input class="form-control" value="{{ $r->total_days_lost }}" readonly>
      </div>
    </div>

    <div class="mt-4">
      <label class="form-label">Injury Nature</label>
      <textarea class="form-control" rows="2" readonly>{{ $r->injury_nature }}</textarea>
    </div>

    <div class="mt-3">
      <label class="form-label">Case of Injury</label>
      <textarea class="form-control" rows="2" readonly>{{ $r->case_of_injury }}</textarea>
    </div>

    <div class="mt-4 section-title">Reporting</div>
    <div class="row g-3">
      <div class="col-md-6">
        <div class="d-flex gap-4 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->reported_at_once) disabled>
            <span class="form-check-label">At once</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->reported_next_day) disabled>
            <span class="form-check-label">Next day</span>
          </label>
          <div class="ms-2">
            <span class="form-label d-block muted">Other specify</span>
            <input class="form-control" value="{{ $r->reported_other_specify }}" readonly>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <span class="form-label d-block muted">Method of informing</span>
        <div class="d-flex gap-4 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->method_by_tele) disabled>
            <span class="form-check-label">By tele</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->method_by_radio) disabled>
            <span class="form-check-label">By radio</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->method_in_person) disabled>
            <span class="form-check-label">In person</span>
          </label>
        </div>
      </div>
    </div>

    <div class="mt-4 section-title">Medical Staff / Response</div>
    <div class="row g-3">
      <div class="col-md-6 d-flex gap-4 flex-wrap">
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->doctor_on_site) disabled>
          <span class="form-check-label">Doctor on site</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->doctor_out_of_site) disabled>
          <span class="form-check-label">Doctor out of site</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->ambulance_dispatched) disabled>
          <span class="form-check-label">Ambulance dispatched</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->company_vehicle_used) disabled>
          <span class="form-check-label">Company vehicle used</span>
        </label>
      </div>
      <div class="col-md-6 d-flex gap-4 flex-wrap">
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->bring_patient_to_clinic) disabled>
          <span class="form-check-label">Bring patient to clinic</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->transport_doctor_to_scene) disabled>
          <span class="form-check-label">Transport doctor to scene</span>
        </label>
        <label class="form-check">
          <input class="form-check-input" type="checkbox" @checked($r->twin_otter_dispatched) disabled>
          <span class="form-check-label">Twin Otter dispatched</span>
        </label>
      </div>
    </div>

    <div class="mt-5">
      <h3 class="card-title mb-3">2. Injured/Patient Personal Records</h3>
      <div class="row g-3">
        <div class="col-md-6 d-flex gap-4 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->occurred_during_working_hours) disabled>
            <span class="form-check-label">During working hrs.</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->occurred_outside_working_hours) disabled>
            <span class="form-check-label">Outside working hrs.</span>
          </label>
        </div>
        <div class="col-md-6 d-flex gap-4 flex-wrap">
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->single_case) disabled>
            <span class="form-check-label">Single case</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->multiple_case) disabled>
            <span class="form-check-label">Multiple case</span>
          </label>
        </div>
        <div class="col-12">
          <label class="form-label">Past medical history</label>
          <textarea class="form-control" rows="2" readonly>{{ $r->past_medical_history ?: 'NONE' }}</textarea>
        </div>
      </div>
    </div>

    <div class="mt-5">
      <h3 class="card-title mb-3">3. Injury/Illness Diagnose and Treatment</h3>
      <div class="mb-3">
        <label class="form-label">Injuries/illness diagnose</label>
        <textarea class="form-control" rows="2" readonly>{{ $r->injuries_diagnose }}</textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Medical treatment</label>
        <textarea class="form-control" rows="2" readonly>{{ $r->medical_treatment }}</textarea>
      </div>
      <div class="mb-3">
        <label class="form-label">Investigations</label>
        <textarea class="form-control" rows="2" readonly>{{ $r->investigations }}</textarea>
      </div>

      <div class="row g-3">
        <div class="col-md-6 d-flex gap-4 flex-wrap">
          <span class="form-label muted">Employee injury report</span>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->employee_report_attached) disabled>
            <span class="form-check-label">Attached</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->employee_report_under_completion) disabled>
            <span class="form-check-label">Under completion & will follow</span>
          </label>
        </div>
        <div class="col-md-6 d-flex gap-4 flex-wrap">
          <span class="form-label muted">Injured/Patient advised to</span>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->advised_light_duties) disabled>
            <span class="form-check-label">Light duties</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->follow_up_on_site) disabled>
            <span class="form-check-label">Follow up on site</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->referred_to_hospital) disabled>
            <span class="form-check-label">Referred to hospital</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->back_to_work) disabled>
            <span class="form-check-label">Back to work</span>
          </label>
          <label class="form-check">
            <input class="form-check-input" type="checkbox" @checked($r->exempt_from_wearing_ppe) disabled>
            <span class="form-check-label">Exempt from wearing PPE</span>
          </label>
        </div>
        <div class="col-12">
          <label class="form-label">Other specify</label>
          <input class="form-control" value="{{ $r->other_specify }}" readonly>
        </div>

        <div class="col-12">
          <label class="form-label">Comments & observations</label>
          <textarea class="form-control" rows="3" readonly>{{ $r->comments }}</textarea>
        </div>
      </div>
    </div>

    <div class="mt-5">
      <div class="section-title">Distribution</div>
      <p class="text-muted">
        Original LP mng · c/ER · c/Field Supt/Coord · c/medical · c/Field Medical Officer — NB: Attached separate sheet if required.
      </p>
    </div>

    <div class="row g-3">
      <div class="col-md-5">
        <label class="form-label">Medical Officer name</label>
        <input class="form-control" value="{{ $r->medical_officer_name }}" readonly>
      </div>
      <div class="col-md-3">
        <label class="form-label">Date</label>
        <input class="form-control" value="{{ optional($r->medical_officer_signed_date)->format('d-m-Y') }}" readonly>
      </div>
      <div class="col-md-4">
        <label class="form-label">Signature (text / link)</label>
        <input class="form-control" value="{{ $r->medical_officer_signature }}" readonly>
      </div>
    </div>

  </div>

  <div class="card-footer d-print-none">
    <div class="d-flex gap-2">
      <a href="{{ route('injury-reports.index') }}" class="btn btn-light">Back</a>
      <a href="{{ route('injury-reports.edit',$r) }}" class="btn btn-warning">Edit</a>
      <button onclick="window.print()" class="btn btn-primary">
        @svg('tabler-printer', 'icon') Print
      </button>
    </div>
  </div>
</div>
@endsection
