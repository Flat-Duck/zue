@php $editing = isset($flight) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="type" label="Type">
            @php $selected = old('type', ($editing ? $flight->type : '')) @endphp
            <option value="Air" {{ $selected == 'Air' ? 'selected' : '' }}>Air</option>
            <option value="Ground" {{ $selected == 'Ground' ? 'selected' : '' }}>Ground</option>
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date name="date" label="Date"
            value="{{ old('date', ($editing ? optional($flight->date)->format('Y-m-d') : '')) }}"
            max="255"></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.time name="time" label="Time" :value="old('time', ($editing ? $flight->time : ''))"
            placeholder="Time"></x-inputs.time>
    </x-inputs.group>

    {{-- The plane sets the seat limit for every leg of this flight. --}}
    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="plane_id" label="Plane" required>
            <option value="">@lang('crud.common.select')</option>
            @php $selectedPlane = old('plane_id', ($editing ? $flight->plane_id : '')) @endphp
            @foreach ($planes as $id => $planeLabel)
                <option value="{{ $id }}" {{ (string) $selectedPlane === (string) $id ? 'selected' : '' }}>
                    {{ $planeLabel }}
                </option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    {{-- The route decides the legs, and each leg its own coming/leaving list. --}}
    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="flight_route_id" label="Route" required>
            <option value="">@lang('crud.common.select')</option>
            @php $selectedRoute = old('flight_route_id', ($editing ? $flight->flight_route_id : '')) @endphp
            @foreach ($flightRoutes as $id => $routeName)
                <option value="{{ $id }}" {{ (string) $selectedRoute === (string) $id ? 'selected' : '' }}>
                    {{ $routeName }}
                </option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    @if ($editing && $flight->legs()->whereHas('bookings')->exists())
        <div class="col-sm-12">
            <div class="alert alert-info mb-0">
                This flight already has booked travellers, so its route can no longer be changed.
            </div>
        </div>
    @endif
</div>
