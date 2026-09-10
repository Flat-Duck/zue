<?php

namespace Database\Factories;

use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => $this->faker->unique()->numberBetween(100000, 999999),
            'job' => $this->faker->text(255),
            'english_name' => $this->faker->text(255),
            'transfered_balance' => $this->faker->randomNumber(0),
            'schedule' => '5/5',
            'start_date' => now()->subYear()->toDateString(),
            'last_date' => null,
            'total_balance' => $this->faker->randomNumber(0),
            'archived_at' => null,
            'department_id' => Department::factory(),
            'location_id' => Location::factory(),
            'center_id' => Center::factory(),
        ];
    }

    /**
     * Every employee has an HR profile, so the factory makes one too. Its contents
     * are deliberately sparse: a test that cares about a profile field should say so
     * with {@see withProfile()} rather than depend on what the faker happened to pick.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Employee $employee): void {
            if (! $employee->details()->exists()) {
                $employee->details()->create([]);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function withProfile(array $attributes): static
    {
        return $this->afterCreating(function (Employee $employee) use ($attributes): void {
            $employee->details()->updateOrCreate([], $attributes);
            $employee->unsetRelation('details');
        });
    }
}
