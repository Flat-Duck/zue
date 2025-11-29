@php
    use App\Models\ManagementScope as ScopeModel;

    /** @var \App\Models\ManagementScope|null $managementScope */
    $editing = isset($managementScope);

    // For multi-select:
    // - prefer old('subordinate_employee_ids') if validation failed
    // - otherwise, when editing an employee scope, preselect the current subordinate
    $selectedSubordinates = old('subordinate_employee_ids');

    if ($editing
        && $managementScope->scope_type === ScopeModel::TYPE_EMPLOYEE
        && empty($selectedSubordinates)
    ) {
        $selectedSubordinates = [$managementScope->subordinate_employee_id];
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
    <x-inputs.group class="col-sm-12">
        <label for="subordinate_employee_ids" class="form-label">
            @lang('crud.management_scopes.inputs.subordinate_employee_id', [], 'en')
        </label>

        <select
            name="subordinate_employee_ids[]"
            id="subordinate_employee_ids"
            {{-- class="form-control" --}}
            multiple
        class="form-select"
        data-role="tagsinput"
        >
            @foreach($employees as $employee)
                <option
                    value="{{ $employee->id }}"
                    @selected(in_array($employee->id, $selectedSubordinates))
                >
                    {{ $employee->english_name ?? ('#'.$employee->id) }}
                </option>
            @endforeach
        </select>

        <small class="form-hint">
            Select one or more employees when scope type is <strong>employee</strong>.
        </small>
    </x-inputs.group>
</div>
