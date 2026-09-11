@php
    use App\Models\ScopePolicyCriterion;

    /** @var \App\Models\ScopePolicy|null $scope */
    // `use` on a closure requires the variable to exist, and on the create screen
    // there is no scope yet.
    $scope = $managementScope ?? null;
    $editing = $scope !== null;

    $chosen = function (string $input, string $dimension) use ($editing, $scope) {
        $old = old($input);

        if (is_array($old)) {
            return array_map('intval', $old);
        }

        return $editing ? $scope->valuesFor($dimension) : [];
    };

    $selectedManagers = old('manager_ids');
    if (! is_array($selectedManagers)) {
        $selectedManagers = $editing
            ? $scope->actors->pluck('actor_employee_id')->map(fn ($id) => (int) $id)->all()
            : array_filter([$managerId ?? null]);
    }
    $selectedManagers = array_map('intval', $selectedManagers);

    $selectedFields = $chosen('field_ids', ScopePolicyCriterion::FIELD);
    $selectedDepartments = $chosen('department_ids', ScopePolicyCriterion::DEPARTMENT);
    $selectedCenters = $chosen('center_ids', ScopePolicyCriterion::CENTER);
    $selectedEmployees = $chosen('employee_ids', ScopePolicyCriterion::EMPLOYEE);

    $coversEveryone = (bool) old('covers_everyone', $editing ? $scope->covers_everyone : false);
    $selectedContext = (int) old('context_id', $editing ? $scope->context_id : ($contexts->first()->id ?? 0));

    // A new scope starts with whatever the context it opens on would suggest, so
    // the switch agrees with the dropdown above it before anything is touched.
    $carvesOut = (bool) old(
        'carves_out_managers',
        $editing
            ? $scope->carves_out_managers
            : ($contexts->firstWhere('id', $selectedContext)?->carves_out_managers ?? false)
    );

    $carveDefaults = $contexts->mapWithKeys(fn ($context) => [$context->id => (bool) $context->carves_out_managers]);
@endphp

<div
    x-data="{
        coversEveryone: @js($coversEveryone),
        carvesOut: @js($carvesOut),
        contextId: @js($selectedContext),
        carveDefaults: @js($carveDefaults),

        /* Choosing a context sets the sensible default for the carve-out, since
           it is the context that decides whether a hierarchy applies. It stays a
           per-scope choice, so changing it afterwards sticks. */
        contextChanged() {
            const suggested = this.carveDefaults[this.contextId];
            if (typeof suggested === 'boolean') {
                this.carvesOut = suggested;
            }
        },
    }"
    class="row"
