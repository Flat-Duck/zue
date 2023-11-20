@php $editing = isset($timeSheet) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="value" label="Value">
            @php $selected = old('value', ($editing ? $timeSheet->value : '')) @endphp
            <option value="A" {{ $selected == 'A' ? 'selected' : '' }} >A</option>
            <option value="B" {{ $selected == 'B' ? 'selected' : '' }} >B</option>
            <option value="C" {{ $selected == 'C' ? 'selected' : '' }} >C</option>
            <option value="D" {{ $selected == 'D' ? 'selected' : '' }} >D</option>
            <option value="E" {{ $selected == 'E' ? 'selected' : '' }} >E</option>
            <option value="F" {{ $selected == 'F' ? 'selected' : '' }} >F</option>
            <option value="G" {{ $selected == 'G' ? 'selected' : '' }} >G</option>
            <option value="H" {{ $selected == 'H' ? 'selected' : '' }} >H</option>
            <option value="I" {{ $selected == 'I' ? 'selected' : '' }} >I</option>
            <option value="J" {{ $selected == 'J' ? 'selected' : '' }} >J</option>
            <option value="K" {{ $selected == 'K' ? 'selected' : '' }} >K</option>
            <option value="L" {{ $selected == 'L' ? 'selected' : '' }} >L</option>
            <option value="M" {{ $selected == 'M' ? 'selected' : '' }} >M</option>
            <option value="N" {{ $selected == 'N' ? 'selected' : '' }} >N</option>
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date
            name="day"
            label="Day"
            value="{{ old('day', ($editing ? optional($timeSheet->day)->format('Y-m-d') : '')) }}"
            max="255"
            required
        ></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="employee_id" label="Employee" required>
            @php $selected = old('employee_id', ($editing ? $timeSheet->employee_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Employee</option>
            @foreach($employees as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date
            name="revised_at"
            label="Revised At"
            value="{{ old('revised_at', ($editing ? optional($timeSheet->revised_at)->format('Y-m-d') : '')) }}"
            max="255"
        ></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text
            name="old_value"
            label="Old Value"
            :value="old('old_value', ($editing ? $timeSheet->old_value : ''))"
            maxlength="255"
            placeholder="Old Value"
        ></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="user_id" label="Revised By">
            @php $selected = old('user_id', ($editing ? $timeSheet->user_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the User</option>
            @foreach($users as $value => $label)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>
</div>
