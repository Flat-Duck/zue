@props([
    'id',
    'name',
    'label',
    'value',
    'checked' => false,
    'addHiddenValue' => true,
    'hiddenValue' => 0,
])




@php
    $checked = !! $checked
@endphp

<div class="form-check">

    {{-- Adds a hidden default value to be send if checked is false --}}
    @if($addHiddenValue)
    <input type="hidden" id="{{  $id ?? $name }}-hidden" name="{{ $name }}" value="{{ $hiddenValue }}">
    @endif

<label class="form-selectgroup-item">
    <input type="radio"  value="excel" class="form-selectgroup-input @error('print_type') is-invalid @enderror" name="print_type">
    <span class="form-selectgroup-label">Excel</span>
</label>

    <input
        type="checkbox"
        id="{{ $id ?? $name }}"
        name="{{ $name }}"
        value="{{ $value ?? 1 }}"
        {{ $checked ? 'checked' : '' }}
        {{ $attributes->merge(['class' => 'form-check-input']) }}
    >

    @if($label ?? null)
        <label class="form-check-label" for="{{ $id ?? $name }}">
            {{ $label }}
        </label>
    @endif
</div>

@error($name)
    @include('components.inputs.partials.error')
@enderror