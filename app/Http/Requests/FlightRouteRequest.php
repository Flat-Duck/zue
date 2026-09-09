<?php

namespace App\Http\Requests;

use App\Models\FlightRouteLeg;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FlightRouteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $route = $this->route('flight_route');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('flight_routes', 'name')->ignore($route?->id),
            ],
            'is_active' => ['nullable', 'boolean'],

            // A route with no legs would produce a flight nobody can be booked
            // onto, so at least one is required.
            'legs' => ['required', 'array', 'min:1', 'max:20'],
            'legs.*.from_station_id' => ['required', 'integer', 'exists:flight_stations,id'],
            'legs.*.to_station_id' => ['required', 'integer', 'exists:flight_stations,id', 'different:legs.*.from_station_id'],
            'legs.*.direction' => ['required', Rule::in([FlightRouteLeg::DIRECTION_COMING, FlightRouteLeg::DIRECTION_LEAVING])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'legs.required' => 'A route needs at least one leg.',
            'legs.*.from_station_id.required' => 'Each leg needs a departure station.',
            'legs.*.to_station_id.required' => 'Each leg needs an arrival station.',
            'legs.*.direction.required' => 'Each leg must be marked as coming or leaving.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ((array) $this->input('legs', []) as $index => $leg) {
                $from = $leg['from_station_id'] ?? null;
                $to = $leg['to_station_id'] ?? null;

                if ($from && $to && (int) $from === (int) $to) {
                    $validator->errors()->add(
                        "legs.{$index}.to_station_id",
                        'A leg cannot start and finish at the same station.'
                    );
                }
            }
        });
    }
}
