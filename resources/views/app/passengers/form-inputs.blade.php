@php $editing = isset($passenger) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="name" label="Name" :value="old('name', ($editing ? $passenger->name : ''))"
            placeholder="@lang('flights.name')"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="company" label="Company" :value="old('company', ($editing ? $passenger->company : ''))"
            placeholder="@lang('flights.company')"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="number" label="Number" :value="old('number', ($editing ? $passenger->number : ''))"
            placeholder="@lang('flights.number')"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="nationality" label="Nationality" :value="old('nationality', ($editing ? $passenger->nationality : ''))" placeholder="@lang('flights.nationality')"></x-inputs.text>
    </x-inputs.group>
</div>