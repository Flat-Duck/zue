@php
    use App\Models\Employee;

    /**
     * Renders every employee field from Employee::profileSections().
     *
     * $readonly renders the show page; otherwise it renders the create/edit
     * form. Both come from the same definition so the two cannot drift apart.
     */
    $readonly = $readonly ?? false;
    $model = $employee ?? null;

    $optionSources = [
        'users' => $users ?? collect(),
        'locations' => $locations ?? collect(),
        'departments' => $departments ?? collect(),
        'centers' => $centers ?? collect(),
    ];

    $choices = [
        'employee_level' => [1 => 'Employee', 2 => 'Supervisor', 3 => 'Field Coordinator', 4 => 'Superintendent'],
        'management_level' => [2 => 'Supervisor', 3 => 'Field Coordinator', 4 => 'Superintendent'],
    ];

    $isSuperAdmin = auth()->user()?->hasRole('super-admin') ?? false;
@endphp

@foreach (Employee::profileSections() as $title => $section)
    <div class="card mt-3">
        <div class="card-header">
            <h4 class="card-title mb-0">
                {{ $title }}
                <span class="text-muted ms-2" dir="rtl">{{ $section['arabic'] }}</span>
            </h4>
        </div>

        <div class="card-body">
            <div class="row">
                @foreach ($section['fields'] as $name => $field)
                    @php
                        $type = $field['type'];

                        // Only a super admin sets the levels that drive approvals.
                        $restricted = in_array($name, ['employee_level', 'management_level'], true);
                    @endphp

                    @continue ($restricted && ! $readonly && ! $isSuperAdmin)

                    @php
                        $current = $model?->{$name};

                        if ($current instanceof \Carbon\CarbonInterface) {
                            $current = $current->format('Y-m-d');
                        }

                        $value = $readonly ? $current : old($name, $current);
                        $width = $type === 'textarea' ? 'col-12' : 'col-md-6 col-lg-4';
                    @endphp

                    <div class="{{ $width }} mb-3">
                        <label class="form-label" for="{{ $name }}">
                            {{ $field['label'] }}
                            <span class="text-muted small ms-1" dir="rtl">{{ $field['arabic'] }}</span>
                        </label>

                        @if ($type === 'administration')
                            {{-- An employee's administration follows its department, which is
                                 how the rest of the application already reads it. On the form
                                 it narrows the department list instead of being stored. --}}
                            @if ($readonly)
                                <input id="{{ $name }}" type="text" class="form-control"
                                       value="{{ $model?->administration_name ?? '-' }}" disabled />
                            @else
                                <select id="{{ $name }}" class="form-select"
                                        x-on:change="administration = $event.target.value">
                                    <option value="">@lang('ui.all_administrations')</option>
                                    @foreach (($administrations ?? collect()) as $administrationId => $administrationName)
                                        <option value="{{ $administrationId }}"
                                            @selected((string) ($model?->department?->administration_id) === (string) $administrationId)>
                                            {{ $administrationName }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">@lang('ui.filters_the_department_list_the_department')</small>
                            @endif

                        @elseif (str_starts_with($type, 'select:'))
                            @php
                                $source = $optionSources[substr($type, 7)] ?? collect();
                                $isDepartment = $name === 'department_id';
                            @endphp

                            @if ($readonly)
                                <input id="{{ $name }}" type="text" class="form-control"
                                       value="{{ optional($model?->{str_replace('_id', '', $name)})->name ?? '-' }}" disabled />
                            @else
                                <select id="{{ $name }}" name="{{ $name }}"
                                        class="form-select @error($name) is-invalid @enderror">
                                    <option value="">@lang('ui.select')</option>
                                    @foreach ($source as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}"
                                            @if ($isDepartment) data-administration="{{ $departmentAdministrations[$optionValue] ?? '' }}"
                                                x-show="!administration || administration === '{{ $departmentAdministrations[$optionValue] ?? '' }}'" @endif
                                            @selected((string) $value === (string) $optionValue)>
                                            {{ $optionLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                        @elseif (str_starts_with($type, 'choice:'))
                            @php $options = $choices[substr($type, 7)] ?? []; @endphp

                            @if ($readonly)
                                <input id="{{ $name }}" type="text" class="form-control"
                                       value="{{ $options[$value] ?? ($value ?? '-') }}" disabled />
                            @else
                                <select id="{{ $name }}" name="{{ $name }}"
                                        class="form-select @error($name) is-invalid @enderror">
                                    <option value="">@lang('ui.select')</option>
                                    @foreach ($options as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>
                                            {{ $optionLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif

                        @elseif ($type === 'textarea')
                            <textarea id="{{ $name }}" name="{{ $name }}" rows="3"
                                      class="form-control @error($name) is-invalid @enderror"
                                      @disabled($readonly)>{{ $value }}</textarea>

                        @elseif ($readonly && blank($value))
                            {{-- An empty date input would show its own dd/mm/yyyy
                                 placeholder, so show nothing recorded instead. --}}
                            <input id="{{ $name }}" type="text" class="form-control" value="-" disabled />

                        @else
                            <input
                                id="{{ $name }}"
                                name="{{ $name }}"
                                type="{{ $type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text') }}"
                                @if ($type === 'number') step="any" @endif
                                class="form-control @error($name) is-invalid @enderror"
                                value="{{ $value }}"
                                @disabled($readonly)
                            />
                        @endif

                        @error($name)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endforeach
