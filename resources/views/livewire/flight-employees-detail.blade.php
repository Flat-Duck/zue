<div>
    <div class="mb-4">
        @can('create', App\Models\Employee::class)
        <button class="btn btn-primary" wire:click="newEmployee">
            <i class="ti ti-plus"></i>
            @lang('crud.common.attach')
        </button>
        @endcan
    </div>

    <x-modal id="{{$id}}" wire:model.live="showingModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $modalTitle }}</h5>
                <button
                    type="button"
                    class="btn-close"
                    data-dismiss="modal"
                    aria-label="@lang('ui.close')"
                    data-bs-dismiss="modal"
                >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <div>
                    <x-inputs.group class="col-sm-12">
                        <x-inputs.select
                            name="employee_id"
                            label="Employee"
                            wire:model.live="employee_id"
                        >
                            <option value="null" disabled>@lang('ui.please_select_the_employee')</option>
                            @foreach($employeesForSelect as $value => $label)
                            <option value="{{ $value }}"  >{{ $label }}</option>
                            @endforeach
                        </x-inputs.select>
                    </x-inputs.group>
                </div>
            </div>

            <div class="modal-footer">
                <button
                    type="button"
                    
                    data-dismiss="modal"
                    aria-label="@lang('ui.close')"
                    data-bs-dismiss="modal"
                    class="btn me-auto"
                    wire:click="$toggle('showingModal')"
                >
                    <i class="ti ti-x"></i>
                    @lang('crud.common.cancel')
                </button>

                <button type="button" class="btn btn-primary" wire:click="save">
                    <i class="ti ti-device-floppy"></i>
                    @lang('crud.common.save')
                </button>
            </div>
        </div>
    </x-modal>

    <div class="table-responsive">
        <table class="table" id="dataTable" width="100%" cellspacing="0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>@lang('crud.flight_employees.inputs.employee_id')</th>
                    <th>@lang('crud.flight_employees.inputs.name')</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($flightEmployees as $k=> $employee)
                <tr>
                    <td>{{ $k+1 }}</td>
                    <td>{{ $employee->number ?? '-' }}</td>
                    <td>{{ $employee->english_name ?? '-' }}</td>
                    <td>
                        <div
                            role="group"
                            aria-label="@lang('ui.row_actions')"
                            class="relative inline-flex align-middle"
                        >
                            @can('delete-any', App\Models\Employee::class)
                            <button
                                class="btn btn-danger"
                                onclick="confirm({{ Js::from(__('ui.confirm_generic')) }}) || event.stopImmediatePropagation()"
                                wire:click="detach({{ $employee->id }})"
                            >
                                <i class="ti ti-trash"></i>
                                @lang('crud.common.detach')
                            </button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if( $flightEmployees->hasPages() )
    <div class="card-footer pb-0">{{ $flightEmployees->links() }}</div>
    @endif
</div>
