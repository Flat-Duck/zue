<?php

namespace Database\Factories;


use App\Models\ClinicApointment;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClinicApointmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ClinicApointment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => 9094,
            'diagnosis' => $this->faker->realText(),
            'prescription' => $this->faker->realText(),
            'date' => $this->faker->dateTime(),
        ];
    }
}
