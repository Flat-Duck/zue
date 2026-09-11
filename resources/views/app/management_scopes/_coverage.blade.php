{{--
    What a scope covers, in the words of the model: alternatives inside a
    dimension, dimensions narrowing each other, named people added on top.
--}}
@php
    use App\Models\ScopePolicyCriterion;

    $dimensions = [
        ScopePolicyCriterion::FIELD => ['scopes.fields', \App\Models\Location::class],
        ScopePolicyCriterion::DEPARTMENT => ['scopes.departments', \App\Models\Department::class],
        ScopePolicyCriterion::CENTER => ['scopes.centers', \App\Models\Center::class],
    ];
@endphp

@if ($scope->covers_everyone)
    <span class="badge bg-red-lt">@lang('scopes.everyone')</span>
@else
    @php $described = false; @endphp
    @foreach ($dimensions as $dimension => [$label, $model])
        @php $values = $scope->valuesFor($dimension); @endphp
        @if ($values !== [])
            @php $described = true; @endphp
            <div class="small">
                <span class="text-secondary">@lang($label):</span>
                {{ $model::query()->whereIn('id', $values)->orderBy('name')->pluck('name')->implode(', ') }}
            </div>
        @endif
    @endforeach

    @php $named = $scope->namedEmployeeIds(); @endphp
    @if ($named !== [])
        @php $described = true; @endphp
        <div class="small">
            <span class="text-secondary">@lang('scopes.named_employees'):</span>
            @lang('scopes.employee_count', ['count' => count($named)])
        </div>
    @endif

    @unless ($described)
        <span class="badge bg-secondary-lt">@lang('scopes.nobody')</span>
    @endunless
@endif

@if ($scope->jobTitle())
    <div class="small">
        <span class="text-secondary">@lang('scopes.job_title'):</span> {{ $scope->jobTitle() }}
    </div>
@endif
