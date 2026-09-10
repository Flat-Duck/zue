<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\TimeSheet;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeSheetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TimeSheet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'value' => 'F',
            'day' => $this->faker->date(),
            'revised_at' => $this->faker->dateTime(),
            'old_value' => $this->faker->text(255),
            'user_id' => null,
            'employee_id' => Employee::factory(),
        ];
    }
}
