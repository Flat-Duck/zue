@extends('layouts.app', ['page' => 'time-sheets'])
@section('content')
    <form action="{{ route('time-sheets.approve') }}" method="get" class="card">
        @csrf
        <div class="card-header p-2">
            <div class="col-auto">
                {{-- @livewire('search-employee') --}}
            </div>
        </div>
        <div class="card-body">
            <div class="col-12">

                <div class="row">
                    @if(!empty($scopeOptions ?? null) && $scopeOptions->isNotEmpty())
                    <x-inputs.group class="col-sm-4">
                        <x-inputs.select name="scope_policy_id" label="Management Scope">
                            @foreach($scopeOptions as $scopeOption)
                                <option value="{{ $scopeOption['id'] }}" @selected((int) ($selectedScopePolicyId ?? 0) === (int) $scopeOption['id'])>
                                    {{ $scopeOption['name'] }}
                                </option>
                            @endforeach
                        </x-inputs.select>
                    </x-inputs.group>
                    @endif
                    <x-inputs.group class="col-sm-4">
                        <x-inputs.select name="selected_month" label="Select Month">
                            <option value="1 ">January</option>
                            <option value="2 ">February</option>
                            <option value="3 ">March</option>
                            <option value="4 ">April</option>
                            <option value="5 ">May</option>
                            <option value="6 ">June</option>
                            <option value="7 ">July</option>
                            <option value="8 ">August</option>
                            <option value="9 ">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">Decembe</option>
                        </x-inputs.select>
                    </x-inputs.group>
                    <x-inputs.group class="col-sm-4">
                        <x-inputs.select name="selected_year" label="Select Year">
                            <option value="2024">2024</option>
                            <option value="2025">2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                            <option value="2028">2028</option>
                        </x-inputs.select>
                    </x-inputs.group>
                </div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col">
                        <a href="{{ route('time-sheets.index', ['scope_policy_id' => $selectedScopePolicyId ?? null]) }}"
                            class="btn btn-outline-secondary">@lang('crud.common.back')</a>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-printer"></i>
                            @lang('crud.common.print_preview')
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
