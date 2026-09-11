@php
    use App\Models\ManagementScope as ScopeModel;

    /** @var \App\Models\ManagementScope|null $managementScope */
    $editing = isset($managementScope);

    // 1. Managers Selection
    $selectedManagers = old('manager_ids');
    if ($editing && empty($selectedManagers)) {
        $selectedManagers = $managementScope->managers->pluck('id')->toArray();
        // Fallback to legacy owner if pivot is empty but owner exists
        if (empty($selectedManagers) && $managementScope->manager_id) {
            $selectedManagers = [$managementScope->manager_id];
        }
    }
    // If we're creating and a managerId was passed in query string
    if (!$editing && empty($selectedManagers) && !empty($managerId)) {
        $selectedManagers = [$managerId];
    }
    $selectedManagers = (array) $selectedManagers;

    // 2. Subordinates Selection (multi-select)
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
    {{-- Managers --}}
    <x-inputs.group class="col-sm-12">
        <label for="manager_ids" class="form-label"> @lang('ui.managers_one_or_more') </label>
        <select
            name="manager_ids[]"
            id="manager_ids"
            class="form-control"
            required
            multiple
            data-tomselect="tags"
        >
            @foreach($managers as $manager)
                <option
                    value="{{ $manager->id }}"
                    @selected(in_array($manager->id, $selectedManagers))
                >
                    {{ $manager->number }} - {{ $manager->english_name ?? ('#'.$manager->id) }}
                </option>
            @endforeach
        </select>
        <small class="form-hint"> @lang('ui.you_can_assign_multiple_managers_to_the_same') </small>
    </x-inputs.group>

    {{-- Name --}}
    <x-inputs.group class="col-sm-12">
        <label for="name" class="form-label"> @lang('ui.scope_name_optional') </label>
        <x-inputs.text
            name="name"
            id="name"
            :value="old('name', ($editing ? $managementScope->name : ''))"
            
            placeholder="@lang('ui.e_g_my_custom_scope')"
        ></x-inputs.text>
    </x-inputs.group>

    {{-- Template --}}
    <x-inputs.group class="col-sm-12">
        <label for="template" class="form-label"> @lang('ui.template_identifier') </label>
        <select
            name="template"
            id="template"
            class="form-control"
            required
        >
            @php
                $templates = ['general', 'test1', 'test2', 'test3', 'test4'];
            @endphp
            @foreach($templates as $tpl)
                <option
                    value="{{ $tpl }}"
                    @selected(old('template', $editing ? $managementScope->template : 'general') == $tpl)
                >
                    {{ ucfirst($tpl) }}
                </option>
            @endforeach
        </select>
    </x-inputs.group>

    {{-- Context --}}
    <x-inputs.group class="col-sm-12">
        <label for="context" class="form-label"> @lang('ui.context') </label>
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
        <small class="form-hint"> @lang('ui.for') <strong>@lang('ui.employee_2')</strong> @lang('ui.scope_type_this_is_used_as_print_header') </small>
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
        <small class="form-hint"> @lang('ui.for') <strong>@lang('ui.employee_2')</strong> @lang('ui.scope_type_this_is_used_as_print_header') </small>
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
                    {{ $employee->number }} - {{ $employee->english_name ?? ('#'.$employee->id) }}
                </option>
            @endforeach
        </select>
        <small class="form-hint"> @lang('ui.select_one_or_more_employees_when_scope_type') <strong>@lang('ui.employee_2')</strong>.
        </small>
    </div>

    {{-- Settings: Job Title Filter --}}
    <x-inputs.group class="col-sm-12">
        <label for="settings_job_title" class="form-label"> @lang('ui.job_title_filter_optional') </label>
        <input
            type="text"
            name="settings[job_title]"
            id="settings_job_title"
            class="form-control"
            value="{{ old('settings.job_title', $editing ? ($managementScope->settings['job_title'] ?? '') : '') }}"
            placeholder="@lang('ui.e_g_nurse')"
        >
        <small class="form-hint"> @lang('ui.apply_this_scope_only_to_employees_with_this') </small>
    </x-inputs.group>
</div>
