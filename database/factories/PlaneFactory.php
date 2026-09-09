<?php

namespace Database\Factories;

use App\Models\Plane;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlaneFactory extends Factory
{
    protected $model = Plane::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->bothify('Plane-###'),
            'capacity' => 20,
            'lines' => $this->faker->word(),
        ];
    }

    public function seats(int $capacity): self
    {
        return $this->state(fn (): array => ['capacity' => $capacity]);
    }
}
