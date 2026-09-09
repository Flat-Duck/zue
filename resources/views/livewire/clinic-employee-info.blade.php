<div>
    <div class="card-body">
        <div class="col-12">
            <div class="card-body border-bottom py-3 row">
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="row row-cards">
                            <div class="col-4 mt-3">
                                <div class="mb-3">
                                    <label class="form-label">ZOC No :</label>
                                    <input value="{{ $employee->number }}" type="text" class="form-control" disabled>
                                </div>
                            </div>
                            <div class="col-8 mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Full Name :</label>
                                    <input value="{{ $employee->english_name }}" type="text" class="form-control"
                                        disabled>
                                </div>
                            </div>
                            <div class="col-4 mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Start Date :</label>
                                    <input value="{{ $employee->id_card_issue_date }}" type="text"
                                        class="form-control" disabled>
                                </div>
                            </div>
                            <div class="col-4 mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Administration :</label>
                                    <input value="{{ $employee->administration_name }}" type="text"
                                        class="form-control" disabled>
                                </div>
                            </div>
                            <div class="col-4 mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Department :</label>
                                    <input value="{{ $employee->department_name }}" type="text" class="form-control"
                                        disabled>
                                </div>
                            </div>
                            <div class="col-6 mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Cost Center :</label>
                                    <input value="{{ $employee->center_name }}" type="text" class="form-control"
                                        disabled>
                                </div>
                            </div>
                            <div class="col-6 mt-3">
                                <div class="mb-3">
                                    <label class="form-label">Location :</label>
                                    <input value="{{ $employee->location_name }}" type="text" class="form-control"
                                        disabled>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col auto">
                    </div>
                    <div class="col-3">
                        <button wire:click="new_apointment" class="btn btn-primary btn-sm w-100 mb-3" >
                            <i class="ti ti-plus"></i> New Ppointment
                        </button>
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">History</h3>
                            </div>
                            <div class="list-group list-group-flush overflow-auto" style="max-height: 15rem">
                                @foreach ($employee->apointments() as $group => $groupedApointments)
                                    <div class="list-group-header sticky-top" style="z-index: 1">{{ $group }}
                                    </div>
                                    @foreach ($groupedApointments as $apointment)
                                            <a href="#" wire:click="load_apointment({{ $apointment->id }})"
   class="list-group-item list-group-item-action py-2
   @if($apointment->id == $aponitment_id) active @endif">
   {{ $apointment->created_at->format('d/m/Y h:i') }}
</a>

                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Text Editors (Notepad) -->
                <!-- Text Editors (Notepad) -->
                <div class="row mt-3" wire:ignore>
                    <div class="col-md-6">
                        <label for="diagnosis" class="form-label">Diagnosis</label>
                        <textarea class="form-control" wire:model.defer="diagnosis" wire:model="diagnosis" id="diagnosis" rows="5"
                            placeholder="Write something..."></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="prescription" class="form-label">Prescription Rx</label>
                        <textarea class="form-control" wire:model.defer="prescription" wire:model="prescription" id="prescription"
                            rows="5" placeholder="Write something..."></textarea>
                    </div>

                </div>
            </div>
        </div>
    </div>
       <div class="card-footer text-end">
        <div class="d-flex">
            <a href="{{ route('passengers.index') }}" class="btn btn-outline-secondary">
                @lang('crud.common.back')
            </a>
            <button wire:click="save_apointment" type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy"></i> @lang('crud.common.update')
            </button>
        </div>
    </div>
</div>
