@php
    use App\Models\ManagementScope as ScopeModel;

    /** @var \App\Models\ManagementScope|null $managementScope */
    $editing = isset($managementScope);

    // For multi-select:
    // - prefer old('subordinate_employee_ids') if validation failed
    // - otherwise, when editing an employee scope:
    //   1. Check direct subordinate (legacy)
    //   2. Check grouped subordinates (settings)
    $selectedSubordinates = old('subordinate_employee_ids');

    if ($editing && empty($selectedSubordinates)) {
        if ($managementScope->subordinate_employee_id) {
            $selectedSubordinates = [$managementScope->subordinate_employee_id];
        } elseif (!empty($managementScope->settings['target_employee_ids'])) {
            $selectedSubordinates = $managementScope->settings['target_employee_ids'];
        }
    }

    $selectedSubordinates = (array) $selectedSubordinates;
@endphp

<div class="row">
    {{-- Manager --}}
    <x-inputs.group class="col-sm-12">
        <label for="manager_id" class="form-label">
            @lang('crud.management_scopes.inputs.manager_id', [], 'en')
        </label>
        <select
            name="manager_id"
            id="manager_id"
            class="form-control"
            required
        >
            <option value="">
                @lang('crud.common.please_select', [], 'en')
            </option>
            @foreach($managers as $manager)
                <option
                    value="{{ $manager->id }}"
                    @selected(old('manager_id', $editing ? $managementScope->manager_id : ($managerId ?? '')) == $manager->id)
                >
                    {{ $manager->english_name ?? ('#'.$manager->id) }}
                </option>
            @endforeach
        </select>
    </x-inputs.group>

    {{-- Context --}}
    <x-inputs.group class="col-sm-12">
        <label for="context" class="form-label">
            Context
        </label>
        <select
            name="context"
            id="context"
            class="form-control"
            required
        >
            @foreach($contexts as $ctx)
                <option
                    value="{{ $ctx }}"
                    @selected(old('context', $editing ? $managementScope->context : 'general') == $ctx)
                >
                    {{ ucfirst(str_replace('_', ' ', $ctx)) }}
                </option>
            @endforeach
        </select>
    </x-inputs.group>

    {{-- Scope Type --}}
    <x-inputs.group class="col-sm-12">
        <label for="scope_type" class="form-label">
            @lang('crud.management_scopes.inputs.scope_type', [], 'en')
        </label>
        <select
            name="scope_type"
            id="scope_type"
            class="form-control"
            required
        >
            <option value="">
                @lang('crud.common.please_select', [], 'en')
            </option>
            @foreach($scopeTypes as $type)
                <option
                    value="{{ $type }}"
                    @selected(old('scope_type', $editing ? $managementScope->scope_type : '') == $type)
                >
                    {{ ucfirst($type) }}
                </option>
            @endforeach
        </select>
    </x-inputs.group>

    {{-- Location --}}
    <x-inputs.group class="col-sm-12">
        <label for="location_id" class="form-label">
            @lang('crud.management_scopes.inputs.location_id', [], 'en')
        </label>
        <select
            name="location_id"
            id="location_id"
            class="form-control"
        >
            <option value="">
                @lang('crud.common.none', [], 'en')
            </option>
            @foreach($locations as $location)
                <option
                    value="{{ $location->id }}"
                    @selected(old('location_id', $editing ? $managementScope->location_id : '') == $location->id)
                >
                    {{ $location->name }}
                </option>
            @endforeach
        </select>
    </x-inputs.group>

    {{-- Department --}}
    <x-inputs.group class="col-sm-12">
        <label for="department_id" class="form-label">
            @lang('crud.management_scopes.inputs.department_id', [], 'en')
        </label>
        <select
            name="department_id"
            id="department_id"
            class="form-control"
        >
            <option value="">
                @lang('crud.common.none', [], 'en')
            </option>
            @foreach($departments as $department)
                <option
                    value="{{ $department->id }}"
                    @selected(old('department_id', $editing ? $managementScope->department_id : '') == $department->id)
                >
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
    </x-inputs.group>

    {{-- Center --}}
    <x-inputs.group class="col-sm-12">
        <label for="center_id" class="form-label">
            @lang('crud.management_scopes.inputs.center_id', [], 'en')
        </label>
        <select
            name="center_id"
            id="center_id"
            class="form-control"
        >
            <option value="">
                @lang('crud.common.none', [], 'en')
            </option>
            @foreach($centers as $center)
                <option
                    value="{{ $center->id }}"
                    @selected(old('center_id', $editing ? $managementScope->center_id : '') == $center->id)
                >
                    {{ $center->name }}
                </option>
            @endforeach
        </select>
    </x-inputs.group>

    {{-- Specific employees (multi-select) --}}
    <div class="mb-3">
        <label for="subordinate_employee_ids" class="form-label">
            @lang('crud.management_scopes.inputs.subordinate_employee_id', [], 'en')
        </label>
        <select
            name="subordinate_employee_ids[]"
            id="subordinate_employee_ids"
            class="form-select"
            multiple
            data-tomselect="tags"
        >
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}" 
                    @selected(in_array($employee->id, $selectedSubordinates)) >
                    {{ $employee->english_name ?? ('#'.$employee->id) }}
                </option>
            @endforeach
        </select>
        <small class="form-hint">
            Select one or more employees when scope type is <strong>employee</strong>.
        </small>
    </div>

    {{-- Settings: Job Title Filter --}}
    <x-inputs.group class="col-sm-12">
        <label for="settings_job_title" class="form-label">
            Job Title Filter (Optional)
        </label>
        <input
            type="text"
            name="settings[job_title]"
            id="settings_job_title"
            class="form-control"
            value="{{ old('settings.job_title', $editing ? ($managementScope->settings['job_title'] ?? '') : '') }}"
            placeholder="e.g. Nurse"
        >
        <small class="form-hint">
            Apply this scope only to employees with this job title.
        </small>
    </x-inputs.group>
</div>