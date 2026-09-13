@php $editing = isset($room) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="number" label="Number" :value="old('number', ($editing ? $room->number : ''))"
            placeholder="@lang('ui.number')" required></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.number name="beds" label="Beds" :value="old('beds', ($editing ? $room->beds : ''))" max="255"
            placeholder="@lang('ui.beds')" required></x-inputs.number>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="residence_id" label="Residence" data-tomselect="select" required>
            @php $selected = old('residence_id', ($editing ? $room->residence_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>@lang('ui.please_select_the_residence')</option>
            @foreach($residences as $k => $residence)
                <option value="{{ $residence->id }}" {{ $selected == $residence->id ? 'selected' : '' }}>
                    {{ $residence->type . ' - ' . $residence->name }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="employee_id[]" label="Employee" multiple required data-tomselect="tags" data-max-items="{{ $editing ? $room->beds : 2 }}">
            @php
                $selectedEmployees = old('employee_id', $editing ? ($residents ?? []) : []);
                $selectedEmployees = is_array($selectedEmployees) ? $selectedEmployees : [$selectedEmployees];
            @endphp
            @foreach($employees as $value => $label)
                <option value="{{ $value }}" {{ in_array($value, $selectedEmployees) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>



</div>
