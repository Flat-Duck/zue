@php $editing = isset($room) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="number"
            label="Number"
            :value="old('number', ($editing ? $room->number : ''))"
            maxlength="255"
            placeholder="Number"
            required
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.number
            name="beds"
            label="Beds"
            :value="old('beds', ($editing ? $room->beds : ''))"
            max="255"
            placeholder="Beds"
            required
        ></x-inputs.number>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="residence_id" label="Residence" required>
            @php $selected = old('residence_id', ($editing ? $room->residence_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Residence</option>
            @foreach($residences as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>
</div>
