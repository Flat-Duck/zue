<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => $this->faker->unique()->numberBetween(100000, 999999),
            'job' => $this->faker->text(255),
            'english_name' => $this->faker->text(255),
            'id_card' => $this->faker->text(255),
            'id_card_issue_date' => $this->faker->date(),
            'passport' => $this->faker->text(255),
            'passport_issue_date' => $this->faker->date(),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->email(),
            'transfered_balance' => $this->faker->randomNumber(0),
            'schedule' => '5/5',
            'start_date' => now()->subYear()->toDateString(),
            'last_date' => null,
            'total_balance' => $this->faker->randomNumber(0),
            'archived_at' => null,
            'user_id' => \App\Models\User::factory(),
            'department_id' => \App\Models\Department::factory(),
            'location_id' => \App\Models\Location::factory(),
            'center_id' => \App\Models\Center::factory(),
        ];
    }
}
