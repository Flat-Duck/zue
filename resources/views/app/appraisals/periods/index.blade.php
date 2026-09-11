@extends('layouts.app', ['page' => 'appraisals'])

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">@lang('appraisals.appraisal_periods')</h3>
            <div class="card-actions">
                <a href="{{ route('appraisals.periods.create') }}" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg> @lang('appraisals.new_period') </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap datatable">
                <thead>
                    <tr>
                        <th>@lang('appraisals.year')</th>
                        <th>@lang('appraisals.type')</th>
                        <th>@lang('appraisals.quarter')</th>
                        <th>@lang('appraisals.window')</th>
                        <th>@lang('appraisals.status')</th>
                        <th>@lang('appraisals.actions')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($periods as $period)
                                    <tr>
                                        <td>{{ $period->year }}</td>
                                        <td>
                                            <span class="badge {{ $period->type === 'yearly' ? 'bg-purple-lt' : 'bg-blue-lt' }}">
                                                {{ ucfirst($period->type) }}
                                            </span>
                                        </td>
                                        <td>{{ $period->quarter ?? '-' }}</td>
                                        <td>
                                            {{ $period->window_open_from->format('M d, Y') }} -
                                            {{ $period->window_open_to->format('M d, Y') }}
                                        </td>
                                        <td>
                                            <span class="badge {{ match ($period->status) {
                            'open' => 'bg-success',
                            'closed' => 'bg-secondary',
                            'locked' => 'bg-danger',
                            default => 'bg-warning'
                        } }}">
                                                {{ ucfirst($period->status) }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('appraisals.periods.edit', $period) }}"
                                                class="btn btn-sm btn-outline-primary">@lang('appraisals.edit')</a>
                                            @if($period->status !== 'open')
                                                <form action="{{ route('appraisals.periods.destroy', $period) }}" method="POST" class="d-inline"
                                                    data-confirm="{{ __('appraisals.confirm_generic') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">@lang('appraisals.delete')</button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex align-items-center">
            {{ $periods->links() }}
        </div>
    </div>
@endsection