>
    {{-- Name --}}
    <x-inputs.group class="col-sm-6">
        <label for="name" class="form-label">@lang('scopes.name')</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name', $editing ? $scope->name : '') }}"
            placeholder="@lang('scopes.name_placeholder')">
        @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
    </x-inputs.group>

    {{-- Context --}}
    <x-inputs.group class="col-sm-6">
        <label for="context_id" class="form-label required">@lang('scopes.context')</label>
        <select name="context_id" id="context_id" class="form-select @error('context_id') is-invalid @enderror"
            x-model.number="contextId" @change="contextChanged()" required>
            @foreach ($contexts as $context)
                <option value="{{ $context->id }}" @selected($selectedContext === $context->id)>
                    {{ $context->label() }}
                </option>
            @endforeach
        </select>
        <small class="form-hint">@lang('scopes.context_hint')</small>
        @error('context_id') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
    </x-inputs.group>

    {{-- Managers --}}
    <x-inputs.group class="col-sm-12">
        <label for="manager_ids" class="form-label required">@lang('scopes.managers')</label>
        <select name="manager_ids[]" id="manager_ids"
            class="form-select @error('manager_ids') is-invalid @enderror"
            multiple required data-tomselect="tags">
            @foreach ($managers as $manager)
                <option value="{{ $manager->id }}" @selected(in_array($manager->id, $selectedManagers, true))>
                    {{ $manager->number }} - {{ $manager->english_name ?? '#'.$manager->id }}
                </option>
            @endforeach
        </select>
        <small class="form-hint">@lang('scopes.managers_hint')</small>
        @error('manager_ids') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
    </x-inputs.group>

    {{-- Coverage --}}
    <div class="col-12">
        <hr class="my-3">
        <h3 class="h4">@lang('scopes.coverage')</h3>

        <label class="form-check form-switch mb-2">
            <input type="hidden" name="covers_everyone" value="0">
            <input class="form-check-input" type="checkbox" name="covers_everyone" value="1"
                x-model="coversEveryone">
            <span class="form-check-label">@lang('scopes.covers_everyone')</span>
        </label>
        <small class="form-hint d-block mb-3">@lang('scopes.covers_everyone_hint')</small>

        {{-- The three dimensions go quiet when the scope already covers the
             company, because they would have nothing left to narrow. --}}
        <div class="row" :class="coversEveryone ? 'opacity-50' : ''">
            <x-inputs.group class="col-sm-4">
                <label for="field_ids" class="form-label">@lang('scopes.fields')</label>
                <select name="field_ids[]" id="field_ids"
                    class="form-select @error('field_ids') is-invalid @enderror"
                    multiple data-tomselect="tags" :disabled="coversEveryone">
                    @foreach ($fields as $field)
                        <option value="{{ $field->id }}" @selected(in_array($field->id, $selectedFields, true))>
                            {{ $field->name }}
                        </option>
                    @endforeach
                </select>
            </x-inputs.group>

            <x-inputs.group class="col-sm-4">
                <label for="department_ids" class="form-label">@lang('scopes.departments')</label>
                <select name="department_ids[]" id="department_ids" class="form-select"
                    multiple data-tomselect="tags" :disabled="coversEveryone">
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(in_array($department->id, $selectedDepartments, true))>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </x-inputs.group>

            <x-inputs.group class="col-sm-4">
                <label for="center_ids" class="form-label">@lang('scopes.centers')</label>
                <select name="center_ids[]" id="center_ids" class="form-select"
                    multiple data-tomselect="tags" :disabled="coversEveryone">
                    @foreach ($centers as $center)
                        <option value="{{ $center->id }}" @selected(in_array($center->id, $selectedCenters, true))>
                            {{ $center->name }}
                        </option>
                    @endforeach
                </select>
            </x-inputs.group>

            <div class="col-12">
                <small class="form-hint">@lang('scopes.coverage_hint')</small>
                @error('field_ids') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
            </div>
        </div>

        <x-inputs.group class="col-sm-12 mt-3">
            <label for="employee_ids" class="form-label">@lang('scopes.named_employees')</label>
            <select name="employee_ids[]" id="employee_ids"
                class="form-select @error('employee_ids') is-invalid @enderror"
                multiple data-tomselect="tags">
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(in_array($employee->id, $selectedEmployees, true))>
                        {{ $employee->number }} - {{ $employee->english_name ?? '#'.$employee->id }}
                    </option>
                @endforeach
            </select>
            <small class="form-hint">@lang('scopes.named_employees_hint')</small>
            @error('employee_ids') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
        </x-inputs.group>

        <x-inputs.group class="col-sm-6 mt-3">
            <label for="job_title" class="form-label">@lang('scopes.job_title')</label>
            <input type="text" name="job_title" id="job_title" class="form-control"
                value="{{ old('job_title', $editing ? ($scope->jobTitle() ?? '') : '') }}"
                placeholder="@lang('scopes.job_title_placeholder')">
            <small class="form-hint">@lang('scopes.job_title_hint')</small>
        </x-inputs.group>
    </div>

    {{-- Behaviour --}}
    <div class="col-12">
        <hr class="my-3">
        <label class="form-check form-switch">
            <input type="hidden" name="carves_out_managers" value="0">
            <input class="form-check-input" type="checkbox" name="carves_out_managers" value="1"
                x-model="carvesOut">
            <span class="form-check-label">@lang('scopes.carves_out_managers')</span>
        </label>
        <small class="form-hint d-block">@lang('scopes.carves_out_managers_hint')</small>
    </div>

    {{-- Print header --}}
    <div class="col-12">
        <hr class="my-3">
        <h3 class="h4">@lang('scopes.print_header')</h3>
        <small class="form-hint d-block mb-2">@lang('scopes.print_header_hint')</small>
        <div class="row">
            @foreach ([
                ['print_location_id', 'scopes.print_field', $fields],
                ['print_department_id', 'scopes.print_department', $departments],
                ['print_center_id', 'scopes.print_center', $centers],
            ] as [$input, $label, $options])
                <x-inputs.group class="col-sm-4">
                    <label for="{{ $input }}" class="form-label">@lang($label)</label>
                    <select name="{{ $input }}" id="{{ $input }}" class="form-select" data-tomselect="select">
                        <option value="">@lang('scopes.none_selected')</option>
                        @foreach ($options as $option)
                            <option value="{{ $option->id }}"
                                @selected((int) old($input, $editing ? $scope->{$input} : null) === $option->id)>
                                {{ $option->name }}
                            </option>
                        @endforeach
                    </select>
                </x-inputs.group>
            @endforeach
        </div>
    </div>

    {{-- Status --}}
    <div class="col-12">
        <hr class="my-3">
        <div class="row">
            <x-inputs.group class="col-sm-3">
                <label for="priority" class="form-label">@lang('scopes.priority')</label>
                <input type="number" name="priority" id="priority" class="form-control" min="0" max="1000"
                    value="{{ old('priority', $editing ? $scope->priority : 0) }}">
            </x-inputs.group>
            <div class="col-sm-9 d-flex align-items-center">
                <label class="form-check form-switch mt-3">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                        @checked(old('is_active', $editing ? $scope->is_active : true))>
                    <span class="form-check-label">@lang('scopes.is_active')</span>
                </label>
            </div>
        </div>
    </div>
</div>
