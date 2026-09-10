<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeDetail>
 */
class EmployeeDetailFactory extends Factory
{
    protected $model = EmployeeDetail::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'nationality' => $this->faker->country(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
        ];
    }
}
