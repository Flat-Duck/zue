@php $editing = isset($employee) @endphp

{{-- Every field comes from Employee::profileSections(), so the form, the show
     page and the validation rules stay in step. --}}
<div x-data="{ administration: '{{ old('administration_id', $editing ? ($employee->department?->administration_id ?? '') : '') }}' }">
    @include('app.employees._profile-fields', [
        'employee' => $editing ? $employee : null,
        'readonly' => false,
    ])
</div>
