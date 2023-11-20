<?php

namespace Database\Factories;

use App\Models\Flight;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlightFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Flight::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'Air',
            'date' => $this->faker->date(),
            'time' => $this->faker->time(),
        ];
    }
}
