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
                                <input value="{{ $employee->number}}" type="text" class="form-control" disabled >
                            </div>
                        </div>
                        <div class="col-8 mt-3">
                            <div class="mb-3">
                                <label class="form-label">Full Name :</label>
                                <input value="{{ $employee->english_name}}" type="text" class="form-control" disabled >
                            </div>
                        </div>
                        <div class="col-4 mt-3">
                            <div class="mb-3">
                                <label class="form-label">Start Date :</label>
                                <input value="{{ $employee->id_card_issue_date}}" type="text" class="form-control" disabled >
                            </div>
                        </div>
                        <div class="col-4 mt-3">
                            <div class="mb-3">
                                <label class="form-label">Administration :</label>
                                <input value="{{ $employee->administration_name }}" type="text" class="form-control" disabled >
                            </div>
                        </div>
                        <div class="col-4 mt-3">
                            <div class="mb-3">
                                <label class="form-label">Department :</label>
                                <input value="{{ $employee->department_name }}" type="text" class="form-control" disabled >
                            </div>
                        </div>
                        <div class="col-6 mt-3">
                            <div class="mb-3">
                                <label class="form-label">Cost Center :</label>
                                <input value="{{ $employee->center_name }}" type="text" class="form-control" disabled >
                            </div>
                        </div>
                        <div class="col-6 mt-3">
                            <div class="mb-3">
                                <label class="form-label">Location :</label>
                                <input value="{{ $employee->location_name }}" type="text" class="form-control" disabled >
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col auto">
                </div>
                <div class="col-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">History</h3>
                        </div>
                        <div class="list-group list-group-flush overflow-auto" style="max-height: 15rem">
                            @foreach ($employee->apointments() as $group => $groupedApointments)
                                <div class="list-group-header sticky-top" style="z-index: 1">{{$group}}</div>
                                @foreach ($groupedApointments as $apointment)
                                    <a href="#"
                                    {{-- wire:confirm="Do you want to load this date diagnosis you will lose unsaved data?" --}}
                                    wire:click="load_apointment({{ $apointment->id }})"
                                    class="list-group-item list-group-item-action py-2">{{ $apointment->created_at->format('d/m/Y h:m') }}</a>
                                @endforeach
                            @endforeach
{{-- 
                              <a href="#" class="list-group-item list-group-item-action py-2">A fourth link item</a>
                              <a class="list-group-item list-group-item-action disabled">A disabled link item</a>
                              <div class="list-group-header sticky-top">W</div> --}}
                        </div>
                    </div>
                </div>
            </div>
            <!-- Text Editors (Notepad) -->
           <!-- Text Editors (Notepad) -->
           <div class="row mt-3" wire:ignore >
            <div class="col-md-6" >
                <label for="diagnosis" class="form-label">Diagnosis</label>
                <textarea  class="form-control" wire:model.defer="diagnosis" wire:model="diagnosis" id="diagnosis" rows="5" placeholder="Write something..."></textarea>
            </div>
            <div class="col-md-6" >
                <label for="prescription" class="form-label">Prescription Rx</label>
                <textarea  class="form-control" wire:model.defer="prescription" wire:model="prescription" id="prescription" rows="5" placeholder="Write something..."></textarea>
            </div>
         
        </div>

            {{-- <div class="col-3">
            </div>
            <div class="col-6">
            
                
                <form method="POST" action="{{ route('admin.reports.minus') }}">
                    @csrf
                    <div class="row mb-3">
                        <label class="form-label">Only Employees With Minus</label>
                        <button type="submit" class="btn btn-primary">Get Run </button>
                    </div>
                </form>
                <hr>
                <br/>
                <form method="POST" action="{{ route('admin.reports.to_date') }}">
                    @csrf
                    <div class="row mb-3">
                        <label class="form-label">Fieldbreak Balance To Date</label>
                        
                        <x-inputs.group class="col-sm-12">
                            <x-inputs.select name="department_id" label="Department" required>
                                @php $selected = old('department_id') @endphp
                                <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Department</option>
                                @foreach($departments as $value => $label)
                                <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
                                @endforeach
                            </x-inputs.select>
                        </x-inputs.group>
                        <x-inputs.group class="col-sm-12">
                            <x-inputs.select name="center_id" label="Center" required>
                                @php $selected = old('center_id') @endphp
                                <option disabled {{ empty($selected) ? 'selected' : '' }}>Please select the Center</option>
                                @foreach($centers as $value => $label)
                                <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }} >{{ $label }}</option>
                                @endforeach
                            </x-inputs.select>
                        </x-inputs.group>
                        <div class="mb-3">
                            <label class="form-label @error('print_type') is-invalid @enderror" for="printer">Print Type</label>
                            <div class="btn-group w-100" id="priter" role="group">
                                <input type="radio" class="btn-check" name="print_type" id="pdf" value="pdf" autocomplete="off">
                                <label for="pdf" class="btn btn-icon">
                                    <i class="ti ti-file-type-pdf"></i> PDF
                                </label>
                                <input type="radio" class="btn-check" name="print_type" id="excel" value="excel" autocomplete="off">
                                <label for="excel" class="btn btn-icon">
                                    <i class="ti ti-file-type-xls"></i> Excel
                                </label>
                            </div>
                            @error('print_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <x-inputs.group class="col-sm-12">
                            <x-inputs.date
                            name="date"
                            label="Date"
                            value="{{ old('date', (now())->format('Y-m-d')) }}"
                            max="255"
                            ></x-inputs.date>
                        </x-inputs.group>
                        <button type="submit" class="btn btn-primary">Get Run </button>
                    </div>
                </form>
            </div>
            <div class="col-3">
            </div> --}}
        </div>
    </div>
</div>
<div class="card-footer text-end">
    <div class="d-flex">
        <a href="{{ route('time-sheets.index') }}" class="btn btn-outline-secondary">@lang('crud.common.back')</a>
    </div>
</div>
</div>
