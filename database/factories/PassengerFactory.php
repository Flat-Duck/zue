<?php

namespace Database\Factories;

use App\Models\Passenger;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class PassengerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Passenger::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'company' => $this->faker->text(255),
            'number' => $this->faker->text(255),
            'nationality' => $this->faker->text(255),
        ];
    }
}
