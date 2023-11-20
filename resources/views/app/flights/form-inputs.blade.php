@php $editing = isset($flight) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="type" label="Type">
            @php $selected = old('type', ($editing ? $flight->type : '')) @endphp
            <option value="Air" {{ $selected == 'Air' ? 'selected' : '' }} >Air</option>
            <option value="Ground" {{ $selected == 'Ground' ? 'selected' : '' }} >Ground</option>
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date
            name="date"
            label="Date"
            value="{{ old('date', ($editing ? optional($flight->date)->format('Y-m-d') : '')) }}"
            max="255"
        ></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="time"
            label="Time"
            :value="old('time', ($editing ? $flight->time : ''))"
            maxlength="255"
            placeholder="Time"
        ></x-inputs.text>
    </x-inputs.group>
</div>
