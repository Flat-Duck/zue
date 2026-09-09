<div>
    @if ($flight->legs->isEmpty() && $legs->isEmpty())
        <div class="alert alert-warning">
            This flight has no legs yet. Edit the flight and choose a route.
        </div>
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
                                <i class="ti ti-printer"></i>
                                Print manifest
                            </a>

                            <p class="mb-2">
                                <strong>{{ $selectedLeg->seatsRemaining() }}</strong>
                                of {{ $selectedLeg->seat_capacity }} seats free
                                @if ($selectedLeg->isOverCapacity())
                                    <span class="badge bg-red ms-2">Over capacity</span>
                                @endif
                            </p>

                            @if ($canDispatch)
                                <div class="mb-2">
                                    <label class="form-label">Traveller type</label>
                                    <select class="form-select" wire:model.live="travellerType">
                                        <option value="employee">Employee</option>
                                        <option value="passenger">Passenger</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <label class="form-label mb-0">Traveller</label>

                                        @if ($travellerType === 'passenger' && $canCreatePassengers)
                                            <button type="button"
                                                    class="btn btn-link btn-sm p-0"
                                                    wire:click="openPassengerModal">
                                                <i class="ti ti-plus"></i> New passenger
                                            </button>
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
                                                data-placeholder="Type a name or number…">
                                            <option value="">Search…</option>
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

                                    @error('travellerId')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Note (optional)</label>
                                    <input type="text" class="form-control" wire:model="note" maxlength="255">
                                </div>

                                <button class="btn btn-primary" wire:click="addTraveller" wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="addTraveller">Add to this leg</span>
                                    <span wire:loading wire:target="addTraveller">Adding…</span>
                                </button>

                                @if ($selectedLeg->seatsRemaining() === 0)
                                    <div class="text-muted small mt-2">
                                        This leg is full. Anyone added now joins the waiting list.
                                    </div>
                                @endif
                            @else
                                <div class="text-muted">You do not have permission to seat travellers.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Seated ({{ $confirmed->count() }})</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Traveller</th>
                                        <th>Type</th>
                                        <th>Note</th>
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
                                                            wire:click="demote({{ $booking->id }})">
                                                        To waiting list
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger ms-1"
                                                            wire:click="remove({{ $booking->id }})">
                                                        Remove
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted">Nobody seated yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Waiting list ({{ $waitlisted->count() }})</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Traveller</th>
                                        <th>Type</th>
                                        <th>Note</th>
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
                                                            @disabled($selectedLeg->seatsRemaining() === 0)>
                                                        Give a seat
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger ms-1"
                                                            wire:click="remove({{ $booking->id }})">
                                                        Remove
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted">Nobody waiting.</td></tr>
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
                        <h5 class="modal-title">New passenger</h5>
                        <button type="button" class="btn-close" wire:click="closePassengerModal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Name</label>
                            <input type="text" class="form-control @error('newPassengerName') is-invalid @enderror"
                                   wire:model="newPassengerName" placeholder="Full name" autofocus>
                            @error('newPassengerName')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control @error('newPassengerCompany') is-invalid @enderror"
                                   wire:model="newPassengerCompany" placeholder="Employer near the field">
                            @error('newPassengerCompany')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label class="form-label">ID / number</label>
                                <input type="text" class="form-control @error('newPassengerNumber') is-invalid @enderror"
                                       wire:model="newPassengerNumber">
                                @error('newPassengerNumber')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-sm-6 mb-3">
                                <label class="form-label">Nationality</label>
                                <input type="text" class="form-control @error('newPassengerNationality') is-invalid @enderror"
                                       wire:model="newPassengerNationality">
                                @error('newPassengerNationality')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-link link-secondary" wire:click="closePassengerModal">
                            Cancel
                        </button>
                        <button type="button" class="btn btn-primary ms-auto"
                                wire:click="createPassenger" wire:loading.attr="disabled">
                            <i class="ti ti-plus"></i>
                            <span wire:loading.remove wire:target="createPassenger">Add passenger</span>
                            <span wire:loading wire:target="createPassenger">Adding…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
