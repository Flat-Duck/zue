<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRelationshipAccessorTest extends TestCase
{
    use RefreshDatabase;

    public function test_nullable_employee_relationship_helpers_return_null_when_user_has_no_employee(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->center());
        $this->assertNull($user->department());
        $this->assertNull($user->location());
        $this->assertNull($user->employee_level());
        $this->assertNull($user->management_level());
    }

    public function test_signature_path_accessor_returns_null_when_signature_is_missing(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->signature_path);
    }
}
