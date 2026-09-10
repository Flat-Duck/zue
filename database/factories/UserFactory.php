<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Every user is an employee, so the factory makes one by default.
            // Pass `employee_id` explicitly to attach an existing employee.
            'employee_id' => Employee::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique->email(),
            'email_verified_at' => now(),
            'password' => \Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Attach the account to an employee carrying a specific number.
     *
     * The number lives on the employee, so a test that cares about it says so
     * here rather than setting a column the user no longer has.
     */
    public function forEmployeeNumber(int $number): static
    {
        return $this->state(fn (): array => [
            'employee_id' => Employee::factory()->create(['number' => $number])->id,
        ]);
    }

    /**
     * Attach the account to an employee that already exists.
     */
    public function forEmployee(Employee $employee): static
    {
        return $this->state(fn (): array => ['employee_id' => $employee->id]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }
}
