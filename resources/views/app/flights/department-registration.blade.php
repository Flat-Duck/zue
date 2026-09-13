@extends('layouts.app', ['page' => 'flights'])

@section('content')
    @php
        $activeLegId = (int) request()->integer('leg', (int) ($legs->first()['id'] ?? 0));
    @endphp

    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">@lang('flights.department_registration')</h2>
                    <div class="text-muted mt-1">
                        {{ optional($flight->route)->name ?? __('flights.no_route_selected') }}
                        @if ($flight->plane)
                            — @lang('flights.seats_per_leg', ['plane' => $flight->plane->name, 'capacity' => $flight->plane->capacity])
                        @endif
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <a href="{{ route('flights.show', $flight) }}" class="btn btn-outline-secondary">
                        <i class="ti ti-arrow-back"></i>
                        @lang('crud.common.back')
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @if ($errors->has('registration'))
                <div class="alert alert-danger" role="alert">{{ $errors->first('registration') }}</div>
            @endif

            <div class="row row-cards mb-3">
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="subheader">@lang('flights.registration_status')</div>
                            <div class="h2 mb-0 {{ $flight->registrationIsOpen() ? 'text-success' : 'text-warning' }}">
                                {{ $flight->registrationIsOpen() ? __('flights.registration_open') : __('flights.registration_closed_short') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="subheader">@lang('flights.registration_context_quota')</div>
                            <div class="h2 mb-0">{{ $usedQuota }} / {{ $quota }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="subheader">@lang('flights.registration_opens_at')</div>
                            <div class="h3 mb-0">{{ $flight->registration_opens_at?->format('Y-m-d H:i') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="subheader">@lang('flights.registration_closes_at')</div>
                            <div class="h3 mb-0">{{ $flight->registration_closes_at?->format('Y-m-d H:i') ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                        @foreach ($legs as $leg)
                            <li class="nav-item" role="presentation">
                                <a href="#flight-leg-{{ $leg['id'] }}" class="nav-link {{ (int) $leg['id'] === $activeLegId ? 'active' : '' }}" data-bs-toggle="tab" aria-selected="{{ (int) $leg['id'] === $activeLegId ? 'true' : 'false' }}" role="tab">
                                    {{ $leg['label'] }}
                                    <span class="badge bg-blue-lt ms-2">{{ __('flights.' . $leg['direction']) }}</span>
                                    <span class="badge bg-green-lt ms-1">{{ $leg['remaining'] }} / {{ $leg['capacity'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">
                        @foreach ($legs as $leg)
                            <div class="tab-pane {{ (int) $leg['id'] === $activeLegId ? 'active show' : '' }}" id="flight-leg-{{ $leg['id'] }}" role="tabpanel">
                                <div class="row row-cards">
                                    <div class="col-lg-5">
                                        <form method="POST" action="{{ route('flights.department-registration.store', $flight) }}" class="card">
                                            @csrf
                                            <input type="hidden" name="flight_leg_id" value="{{ $leg['id'] }}">

                                            <div class="card-header">
                                                <h3 class="card-title">@lang('flights.add_department_employee')</h3>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">@lang('flights.leg')</label>
                                                    <div class="form-control-plaintext fw-bold">
                                                        {{ $leg['label'] }} — {{ __('flights.' . $leg['direction']) }}
                                                    </div>
                                                    <div class="form-hint">{{ $leg['remaining'] }} / {{ $leg['capacity'] }} @lang('flights.seats_available')</div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label" for="employee_id_{{ $leg['id'] }}">@lang('flights.employee')</label>
                                                    <select name="employee_id" id="employee_id_{{ $leg['id'] }}" class="form-select @error('employee_id') is-invalid @enderror" data-tomselect="select" required>
                                                        <option value="">@lang('flights.select')</option>
                                                        @foreach ($employees as $employee)
                                                            <option value="{{ $employee->id }}" @selected((string) old('employee_id') === (string) $employee->id)>
                                                                {{ $employee->english_name ?: $employee->arabic_name }} — {{ $employee->number }}
                                                                @if ($employee->location || $employee->department)
                                                                    ({{ collect([$employee->location?->name, $employee->department?->name, $employee->center?->name])->filter()->implode(' / ') }})
                                                                @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error('employee_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                    @error('flight_leg_id')
                                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                                    @enderror
                                                    <div class="form-hint">@lang('flights.dispatcher_context_employee_hint')</div>
                                                </div>
                                            </div>
                                            <div class="card-footer text-end">
                                                <button type="submit" class="btn btn-primary" @disabled(! $flight->registrationIsOpen() || $remainingQuota < 1 || $leg['remaining'] < 1)>
                                                    @lang('flights.register_employee')
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="col-lg-7">
                                        <div class="card">
                                            <div class="card-header">
                                                <h3 class="card-title">@lang('flights.registered_by_you')</h3>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-vcenter table-striped card-table">
                                                    <thead>
                                                        <tr>
                                                            <th>@lang('flights.number')</th>
                                                            <th>@lang('flights.employee')</th>
                                                            <th>@lang('flights.from')</th>
                                                            <th>@lang('flights.booking_status')</th>
                                                            <th class="text-end">@lang('flights.actions')</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($leg['registered'] as $booking)
                                                            @php($registeredEmployee = $booking->bookable)
                                                            <tr>
                                                                <td>{{ $registeredEmployee?->number ?? '-' }}</td>
                                                                <td>{{ $registeredEmployee?->english_name ?: $registeredEmployee?->arabic_name ?: '-' }}</td>
                                                                <td>{{ collect([$registeredEmployee?->location?->name, $registeredEmployee?->department?->name, $registeredEmployee?->center?->name])->filter()->implode(' / ') ?: '-' }}</td>
                                                                <td>
                                                                    <span class="badge {{ $booking->isConfirmed() ? 'bg-green-lt' : 'bg-yellow-lt' }}">
                                                                        {{ __('flights.' . $booking->status) }}
                                                                    </span>
                                                                </td>
                                                                <td class="text-end">
                                                                    <form method="POST" action="{{ route('flights.department-registration.destroy', [$flight, $booking]) }}" data-confirm="{{ __('flights.registration_cancel_confirm') }}">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="btn btn-icon btn-outline-danger" aria-label="@lang('flights.cancel_registration')">
                                                                            <i class="ti ti-x"></i>
                                                                        </button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center text-muted">@lang('flights.no_registered_employees_for_leg')</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
