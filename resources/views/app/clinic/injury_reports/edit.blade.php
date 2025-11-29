@extends('layouts.app', ['page' => 'clinic'])
@section('title','تعديل تقرير')
@section('content')
<form action="{{ route('injury-reports.update',$report) }}" method="POST" class="card card-body shadow-sm">
  @csrf @method('PUT')
  @include('app.clinic.injury_reports.form-inputs', ['report'=>$report])
  <div class="mt-3">
    <button class="btn btn-primary">تحديث</button>
    <a href="{{ route('injury-reports.show',$report) }}" class="btn btn-light">إلغاء</a>
  </div>
</form>
@endsection
