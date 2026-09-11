@extends('layouts.app', ['page' => 'flight-routes'])
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">@lang('flights.flight_routes')</h3>
        <div class="col-auto ms-auto d-print-none">
            @can('create', App\Models\FlightRoute::class)
                <a href="{{ route('flight-routes.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i> @lang('flights.new_route') </a>
            @endcan
        </div>
    </div>

    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>@lang('flights.name')</th>
                    <th>@lang('flights.legs')</th>
                    <th>@lang('flights.flights')</th>
                    <th>@lang('flights.status')</th>
                    <th class="text-end">@lang('flights.actions')</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($routes as $route)
                    <tr>
                        <td>{{ $route->name }}</td>
                        <td>
                            @foreach ($route->legs as $leg)
                                <span class="badge bg-{{ $leg->direction === 'coming' ? 'green' : 'blue' }} me-1">
                                    {{ $leg->fromStation?->code }} → {{ $leg->toStation?->code }}
                                </span>
                            @endforeach
                        </td>
                        <td>{{ $route->flights_count }}</td>
                        <td>
                            <span class="badge bg-{{ $route->is_active ? 'green' : 'secondary' }}">
                                {{ $route->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('update', $route)
                                <a href="{{ route('flight-routes.edit', $route) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-edit"></i>
                                </a>
                            @endcan
                            @can('delete', $route)
                                <form method="POST" action="{{ route('flight-routes.destroy', $route) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger ms-1"><i class="ti ti-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">@lang('flights.no_routes_yet')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex align-items-center">
        {!! $routes->render() !!}
    </div>
</div>
@endsection
