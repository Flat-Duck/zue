@extends('layouts.app', ['page' => 'clinic'])
@section('title', 'Employee Diagnosis')
@section('title','قائمة التقارير')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h4>@lang('clinic.reports_list')</h4>
  <a href="{{ route('injury-reports.create') }}" class="btn btn-primary">@lang('clinic.new_report')</a>
</div>
<table class="table table-striped">
  <thead>
    <tr>
      <th>#</th><th>@lang('clinic.injured_name')</th><th>@lang('clinic.date')</th><th>@lang('clinic.classification')</th><th></th>
    </tr>
  </thead>
  <tbody>
  @foreach($reports as $r)
    <tr>
      <td>{{ $r->id }}</td>
      <td>{{ $r->injured_name }}</td>
      <td>{{ optional($r->incident_date)->format('Y-m-d') }}</td>
      <td>{{ $r->classification }}</td>
      <td class="text-end">
        <a class="btn btn-sm btn-secondary" href="{{ route('injury-reports.show',$r) }}">@lang('clinic.view')</a>
        <a class="btn btn-sm btn-warning" href="{{ route('injury-reports.edit',$r) }}">@lang('clinic.edit')</a>
        <form action="{{ route('injury-reports.destroy',$r) }}" method="POST" class="d-inline">
          @csrf @method('DELETE')
          <button class="btn btn-sm btn-danger" data-confirm="{{ __('clinic.confirm_delete') }}">@lang('clinic.delete')</button>
        </form>
      </td>
    </tr>
  @endforeach
  </tbody>
</table>
{{ $reports->links() }}
@endsection
