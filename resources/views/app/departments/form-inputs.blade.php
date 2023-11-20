@php $editing = isset($department) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="name"
            label="Name"
            :value="old('name', ($editing ? $department->name : ''))"
            maxlength="255"
            placeholder="Name"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select
            name="administration_id"
            label="Administration"
            required
        >
            @php $selected = old('administration_id', ($editing ? $department->administration_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Administration</option>
            @foreach($administrations as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>
</div>
