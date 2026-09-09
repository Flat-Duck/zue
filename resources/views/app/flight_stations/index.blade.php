@extends('layouts.app', ['page' => 'flight-stations'])
@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Flight stations</h3>
        <div class="col-auto ms-auto d-print-none">
            @can('create', App\Models\FlightStation::class)
                <a href="{{ route('flight-stations.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i> New station
                </a>
            @endcan
        </div>
    </div>

    @if ($errors->has('station'))
        <div class="alert alert-danger m-3">{{ $errors->first('station') }}</div>
    @endif

    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Arabic name</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stations as $station)
                    <tr wire:key="station-{{ $station->id }}">
                        <td>{{ $station->name }}</td>
                        <td dir="rtl">{{ $station->name_ar ?: '-' }}</td>
                        <td>{{ $station->code }}</td>
                        <td>
                            <span class="badge bg-{{ $station->is_field ? 'green' : 'blue' }}">
                                {{ $station->is_field ? 'Field' : 'City' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $station->is_active ? 'green' : 'secondary' }}">
                                {{ $station->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @can('update', $station)
                                <a href="{{ route('flight-stations.edit', $station) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="ti ti-edit"></i>
                                </a>
                            @endcan
                            @can('delete', $station)
                                <form method="POST" action="{{ route('flight-stations.destroy', $station) }}" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger ms-1"><i class="ti ti-trash"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">No stations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer d-flex align-items-center">
        {!! $stations->render() !!}
    </div>
</div>
@endsection
