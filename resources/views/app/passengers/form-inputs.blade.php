@php $editing = isset($passenger) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="name"
            label="Name"
            :value="old('name', ($editing ? $passenger->name : ''))"
            maxlength="255"
            placeholder="Name"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="company"
            label="Company"
            :value="old('company', ($editing ? $passenger->company : ''))"
            maxlength="255"
            placeholder="Company"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="number"
            label="Number"
            :value="old('number', ($editing ? $passenger->number : ''))"
            maxlength="255"
            placeholder="Number"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="nationality"
            label="Nationality"
            :value="old('nationality', ($editing ? $passenger->nationality : ''))"
            maxlength="255"
            placeholder="Nationality"
        ></x-inputs.text>
    </x-inputs.group>
</div>
