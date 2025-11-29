@extends('layouts.app', ['page' => 'permissions'])
@section('title', 'قائمة التقارير')
@section('content')
    <div class="container-xl">
        <div class="page-header d-print-none">
            <h2 class="page-title">
                {{ __('التقارير') }}
            </h2>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body border-bottom py-3 row">
                    <div class="col-3">
                    </div>
                    <div class="col-6">
                    
                        
                        <form method="POST" action="{{ route('admin.reports.minus') }}">
                            @csrf
                            <div class="row mb-3">
                                <label class="form-label">Only Employees With Minus</label>
                                <button type="submit" class="btn btn-primary">Get Run </button>
                            </div>
                        </form>
                        <hr>
                        <br/>
                        <form method="POST" action="{{ route('admin.reports.to_date') }}">
                            @csrf
                            <div class="row mb-3">
                                <label class="form-label">Fieldbreak Balance To Date</label>
                                
                                <x-inputs.group class="col-sm-12">
                                    <x-inputs.select name="department_id" label="Department" required>
                                        @php $selected = old('department_id') @endphp
                                        <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Department</option>
                                        @foreach($departments as $value => $label)
                                        <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
                                        @endforeach
                                    </x-inputs.select>
                                </x-inputs.group>
                                <x-inputs.group class="col-sm-12">
                                    <x-inputs.select name="center_id" label="Center" required>
                                        @php $selected = old('center_id') @endphp
                                        <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Center</option>
                                        @foreach($centers as $value => $label)
                                        <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
                                        @endforeach
                                    </x-inputs.select>
                                </x-inputs.group>
                                <div class="mb-3">
                                    <label class="form-label @error('print_type') is-invalid @enderror" for="printer">Print Type</label>
                                    <div class="btn-group w-100" id="priter" role="group">
                                        <input type="radio" class="btn-check" name="print_type" id="pdf" value="pdf" autocomplete="off">
                                        <label for="pdf" class="btn btn-icon">
                                            <i class="ti ti-file-type-pdf"></i> PDF
                                        </label>
                                        <input type="radio" class="btn-check" name="print_type" id="excel" value="excel" autocomplete="off">
                                        <label for="excel" class="btn btn-icon">
                                            <i class="ti ti-file-type-xls"></i> Excel
                                        </label>
                                    </div>
                                    @error('print_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <x-inputs.group class="col-sm-12">
                                    <x-inputs.date
                                    name="date"
                                    label="Date"
                                    value="{{ old('date', (now())->format('Y-m-d')) }}"
                                    max="255"
                                    ></x-inputs.date>
                                </x-inputs.group>
                                <button type="submit" class="btn btn-primary">Get Run </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-3">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection