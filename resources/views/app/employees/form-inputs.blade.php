@php $editing = isset($employee) @endphp

<div class="row">
    <x-inputs.group class="col-sm-12">
        <x-inputs.number name="number" label="Number" :value="old('number', ($editing ? $employee->number : ''))"
            placeholder="Number"></x-inputs.number>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="job" label="Job" :value="old('job', ($editing ? $employee->job : ''))" maxlength="255"
            placeholder="Job"></x-inputs.text>
    </x-inputs.group>
    @role('super-admin')
    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="employee_level" label="Employement Level" required>
            @php $selected = old('employee_level', ($editing ? $employee->employee_level : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Employement Level</option>
            <option value="1" {{ $selected == 1 ? 'selected' : '' }}>Employee </option>
            <option value="2" {{ $selected == 2 ? 'selected' : '' }}>Supervisor </option>
            <option value="3" {{ $selected == 3 ? 'selected' : '' }}>Field Coordinator </option>
            <option value="4" {{ $selected == 4 ? 'selected' : '' }}>Superintendent </option>

        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="management_level" label="Management Level" required>
            @php $selected = old('management_level', ($editing ? $employee->management_level : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Management Level</option>
            <option value="2" {{ $selected == 2 ? 'selected' : '' }}>Supervisor </option>
            <option value="3" {{ $selected == 3 ? 'selected' : '' }}>Field Coordinator </option>
            <option value="4" {{ $selected == 4 ? 'selected' : '' }}>Superintendent </option>
        </x-inputs.select>
    </x-inputs.group>
    @endrole
    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="english_name" label="English Name" :value="old('english_name', ($editing ? $employee->english_name : ''))" maxlength="255" placeholder="English Name"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="id_card" label="Id Card" :value="old('id_card', ($editing ? $employee->id_card : ''))"
            maxlength="255" placeholder="Id Card"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date name="id_card_issue_date" label="Id Card Issue Date"
            value="{{ old('id_card_issue_date', ($editing ? optional($employee->id_card_issue_date)->format('Y-m-d') : '')) }}"></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="passport" label="Passport" :value="old('passport', ($editing ? $employee->passport : ''))"
            maxlength="255" placeholder="Passport"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date name="passport_issue_date" label="Passport Issue Date"
            value="{{ old('passport_issue_date', ($editing ? optional($employee->passport_issue_date)->format('Y-m-d') : '')) }}"></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="address" label="Address" :value="old('address', ($editing ? $employee->address : ''))"
            maxlength="255" placeholder="Address"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="phone" label="Phone" :value="old('phone', ($editing ? $employee->phone : ''))"
            maxlength="255" placeholder="Phone"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.email name="email" label="Email" :value="old('email', ($editing ? $employee->email : ''))"
            maxlength="255" placeholder="Email"></x-inputs.email>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="user_id" label="User" required>
            @php $selected = old('user_id', ($editing ? $employee->user_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the User</option>
            @foreach($users as $value => $label)
                <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="location_id" label="Location" required>
            @php $selected = old('location_id', ($editing ? $employee->location_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Location</option>
            @foreach($locations as $value => $label)
                <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="department_id" label="Department" required>
            @php $selected = old('department_id', ($editing ? $employee->department_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Department</option>
            @foreach($departments as $value => $label)
                <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.select name="center_id" label="Center" required>
            @php $selected = old('center_id', ($editing ? $employee->center_id : '')) @endphp
            <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Center</option>
            @foreach($centers as $value => $label)
                <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </x-inputs.select>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.number name="transfered_balance" label="Transfered Balance" :value="old('transfered_balance', ($editing ? $employee->transfered_balance : '0'))" placeholder="Transfered Balance"></x-inputs.number>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.text name="schedule" label="Schedule" :value="old('schedule', ($editing ? $employee->schedule : ''))"
            maxlength="255" placeholder="Schedule"></x-inputs.text>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date name="start_date" label="Start Date"
            value="{{ old('start_date', ($editing ? optional($employee->start_date)->format('Y-m-d') : '')) }}"></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date name="last_date" label="Last Date"
            value="{{ old('last_date', ($editing ? optional($employee->last_date)->format('Y-m-d') : '')) }}"></x-inputs.date>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.number name="total_balance" label="Total Balance" :value="old('total_balance', ($editing ? $employee->total_balance : ''))" placeholder="Total Balance"></x-inputs.number>
    </x-inputs.group>

    <x-inputs.group class="col-sm-12">
        <x-inputs.date name="archived_at" label="Archived At"
            value="{{ old('archived_at', ($editing ? optional($employee->archived_at)->format('Y-m-d') : '')) }}"></x-inputs.date>
    </x-inputs.group>
</div>