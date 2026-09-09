@php
    $editing = isset($route);

    /*
     * Legs come from old input on a failed submit, otherwise from the route
     * being edited, otherwise a single empty row to start from.
     */
    $existingLegs = old('legs', $editing
        ? $route->legs->map(fn ($leg) => [
            'from_station_id' => $leg->from_station_id,
            'to_station_id' => $leg->to_station_id,
            'direction' => $leg->direction,
          ])->all()
        : [['from_station_id' => '', 'to_station_id' => '', 'direction' => 'coming']]);
@endphp

<div class="row">
    <x-inputs.group class="col-sm-8">
        <x-inputs.text name="name" label="Route name" required
            :value="old('name', $editing ? $route->name : '')"
            placeholder="Tripoli - 103A - Benghazi - 103A - Tripoli"></x-inputs.text>
    </x-inputs.group>

    <div class="col-sm-4">
        <div class="mb-3 mt-4">
            <label class="form-check form-switch">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                    {{ old('is_active', $editing ? $route->is_active : true) ? 'checked' : '' }}>
                <span class="form-check-label">Available to dispatchers</span>
            </label>
        </div>
    </div>
</div>

<hr>

<div x-data="routeLegs(@js($existingLegs))">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h4 class="mb-0">Legs</h4>
            <div class="text-muted small">
                In order of travel. <strong>Coming</strong> means the aircraft is arriving at a field;
                <strong>leaving</strong> means it is flying away from one. Each leg carries its own
                passenger list and its own seat count.
            </div>
        </div>
        <button type="button" class="btn btn-outline-primary btn-sm" @click="addLeg()">
            <i class="ti ti-plus"></i> Add leg
        </button>
    </div>

    @error('legs')
        <div class="alert alert-danger">{{ $message }}</div>
    @enderror

    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th style="width:8%">#</th>
                    <th style="width:30%">From</th>
                    <th style="width:30%">To</th>
                    <th style="width:22%">Direction</th>
                    <th style="width:10%"></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(leg, index) in legs" :key="index">
                    <tr>
                        <td x-text="index + 1"></td>
                        <td>
                            <select class="form-select" :name="`legs[${index}][from_station_id]`" x-model="leg.from_station_id">
                                <option value="">Select…</option>
                                @foreach ($stations as $station)
                                    <option value="{{ $station->id }}">
                                        {{ $station->name }} ({{ $station->code }}){{ $station->is_field ? ' — field' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-select" :name="`legs[${index}][to_station_id]`" x-model="leg.to_station_id">
                                <option value="">Select…</option>
                                @foreach ($stations as $station)
                                    <option value="{{ $station->id }}">
                                        {{ $station->name }} ({{ $station->code }}){{ $station->is_field ? ' — field' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-select" :name="`legs[${index}][direction]`" x-model="leg.direction">
                                <option value="coming">Coming (arriving at a field)</option>
                                <option value="leaving">Leaving (away from a field)</option>
                            </select>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    @click="removeLeg(index)" :disabled="legs.length === 1">
                                <i class="ti ti-trash"></i>
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    @foreach ($errors->get('legs.*') as $key => $messages)
        <div class="text-danger small">{{ $messages[0] }}</div>
    @endforeach
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('routeLegs', (initialLegs) => ({
            legs: initialLegs.length ? initialLegs : [{ from_station_id: '', to_station_id: '', direction: 'coming' }],

            addLeg() {
                // A new leg usually continues from where the previous one ended.
                const previous = this.legs[this.legs.length - 1];

                this.legs.push({
                    from_station_id: previous ? previous.to_station_id : '',
                    to_station_id: '',
                    direction: previous && previous.direction === 'coming' ? 'leaving' : 'coming',
                });
            },

            removeLeg(index) {
                if (this.legs.length === 1) return;
                this.legs.splice(index, 1);
            },
        }));
    });
</script>
@endpush
