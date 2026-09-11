@php $editing = isset($plane) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="name" label="Name" :value="old('name', ($editing ? $plane->name : ''))" placeholder="@lang('flights.name')"
            required></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.number name="capacity" label="Capacity" :value="old('capacity', ($editing ? $plane->capacity : ''))"
            max="255" placeholder="@lang('flights.capacity')" required></x-inputs.number>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="lines" label="Lines" :value="old('lines', ($editing ? $plane->lines : ''))"
            placeholder="@lang('flights.lines')" required></x-inputs.text>
    </x-inputs.group>
</div>