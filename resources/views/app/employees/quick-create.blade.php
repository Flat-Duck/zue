@extends('layouts.app', ['page' => 'employees'])

@section('content')
    <form method="POST" action="{{ route('employees.quick-store') }}" class="card">
        @csrf
        <div class="card-header">
            <a href="{{ route('employees.index') }}" class="mr-4">
                <i class="ti ti-arrow-back"></i>
            </a>
            <h3 class="card-title">@lang('ui.quick_create_employee')</h3>
        </div>

        <div class="card-body">
            @error('department')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
            @enderror

            <div class="row">
                <x-inputs.group class="col-sm-6">
                    <x-inputs.text name="english_name" label="Name" :value="old('english_name')" placeholder="@lang('ui.name')"
                        required></x-inputs.text>
                </x-inputs.group>

                <x-inputs.group class="col-sm-6">
                    <x-inputs.number name="number" label="Number" :value="old('number')" placeholder="@lang('ui.number')"
                        required></x-inputs.number>
                </x-inputs.group>

                <x-inputs.group class="col-sm-6">
                    <x-inputs.date name="employment_date" label="Employment Date" value="{{ old('employment_date') }}"
                        required></x-inputs.date>
                </x-inputs.group>

                <x-inputs.group class="col-sm-6">
                    <x-inputs.select name="location_id" label="Location" required>
                        @php $selected = old('location_id') @endphp
                        <option disabled {{ empty($selected) ? 'selected' : '' }}>@lang('ui.please_select_the_location')</option>
                        @foreach($locations as $value => $label)
                            <option value="{{ $value }}" {{ (string) $selected === (string) $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-inputs.select>
                </x-inputs.group>

                <x-inputs.group class="col-sm-6">
                    <x-inputs.select name="center_id" label="Centre" required>
                        @php $selected = old('center_id') @endphp
                        <option disabled {{ empty($selected) ? 'selected' : '' }}>@lang('ui.please_select_the_centre')</option>
                        @foreach($centers as $value => $label)
                            <option value="{{ $value }}" {{ (string) $selected === (string) $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </x-inputs.select>
                </x-inputs.group>
            </div>
        </div>

        <div class="card-footer text-end">
            <div class="d-flex">
                <a href="{{ route('employees.index') }}" class="btn btn-outline-secondary">
                    @lang('crud.common.back')
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy"></i> @lang('crud.common.create')
                </button>
            </div>
        </div>
    </form>
@endsection