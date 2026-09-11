<div>
    @if ($flight->legs->isEmpty() && $legs->isEmpty())
        <div class="alert alert-warning"> @lang('ui.this_flight_has_no_legs_yet_edit_the_flight') </div>
    @else
        @if (session('manifest_status'))
            <div class="alert alert-info" wire:key="status-{{ md5(session('manifest_status')) }}">
                {{ session('manifest_status') }}
            </div>
        @endif

        @error('manifest')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        {{-- One tab per leg: each carries its own seat count. --}}
        <ul class="nav nav-tabs mb-3">
            @foreach ($legs as $leg)
                <li class="nav-item" wire:key="leg-tab-{{ $leg->id }}">
                    <a href="#"
                       class="nav-link {{ $selectedLeg && $selectedLeg->id === $leg->id ? 'active' : '' }}"
                       wire:click.prevent="selectLeg({{ $leg->id }})">
                        <span class="badge bg-{{ $leg->direction === 'coming' ? 'green' : 'blue' }} me-2">
                            {{ $leg->direction === 'coming' ? 'Coming' : 'Leaving' }}
                        </span>
                        {{ $leg->label() }}
                        <span class="text-muted ms-2">
                            {{ $leg->bookings->where('status', 'confirmed')->count() }}/{{ $leg->seat_capacity }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        @if ($selectedLeg)
            <div class="row">
                <div class="col-md-5">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">
                                {{ $selectedLeg->label() }}
                                &mdash;
                                {{ $selectedLeg->direction === 'coming' ? 'coming list' : 'leaving list' }}
                            </h3>
                        </div>
                        <div class="card-body">
                            <a href="{{ route('flights.manifest', [$flight, $selectedLeg]) }}"
                               target="_blank"
                               class="btn btn-outline-primary btn-sm mb-3">
                                <i class="ti ti-printer"></i> @lang('ui.print_manifest') </a>

                            <p class="mb-2">
                                {!! __('ui.seats_free', [
                                    'free' => '<strong>'.e($selectedLeg->seatsRemaining()).'</strong>',
                                    'total' => e($selectedLeg->seat_capacity),
                                ]) !!}
                                @if ($selectedLeg->isOverCapacity())
                                    <span class="badge bg-red ms-2">@lang('ui.over_capacity')</span>
                                @endif
                            </p>

                            @if ($canDispatch)
                                <div class="mb-2">
                                    <label class="form-label">@lang('ui.traveller_type')</label>
                                    <select class="form-select" wire:model.live="travellerType">
                                        <option value="employee">@lang('ui.employee')</option>
                                        <option value="passenger">@lang('ui.passenger')</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label class="form-label mb-0">@lang('ui.traveller')</label>

                                        @if ($travellerType === 'passenger' && $canCreatePassengers)
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0"
                                                    wire:click="openPassengerModal">
                                                <i class="ti ti-plus"></i> @lang('ui.new_passenger') </button>
                                        @endif
                                    </div>

                                    {{-- Tom Select (Tabler's advanced select) rewrites the DOM
                                         around this element, so Livewire must leave it alone.
                                         The Alpine bridge reports the value back. --}}
                                    <div wire:ignore
                                         wire:key="traveller-select-{{ $travellerType }}-{{ $selectedLeg->id }}"
                                         x-data="searchableSelect(@js($travellerId))">
                                        <select x-ref="select"
                                                class="form-select"
                                                data-model="travellerId"
                                                data-placeholder="@lang('ui.type_a_name_or_number')">
                                            <option value="">@lang('ui.search_3')</option>
                                            @if ($travellerType === 'employee')
                                                @foreach ($employeeOptions as $employee)
                                                    <option value="{{ $employee->id }}">
                                                        {{ $employee->number }} — {{ $employee->english_name }}
                                                    </option>
                                                @endforeach
                                            @else
                                                @foreach ($passengerOptions as $passenger)
                                                    <option value="{{ $passenger->id }}">
                                                        {{ $passenger->name }}{{ $passenger->company ? ' — '.$passenger->company : '' }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    @if ($travellerType === 'employee' && $employeeOptions->isEmpty())
                                        <div class="text-secondary small mt-1">
                                            @lang('ui.no_dispatcher_scope')
                                        </div>
                                    @endif

                                    @error('travellerId')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">@lang('ui.note_optional')</label>
                                    <input type="text" class="form-control" wire:model="note" maxlength="255">
                                </div>

                                <button class="btn btn-primary" wire:click="addTraveller" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="addTraveller">@lang('ui.add_to_this_leg')</span>
                                    <span wire:loading wire:target="addTraveller">@lang('ui.adding')</span>
                                </button>

                                @if ($selectedLeg->seatsRemaining() === 0)
                                    <div class="text-muted small mt-2"> @lang('ui.this_leg_is_full_anyone_added_now_joins_the') </div>
                                @endif
                            @else
                                <div class="text-muted">@lang('ui.you_do_not_have_permission_to_seat')</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">@lang('ui.seated_count', ['count' => $confirmed->count()])</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>@lang('ui.traveller')</th>
                                        <th>@lang('ui.type')</th>
                                        <th>@lang('ui.note')</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($confirmed as $index => $booking)
                                        <tr wire:key="confirmed-{{ $booking->id }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $booking->travellerName() }}</td>
                                            <td>{{ class_basename($booking->bookable_type) }}</td>
                                            <td>{{ $booking->note ?? '-' }}</td>
                                            <td class="text-end">
                                                @if ($canDispatch)
                                                    <button class="btn btn-sm btn-outline-secondary"
                                                            wire:click="demote({{ $booking->id }})"> @lang('ui.to_waiting_list') </button>
                                                    <button class="btn btn-sm btn-outline-danger ms-1"
                                                            wire:click="remove({{ $booking->id }})"> @lang('ui.remove') </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted">@lang('ui.nobody_seated_yet')</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">@lang('ui.waiting_list_count', ['count' => $waitlisted->count()])</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>@lang('ui.traveller')</th>
                                        <th>@lang('ui.type')</th>
                                        <th>@lang('ui.note')</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($waitlisted as $index => $booking)
                                        <tr wire:key="waiting-{{ $booking->id }}">
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $booking->travellerName() }}</td>
                                            <td>{{ class_basename($booking->bookable_type) }}</td>
                                            <td>{{ $booking->note ?? '-' }}</td>
                                            <td class="text-end">
                                                @if ($canDispatch)
                                                    <button class="btn btn-sm btn-primary"
                                                            wire:click="promote({{ $booking->id }})"
                                                            @disabled($selectedLeg->seatsRemaining() === 0)> @lang('ui.give_a_seat') </button>
                                                    <button class="btn btn-sm btn-outline-danger ms-1"
                                                            wire:click="remove({{ $booking->id }})"> @lang('ui.remove') </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted">@lang('ui.nobody_waiting')</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
    {{-- Quick add: passengers work for the companies around the field and are
         usually created the moment they turn up for a flight. --}}
    @if ($showPassengerModal)
        <div class="modal modal-blur fade show d-block" tabindex="-1" role="dialog" wire:key="passenger-modal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">@lang('ui.new_passenger')</h5>
                        <button type="button" class="btn-close" wire:click="closePassengerModal" aria-label="@lang('ui.close')"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">@lang('ui.name')</label>
                            <input type="text" class="form-control @error('newPassengerName') is-invalid @enderror"
                                   wire:model="newPassengerName" placeholder="@lang('ui.full_name_2')" autofocus>
                            @error('newPassengerName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">@lang('ui.company')</label>
                            <input type="text" class="form-control @error('newPassengerCompany') is-invalid @enderror"
                                   wire:model="newPassengerCompany" placeholder="@lang('ui.employer_near_the_field')">
                            @error('newPassengerCompany')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label class="form-label">@lang('ui.id_number')</label>
                                <input type="text" class="form-control @error('newPassengerNumber') is-invalid @enderror"
                                       wire:model="newPassengerNumber">
                                @error('newPassengerNumber')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-sm-6 mb-3">
                                <label class="form-label">@lang('ui.nationality')</label>
                                <input type="text" class="form-control @error('newPassengerNationality') is-invalid @enderror"
                                       wire:model="newPassengerNationality">
                                @error('newPassengerNationality')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" wire:click="closePassengerModal"> @lang('ui.cancel') </button>
                        <button type="button" class="btn btn-primary ms-auto"
                                wire:click="createPassenger" wire:loading.attr="disabled">
                            <i class="ti ti-plus"></i>
                            <span wire:loading.remove wire:target="createPassenger">@lang('ui.add_passenger')</span>
                            <span wire:loading wire:target="createPassenger">@lang('ui.adding')</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
