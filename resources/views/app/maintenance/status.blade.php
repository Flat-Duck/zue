@extends('layouts.app', ['page' => 'status'])

@section('content')
<div class="page-header d-print-none mb-3">
    <div class="row g-2 align-items-center">
        <div class="col">
            <h2 class="page-title">@lang('maintenance.status_title')</h2>
            <div class="text-secondary mt-1">@lang('maintenance.status_subtitle')</div>
        </div>
        <div class="col-auto ms-auto">
            <a href="{{ route('maintenance.status') }}" class="btn btn-outline-secondary">
                <i class="ti ti-refresh"></i>
                @lang('crud.common.refresh')
            </a>
        </div>
    </div>
</div>

<div class="alert {{ $healthy ? 'alert-success' : 'alert-danger' }}" role="status">
    <i class="ti {{ $healthy ? 'ti-circle-check' : 'ti-alert-triangle' }} me-1"></i>
    {{ $healthy ? __('maintenance.status_all_well') : __('maintenance.status_something_failing') }}
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>@lang('maintenance.status_check')</th>
                    <th>@lang('maintenance.status_state')</th>
                    <th>@lang('maintenance.status_detail')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($checks as $check)
                    @php
                        [$badge, $label] = match ($check->status) {
                            'ok' => ['bg-green-lt', __('maintenance.status_ok')],
                            'warning' => ['bg-yellow-lt', __('maintenance.status_warning')],
                            default => ['bg-red-lt', __('maintenance.status_failing')],
                        };
                    @endphp
                    <tr>
                        <td>@lang('maintenance.check_'.$check->key)</td>
                        <td><span class="badge {{ $badge }}">{{ $label }}</span></td>
                        <td class="text-secondary">{{ $check->detail }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-footer text-secondary small">
        @lang('maintenance.status_monitor_hint', ['url' => route('health')])
    </div>
</div>
@endsection
