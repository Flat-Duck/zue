<?php

namespace Tests\Unit\TimeSheetAuth;

use App\Models\Center;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use App\Models\User;
use App\Services\TimeSheetAuth\ActorResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActorResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_employee_by_user_id_before_legacy_fallback(): void
    {
        $resolver = app(ActorResolver::class);

        $location = Location::factory()->create();
        $department = Department::factory()->create();
        $center = Center::factory()->create();

        $canonicalUser = User::factory()->create(['number' => 7001]);
        $canonicalEmployee = Employee::factory()->create([
            'user_id' => $canonicalUser->id,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);

        $legacyEmployee = Employee::factory()->create([
            'user_id' => null,
            'location_id' => $location->id,
            'department_id' => $department->id,
            'center_id' => $center->id,
            'archived_at' => null,
        ]);
        $legacyUser = User::factory()->create(['number' => $legacyEmployee->id]);

        $resolvedCanonical = $resolver->resolveEmployee($canonicalUser);
        $resolvedLegacy = $resolver->resolveEmployee($legacyUser);

        $this->assertNotNull($resolvedCanonical);
        $this->assertSame($canonicalEmployee->id, $resolvedCanonical->id);

        $this->assertNotNull($resolvedLegacy);
        $this->assertSame($legacyEmployee->id, $resolvedLegacy->id);
    }
}
