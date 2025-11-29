@extends('layouts.app', ['page' => 'clinic'])
@section('title', 'Employee Diagnosis')
@section('title','تقرير جديد')
@section('content')
<form action="{{ route('injury-reports.store') }}" method="POST" class="card card-body shadow-sm">
  @csrf
  @include('app.clinic.injury_reports.form-inputs')
  <div class="mt-3">
    <button class="btn btn-primary">حفظ</button>
    <a href="{{ route('injury-reports.index') }}" class="btn btn-light">رجوع</a>
  </div>
</form>
@endsection
