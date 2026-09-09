<?php

namespace Database\Factories;

use App\Models\FlightStation;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlightStationFactory extends Factory
{
    protected $model = FlightStation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->city(),
            'code' => strtoupper($this->faker->unique()->bothify('??#')),
            'is_field' => false,
            'is_active' => true,
        ];
    }

    public function field(): self
    {
        return $this->state(fn (): array => ['is_field' => true]);
    }
}
