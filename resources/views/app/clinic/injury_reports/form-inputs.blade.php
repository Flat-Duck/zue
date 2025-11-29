@php
  $r = $report ?? null;
@endphp

<div class="row g-3">
  <div class="col-md-3">
    <label class="form-label">رقم الحالة</label>
    <input name="case_no" class="form-control" value="{{ old('case_no', $r->case_no ?? '') }}">
  </div>
  <div class="col-md-3">
    <label class="form-label">الموقع</label>
    <input name="location" class="form-control" value="{{ old('location', $r->location ?? '') }}">
  </div>
  <div class="col-md-3">
    <label class="form-label">القسم</label>
    <input name="department" class="form-control" value="{{ old('department', $r->department ?? '') }}">
  </div>
  <div class="col-md-3">
    <label class="form-label">الشركة</label>
    <input name="company" class="form-control" value="{{ old('company', $r->company ?? '') }}">
  </div>

  <div class="col-md-4">
    <label class="form-label">اسم المصاب *</label>
    <input name="injured_name" class="form-control @error('injured_name') is-invalid @enderror"
           value="{{ old('injured_name', $r->injured_name ?? '') }}">
    @error('injured_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  <div class="col-md-2">
    <label class="form-label">الجنسية</label>
    <input name="nationality" class="form-control" value="{{ old('nationality', $r->nationality ?? '') }}">
  </div>
  <div class="col-md-3">
    <label class="form-label">المسمّى الوظيفي</label>
    <input name="job_title" class="form-control" value="{{ old('job_title', $r->job_title ?? '') }}">
  </div>
  <div class="col-md-2">
    <label class="form-label">تاريخ الحادث</label>
    <input type="date" name="incident_date" class="form-control"
           value="{{ old('incident_date', optional($r->incident_date ?? null)->format('Y-m-d')) }}">
  </div>
  <div class="col-md-2">
    <label class="form-label">وقت الحادث</label>
    <input name="incident_time" class="form-control" placeholder="3:am"
           value="{{ old('incident_time', $r->incident_time ?? '') }}">
  </div>

  <div class="col-md-3">
    <label class="form-label">التصنيف</label>
    <select name="classification" class="form-select">
      <option value="">—</option>
      @foreach(['fatal'=>'Fatal','serious'=>'Serious','minor'=>'Minor'] as $k=>$v)
        <option value="{{ $k }}" @selected(old('classification', $r->classification ?? '')==$k)>{{ $v }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-2 form-check mt-4">
    <input type="checkbox" class="form-check-input" id="lost" name="is_lost_time_case"
           value="1" @checked(old('is_lost_time_case', $r->is_lost_time_case ?? false))>
    <label class="form-check-label" for="lost">Lost Time Case</label>
  </div>
  <div class="col-md-2">
    <label class="form-label">أيام ضائعة</label>
    <input type="number" class="form-control" name="total_days_lost"
           value="{{ old('total_days_lost', $r->total_days_lost ?? '') }}">
  </div>

  <div class="col-12">
    <label class="form-label">طبيعة الإصابة</label>
    <textarea name="injury_nature" class="form-control" rows="2">{{ old('injury_nature', $r->injury_nature ?? '') }}</textarea>
  </div>
  <div class="col-12">
    <label class="form-label">كيفية/سبب الإصابة</label>
    <textarea name="case_of_injury" class="form-control" rows="2">{{ old('case_of_injury', $r->case_of_injury ?? '') }}</textarea>
  </div>

  <hr class="mt-3">

  <div class="col-12"><strong>الإبلاغ</strong></div>
  @foreach([
    'reported_at_once' => 'فوراً',
    'reported_next_day' => 'اليوم التالي',
  ] as $name=>$label)
    <div class="col-md-2 form-check">
      <input type="checkbox" class="form-check-input" id="{{ $name }}" name="{{ $name }}" value="1"
        @checked(old($name, $r->$name ?? false))>
      <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
    </div>
  @endforeach
  <div class="col-md-4">
    <label class="form-label">Other specify</label>
    <input name="reported_other_specify" class="form-control"
           value="{{ old('reported_other_specify', $r->reported_other_specify ?? '') }}">
  </div>

  <div class="col-12 mt-2"><small class="text-muted">طريقة الإبلاغ</small></div>
  @foreach([
    'method_by_tele' => 'By tele',
    'method_by_radio' => 'By radio',
    'method_in_person' => 'In person',
  ] as $name=>$label)
    <div class="col-md-2 form-check">
      <input type="checkbox" class="form-check-input" id="{{ $name }}" name="{{ $name }}" value="1"
        @checked(old($name, $r->$name ?? false))>
      <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
    </div>
  @endforeach

  <hr class="mt-3">
  <div class="col-12"><strong>الاستجابة الطبية</strong></div>
  @foreach([
    'doctor_on_site'=>'Doctor on site',
    'doctor_out_of_site'=>'Doctor out of site',
    'ambulance_dispatched'=>'Ambulance dispatched',
    'company_vehicle_used'=>'Company vehicle used',
    'bring_patient_to_clinic'=>'Bring injured/patient to clinic',
    'transport_doctor_to_scene'=>'Transport doctor to the scene',
    'twin_otter_dispatched'=>'Twin Otter dispatched',
  ] as $name=>$label)
    <div class="col-md-4 form-check">
      <input type="checkbox" class="form-check-input" id="{{ $name }}" name="{{ $name }}" value="1"
        @checked(old($name, $r->$name ?? false))>
      <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
    </div>
  @endforeach

  <hr class="mt-3">
  <div class="col-12"><strong>الظروف والسجل الطبي</strong></div>
  @foreach([
    'occurred_during_working_hours'=>'During working hrs.',
    'occurred_outside_working_hours'=>'Outside working hrs.',
    'single_case'=>'Single case',
    'multiple_case'=>'Multiple case',
  ] as $name=>$label)
    <div class="col-md-3 form-check">
      <input type="checkbox" class="form-check-input" id="{{ $name }}" name="{{ $name }}" value="1"
        @checked(old($name, $r->$name ?? false))>
      <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
    </div>
  @endforeach
  <div class="col-12">
    <label class="form-label">Past medical history</label>
    <textarea name="past_medical_history" class="form-control" rows="2">{{ old('past_medical_history', $r->past_medical_history ?? '') }}</textarea>
  </div>

  <hr class="mt-3">
  <div class="col-12"><strong>التشخيص والعلاج</strong></div>
  <div class="col-12">
    <label class="form-label">التشخيص</label>
    <textarea name="injuries_diagnose" class="form-control" rows="2">{{ old('injuries_diagnose', $r->injuries_diagnose ?? '') }}</textarea>
  </div>
  <div class="col-12">
    <label class="form-label">العلاج الطبي</label>
    <textarea name="medical_treatment" class="form-control" rows="2">{{ old('medical_treatment', $r->medical_treatment ?? '') }}</textarea>
  </div>
  <div class="col-12">
    <label class="form-label">الفحوصات/Investigation</label>
    <textarea name="investigations" class="form-control" rows="2">{{ old('investigations', $r->investigations ?? '') }}</textarea>
  </div>

  <hr class="mt-3">
  <div class="col-12"><strong>تقارير ونصيحة</strong></div>
  @foreach([
    'employee_report_attached'=>'Employee injury report: attached',
    'employee_report_under_completion'=>'under completion',
    'advised_light_duties'=>'Light duties',
    'follow_up_on_site'=>'Follow up on site',
    'referred_to_hospital'=>'Referred to hospital',
    'back_to_work'=>'Back to work',
    'exempt_from_wearing_ppe'=>'Exempt from wearing PPE',
  ] as $name=>$label)
    <div class="col-md-4 form-check">
      <input type="checkbox" class="form-check-input" id="{{ $name }}" name="{{ $name }}" value="1"
        @checked(old($name, $r->$name ?? false))>
      <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
    </div>
  @endforeach
  <div class="col-12">
    <label class="form-label">Other specify</label>
    <input name="other_specify" class="form-control" value="{{ old('other_specify', $r->other_specify ?? '') }}">
  </div>

  <div class="col-12">
    <label class="form-label">ملاحظات</label>
    <textarea name="comments" class="form-control" rows="3">{{ old('comments', $r->comments ?? '') }}</textarea>
  </div>

  <div class="col-md-4">
    <label class="form-label">اسم الطبيب</label>
    <input name="medical_officer_name" class="form-control" value="{{ old('medical_officer_name', $r->medical_officer_name ?? '') }}">
  </div>
  <div class="col-md-4">
    <label class="form-label">التوقيع (نص/رابط صورة)</label>
    <input name="medical_officer_signature" class="form-control" value="{{ old('medical_officer_signature', $r->medical_officer_signature ?? '') }}">
  </div>
  <div class="col-md-4">
    <label class="form-label">تاريخ التوقيع</label>
    <input type="date" name="medical_officer_signed_date" class="form-control"
           value="{{ old('medical_officer_signed_date', optional($r->medical_officer_signed_date ?? null)->format('Y-m-d')) }}">
  </div>
</div>
