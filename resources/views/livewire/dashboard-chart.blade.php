<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center">
            <div class="subheader">@lang('ui.timesheet_coverage_summary')</div>
            <div class="ms-auto lh-1">
                <div class="dropdown">
                    <select wire:model.live="selectedYear" class="form-select form-select-sm">
                        @foreach($availableYears as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div wire:ignore
             id="chart-timesheet-coverage"
             class="dashboard-chart"
             data-coverage="{{ json_encode($initialData) }}"></div>
    </div>
</div>
