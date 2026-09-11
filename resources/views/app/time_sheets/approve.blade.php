@extends('layouts.app', ['page' => 'employees'])
@section('content')
    @section('styles')
    @vite('resources/sass/print/timesheet-approve.scss')
    @endsection
    <div class="card">
        <div class="card-body">
            @if(!empty($scopeOptions ?? null) && $scopeOptions->isNotEmpty())
                <form action="{{ route('time-sheets.approve') }}" method="get" class="d-print-none mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">@lang('timesheets.management_scope')</label>
                            <select name="scope_policy_id" class="form-select">
                                @foreach($scopeOptions as $scopeOption)
                                    <option value="{{ $scopeOption['id'] }}" @selected((int) ($selectedScopePolicyId ?? 0) === (int) $scopeOption['id'])>
                                        {{ $scopeOption['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">@lang('timesheets.month')</label>
                            <input type="number" min="1" max="12" name="selected_month" class="form-control" value="{{ $selected_month }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">@lang('timesheets.year')</label>
                            <input type="number" min="2000" max="2100" name="selected_year" class="form-control" value="{{ $selected_year }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">@lang('timesheets.apply')</button>
                        </div>
                    </div>
                </form>
            @endif
            @php
                $rt = 1;
            @endphp
            @foreach ($chunks as $k => $days)
                <div class="header">
                    <div class="row mt-2">
                        <div class="col-3">
                            <img src="{{ asset('/img/zue-logo.png') }}" class="print-logo" class="mx-auto d-block">
                        </div>
                        <div class="col-6">
                            <h2 class="h2 text-center"> @lang('timesheets.intisar_fields') </h2>
                            <h3 class="h3 text-center"> @lang('timesheets.time_control_card') </h3>
                        </div>
                        <div class="col-3">
                            <img src="{{ asset('/img/noc-logo.png') }}" class="print-logo" class="mx-auto d-block">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-1"></div>
                        <div class="col-2 box">
                            <h6 class="text-center"> @lang('timesheets.year') </h6>
                            <hr class="divider">
                            <h6 class="text-center">
                                {{ $selected_year }}
                            </h6>
                        </div>
                        <div class="col-2 box">
                            <h6 class="text-center"> @lang('timesheets.month') </h6>
                            <hr class="divider">
                            <h6 class="text-center">
                                {{ $month_name }}
                            </h6>
                        </div>
                        <div class="col-2 box">
                            <h6 class="text-center"> @lang('timesheets.cost_center') </h6>
                            <hr class="divider">
                            <h6 class="text-center">
                                {{ $center ?? '' }}
                            </h6>
                        </div>
                        <div class="col-2 box">
                            <h6 class="text-center"> @lang('timesheets.department') </h6>
                            <hr class="divider">
                            <h6 class="text-center">
                                {{ $department ?? '' }}
                            </h6>
                        </div>
                        <div class="col-2 box">
                            <h6 class="text-center"> @lang('timesheets.administration') </h6>
                            <hr class="divider">
                            <h6 class="text-center">
                                {{ $administration ?? '' }}
                            </h6>
                        </div>
                        <div class="col-1"></div>
                    </div>
                </div>
                <div class="table-responsive p-0 mt-4 mb-1" style="margin-top: 50px !important;">
                    <table class="table table-vcenter text-center">
                        <thead>
                            <tr>
                                <th class="p-1">#</th>
                                <th class="p-0 m-0">Z-N</th>
                                <th class="p-0 ">@lang('timesheets.employee_name')</th>
                                @for ($i = 1; $i < $month_days; $i++)
                                    <th rowspan="2" class="p-1">
                                        <h5 class="p-0 m-0"> {{ $x = $i >= 10 ? $i : '0' . $i }}</h5>
                                    </th>
                                @endfor
                                <th class="p-0">@lang('timesheets.ot')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($days as $e_id => $month)
                                <div>
                                    <tr>
                                        <th rowspan="2" class="p-0">{{ $rt++ }}</th>
                                        <th rowspan="2" class="p-0 m-0">{{ $e_id }}</th>
                                        <th rowspan="2" class="p-0 name m-0">{{ $employees[$e_id] }}</th>
                                        @php
                                            $totalOT = 0;
                                            $daysCount = count($month);
                                            $columnsCount = $month_days - 1;
                                        @endphp
                                        @foreach ($month as $day)
                                            @php
                                                $totalOT += $day->ot_value;
                                            @endphp
                                            @if ($day->value)
                                                <th class="p-0 {{ $day->css_class }} expnded">{{ $day->value }}</th>
                                            @else
                                                <th class="p-0 bg-muted expnded">?</th>
                                            @endif
                                        @endforeach
                                        @for ($i = $daysCount; $i < $columnsCount; $i++)
                                            <th class="p-0 bg-muted expnded">?</th>
                                        @endfor
                                        <th rowspan="2" class="p-1 expnded">{{ $totalOT }}</th>
                                    </tr>
                                    <tr>
                                        @foreach ($month as $day)
                                            @php
                                                $bg = '';
                                                if ($day->ot_value > 0) {
                                                    $bg = 'skyblue';
                                                }
                                            @endphp
                                            <td class="p-0 {{ $bg }} expnded">{{ $day->ot_value == 0 ? '*' : $day->ot_value }}</td>
                                        @endforeach

                                        {{-- Fill remaining columns --}}
                                        @for ($i = $daysCount; $i < $columnsCount; $i++)
                                            <td class="p-0 bg-muted expnded">*</td>
                                        @endfor
                                    </tr>

                                </div>
                            @endforeach

                        </tbody>
                    </table>
                </div>
                <footer>
                    @if(!empty($approvalStages ?? []))
                        <div class="row gx-2">
                            @foreach($approvalStages as $stage)
                                @continue(empty($stage['visible']))
                                <div class="col box text-center">
                                    <h6>{{ $stage['label'] }}</h6>
                                    <hr class="divider">
                                    @if(!empty($stage['signature']['path'] ?? null))
                                        <div>
                                            <h6 class="container">
                                                <img src="{{ asset('storage/' . $stage['signature']['path']) }}"
                                                    class="signature-space" class="mx-auto d-block centered">
                                            </h6>
                                            {{ $stage['signature']['name'] ?? '' }}
                                        </div>
                                    @elseif(!empty($stage['can_approve']))
                                        <form method="POST" action="{{ route('time-sheets.approves') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="level" value="{{ $stage['key'] }}">
                                            <input type="hidden" name="month" value="{{ $selected_month }}">
                                            <input type="hidden" name="year" value="{{ $selected_year }}">
                                            @if(!is_null($selectedScopePolicyId ?? null))
                                                <input type="hidden" name="scope_policy_id" value="{{ $selectedScopePolicyId }}">
                                            @endif
                                            <button type="submit" data-bs-original-title="@lang('timesheets.approve')" data-bs-placement="top" data-bs-toggle="tooltip"
                                                class="pull-right btn btn-yellow">
                                                <i class="ti ti-check"></i>
                                                @lang('crud.common.time_sheet_approve')
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        @php
                            $timekeeperSigned = !empty($signatures['time_keeper']['sign'] ?? null);
                            $supervisorSigned = !empty($signatures['super_visor']['sign'] ?? null);
                            $fieldCoordinatorSigned = !empty($signatures['field_coordinator']['sign'] ?? null);
                            $superintendentSigned = !empty($signatures['super_intendent']['sign'] ?? null);

                            $showSupervisorStage = $requiresSupervisorStage && ($timekeeperSigned || $supervisorSigned || $canSupervisorApprove);
                            $showFieldCoordinatorStage = $requiresFieldCoordinatorStage && ($timekeeperSigned || $supervisorSigned || $fieldCoordinatorSigned || $canFieldCoordinatorApprove);
                            $showSuperintendentStage = $requiresSuperintendentStage && ($timekeeperSigned || $superintendentSigned || $canSuperintendentApprove);
                        @endphp

                        <div class="row gx-2">
                            <div class="col box text-center">
                                <h6>@lang('timesheets.timekeeper')</h6>
                                <hr class="divider">
                                @if($timekeeperSigned)
                                    <div>
                                        <h6 class="container">
                                            <img src="{{ asset('storage/' . $signatures['time_keeper']['sign']) }}"
                                                class="signature-space" class="mx-auto d-block centered">
                                        </h6>
                                        {{ $signatures['time_keeper']['name'] }}
                                    </div>
                                @elseif(auth()->user()->hasRole('timekeeper') && $canTimekeeperApprove)
                                    <form method="POST" action="{{ route('time-sheets.approves') }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="level" value="timekeeper">
                                        <input type="hidden" name="month" value="{{ $selected_month }}">
                                        <input type="hidden" name="year" value="{{ $selected_year }}">
                                        @if(!is_null($selectedScopePolicyId ?? null))
                                            <input type="hidden" name="scope_policy_id" value="{{ $selectedScopePolicyId }}">
                                        @endif
                                        <button type="submit" data-bs-original-title="@lang('timesheets.approve')" data-bs-placement="top" data-bs-toggle="tooltip"
                                            class="pull-right btn btn-yellow">
                                            <i class="ti ti-check"></i>
                                            @lang('crud.common.time_sheet_approve')
                                        </button>
                                    </form>
                                @endif
                            </div>

                            @if($showSupervisorStage)
                                <div class="col box text-center">
                                    <h6>@lang('timesheets.department_supervisor')</h6>
                                    <hr class="divider">
                                    @if($supervisorSigned)
                                        <div>
                                            <h6 class="container">
                                                <img src="{{ asset('storage/' . $signatures['super_visor']['sign']) }}"
                                                    class="signature-space" class="mx-auto d-block centered">
                                            </h6>
                                            {{ $signatures['super_visor']['name'] }}
                                        </div>
                                    @elseif(auth()->user()->hasRole('supervisor') && $canSupervisorApprove)
                                        <form method="POST" action="{{ route('time-sheets.approves') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="level" value="supervisor">
                                            <input type="hidden" name="month" value="{{ $selected_month }}">
                                            <input type="hidden" name="year" value="{{ $selected_year }}">
                                            @if(!is_null($selectedScopePolicyId ?? null))
                                                <input type="hidden" name="scope_policy_id" value="{{ $selectedScopePolicyId }}">
                                            @endif
                                            <button type="submit" data-bs-original-title="@lang('timesheets.approve')" data-bs-placement="top" data-bs-toggle="tooltip"
                                                class="pull-right btn btn-yellow">
                                                <i class="ti ti-check"></i>
                                                @lang('crud.common.time_sheet_approve')
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif

                            @if($showFieldCoordinatorStage)
                                <div class="col box text-center">
                                    <h6>@lang('timesheets.field_coordinator')</h6>
                                    <hr class="divider">
                                    @if($fieldCoordinatorSigned)
                                        <div>
                                            <h6 class="container">
                                                <img src="{{ asset('storage/' . $signatures['field_coordinator']['sign']) }}"
                                                    class="signature-space" class="mx-auto d-block centered">
                                            </h6>
                                            {{ $signatures['field_coordinator']['name'] }}
                                        </div>
                                    @elseif(auth()->user()->hasRole('fieldcoordinator') && $canFieldCoordinatorApprove)
                                        <form method="POST" action="{{ route('time-sheets.approves') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="level" value="fieldcoordinator">
                                            <input type="hidden" name="month" value="{{ $selected_month }}">
                                            <input type="hidden" name="year" value="{{ $selected_year }}">
                                            @if(!is_null($selectedScopePolicyId ?? null))
                                                <input type="hidden" name="scope_policy_id" value="{{ $selectedScopePolicyId }}">
                                            @endif
                                            <button type="submit" data-bs-original-title="@lang('timesheets.approve')" data-bs-placement="top" data-bs-toggle="tooltip"
                                                class="pull-right btn btn-yellow">
                                                <i class="ti ti-check"></i>
                                                @lang('crud.common.time_sheet_approve')
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif

                            @if($showSuperintendentStage)
                                <div class="col box text-center">
                                    <h6>@lang('timesheets.field_superintendent')</h6>
                                    <hr class="divider">
                                    @if($superintendentSigned)
                                        <div>
                                            <h6 class="container">
                                                <img src="{{ asset('storage/' . $signatures['super_intendent']['sign']) }}"
                                                    class="signature-space" class="mx-auto d-block centered">
                                            </h6>
                                            {{ $signatures['super_intendent']['name'] }}
                                        </div>
                                    @elseif(auth()->user()->hasRole('superintendent') && $canSuperintendentApprove)
                                        <form method="POST" action="{{ route('time-sheets.approves') }}" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="level" value="superintendent">
                                            <input type="hidden" name="month" value="{{ $selected_month }}">
                                            <input type="hidden" name="year" value="{{ $selected_year }}">
                                            @if(!is_null($selectedScopePolicyId ?? null))
                                                <input type="hidden" name="scope_policy_id" value="{{ $selectedScopePolicyId }}">
                                            @endif
                                            <button type="submit" data-bs-original-title="@lang('timesheets.approve')" data-bs-placement="top" data-bs-toggle="tooltip"
                                                class="pull-right btn btn-yellow">
                                                <i class="ti ti-check"></i>
                                                @lang('crud.common.time_sheet_approve')
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endif
                </footer>
                <div class="pagebreak"></div>
            @endforeach
        </div>
    </div>

@endsection
