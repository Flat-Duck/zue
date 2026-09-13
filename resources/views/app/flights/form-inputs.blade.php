@php $editing = isset($flight) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="type" label="Type">
            @php $selected = old('type', ($editing ? $flight->type : '')) @endphp
            <option value="Air" {{ $selected == 'Air' ? 'selected' : '' }}>@lang('flights.air')</option>
            <option value="Ground" {{ $selected == 'Ground' ? 'selected' : '' }}>@lang('flights.ground')</option>
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date name="date" label="Date"
            value="{{ old('date', ($editing ? optional($flight->date)->format('Y-m-d') : '')) }}"
            max="255"></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.time name="time" label="Time" :value="old('time', ($editing ? $flight->time : ''))"
            placeholder="@lang('flights.time')"></x-inputs.time>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-md-6">
        <label class="form-label" for="registration_opens_at">@lang('flights.registration_opens_at')</label>
        <input
            type="datetime-local"
            name="registration_opens_at"
            id="registration_opens_at"
            class="form-control @error('registration_opens_at') is-invalid @enderror"
            value="{{ old('registration_opens_at', ($editing ? optional($flight->registration_opens_at)->format('Y-m-d\TH:i') : '')) }}"
        >
        @error('registration_opens_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </x-inputs.group>

    <x-inputs.group class="col-sm-12 col-md-6">
        <label class="form-label" for="registration_closes_at">@lang('flights.registration_closes_at')</label>
        <input
            type="datetime-local"
            name="registration_closes_at"
            id="registration_closes_at"
            class="form-control @error('registration_closes_at') is-invalid @enderror"
            value="{{ old('registration_closes_at', ($editing ? optional($flight->registration_closes_at)->format('Y-m-d\TH:i') : '')) }}"
        >
        @error('registration_closes_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="form-hint">@lang('flights.registration_window_hint')</div>
    </x-inputs.group>

    {{-- The plane sets the seat limit for every leg of this flight. --}}
    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="plane_id" label="Plane" data-tomselect="select" required>
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
        <x-inputs.select name="flight_route_id" label="Route" data-tomselect="select" required>
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
            <div class="alert alert-info mb-0"> @lang('flights.this_flight_already_has_booked_travellers_so') </div>
        </div>
    @endif
</div>
