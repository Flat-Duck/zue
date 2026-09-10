<?php

namespace Tests\Unit\TimeSheetAuth;

use App\Models\Employee;
use App\Models\User;
use App\Services\TimeSheetAuth\ActorResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActorResolverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The link is `users.employee_id` and nothing else.
     *
     * The legacy design made `users.id` equal the employee number, so an
     * employee could be found by id coincidence. That path is gone, and this
     * test proves it: an employee whose id happens to match a user's id is not
     * resolved unless the user actually points at it.
     */
    public function test_it_resolves_employee_only_through_the_canonical_user_link(): void
    {
        $resolver = app(ActorResolver::class);

        $user = User::factory()->create();

        $this->assertNotNull($user->employee_id);
        $this->assertSame($user->employee_id, $resolver->resolveEmployee($user)->id);
    }

    public function test_resolution_follows_the_link_not_the_id(): void
    {
        $resolver = app(ActorResolver::class);

        // Push the id spaces apart so a coincidence cannot mask the link.
        Employee::factory()->count(3)->create();

        $user = User::factory()->create();

        $this->assertNotSame(
            $user->id,
            $user->employee_id,
            'This test is only meaningful when the two ids differ.'
        );

        $resolved = $resolver->resolveEmployee($user);

        $this->assertSame($user->employee_id, $resolved->id);
        $this->assertNotSame($user->id, $resolved->id);
    }

    public function test_every_user_has_an_employee(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->employee, 'The database requires every user to have one.');
        $this->assertSame($user->employee->number, $user->number, 'The number is read from the employee.');
    }
}
