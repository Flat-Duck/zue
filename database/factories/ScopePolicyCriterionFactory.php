<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\ScopePolicy;
use App\Models\ScopePolicyCriterion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScopePolicyCriterion>
 */
class ScopePolicyCriterionFactory extends Factory
{
    protected $model = ScopePolicyCriterion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'policy_id' => ScopePolicy::factory(),
            'dimension' => ScopePolicyCriterion::FIELD,
            'value_id' => Location::factory(),
        ];
    }
}
