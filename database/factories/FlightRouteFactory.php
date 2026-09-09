<?php

namespace Database\Factories;

use App\Models\FlightRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlightRouteFactory extends Factory
{
    protected $model = FlightRoute::class;

    public function definition(): array
    {
        return [
            'name' => 'Route '.$this->faker->unique()->numberBetween(1, 100000),
            'is_active' => true,
        ];
    }
}
