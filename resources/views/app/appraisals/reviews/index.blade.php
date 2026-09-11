@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">@lang('appraisals.my_appraisals_as_appraiser')</h2>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('appraisals.reviews.create') }}" class="btn btn-primary"> @lang('appraisals.create_new_appraisal') </a>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>@lang('appraisals.employee')</th>
                            <th>@lang('appraisals.period')</th>
                            <th>@lang('appraisals.status')</th>
                            <th>@lang('appraisals.total')</th>
                            <th>@lang('appraisals.percentage')</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $r)
                            <tr>
                                <td class="fw-bold">{{ $r->employee->name ?? ('#' . $r->employee_id) }}</td>
                                <td>{{ $r->period->label }}</td>
                                <td><span class="badge bg-secondary">{{ strtoupper($r->status) }}</span></td>
                                <td>{{ $r->total_score ?? '-' }} / {{ $r->max_score ?? '-' }}</td>
                                <td>{{ $r->percentage ?? '-' }}</td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary"
                                        href="{{ route('appraisals.reviews.edit', $r->id) }}"> @lang('appraisals.open') </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">@lang('appraisals.no_appraisals')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection