<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Room::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => $this->faker->name(),
            'beds' => $this->faker->randomNumber(0),
            'residence_id' => \App\Models\Residence::factory(),
        ];
    }
}
