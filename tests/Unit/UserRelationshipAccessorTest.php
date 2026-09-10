<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRelationshipAccessorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every user has an employee — the database enforces it — so these helpers
     * read straight through the link.
     */
    public function test_employee_relationship_helpers_read_through_the_link(): void
    {
        $user = User::factory()->create();
        $employee = $user->employee;

        $this->assertNotNull($employee);
        $this->assertSame($employee->center_id, $user->center());
        $this->assertSame($employee->department_id, $user->department());
        $this->assertSame($employee->location_id, $user->location());
        $this->assertSame($employee->employee_level, $user->employee_level());
        $this->assertSame($employee->management_level, $user->management_level());
    }

    public function test_signature_path_accessor_returns_null_when_signature_is_missing(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->signature_path);
    }
}
