<?php

namespace Database\Factories;

use App\Models\ScopeContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScopeContext>
 */
class ScopeContextFactory extends Factory
{
    protected $model = ScopeContext::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'key' => str($name)->slug('_')->toString(),
            'name' => ucfirst($name),
            'name_ar' => null,
            'carves_out_managers' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function carvingOutManagers(): static
    {
        return $this->state(fn (): array => ['carves_out_managers' => true]);
    }
}
