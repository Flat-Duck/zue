<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Impersonation lets a super-admin act as somebody else, so it is exactly the
 * kind of event an audit trail exists for.
 */
class ImpersonationAuditTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    private array $entries = [];

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('super-admin', 'web');
    }

    private function captureAuditChannel(): void
    {
        $this->entries = [];

        $spy = new class($this->entries) extends Logger
        {
            public function __construct(private array &$captured)
            {
                // Intentionally does not call parent::__construct().
            }

            public function info($message, array $context = []): void
            {
                $this->captured[] = ['level' => 'info', 'message' => $message, 'context' => $context];
            }

            public function warning($message, array $context = []): void
            {
                $this->captured[] = ['level' => 'warning', 'message' => $message, 'context' => $context];
            }
        };

        Log::shouldReceive('channel')->with('audit')->andReturn($spy);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function entry(string $message): ?array
    {
        foreach ($this->entries as $entry) {
            if ($entry['message'] === $message) {
                return $entry;
            }
        }

        return null;
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['name' => 'Root Admin']);
        $user->assignRole('super-admin');

        return $user;
    }

    #[Test]
    public function starting_impersonation_is_audited_against_the_real_actor(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create(['name' => 'Target Person']);

        $this->actingAs($admin);
        $this->captureAuditChannel();

        $this->post(route('users.impersonate', $target))->assertRedirect();

        $entry = $this->entry('impersonation.started');

        $this->assertNotNull($entry, 'Starting impersonation must be recorded.');
        $this->assertSame($admin->id, $entry['context']['data']['impersonator_id']);
        $this->assertSame('Root Admin', $entry['context']['data']['impersonator_name']);
        $this->assertSame($target->id, $entry['context']['data']['target_user_id']);
        $this->assertSame('Target Person', $entry['context']['data']['target_user_name']);

        // Recorded before the session switched, so the actor is the admin.
        $this->assertSame($admin->id, $entry['context']['actor_id']);
    }

    #[Test]
    public function a_refused_impersonation_attempt_is_audited(): void
    {
        $ordinary = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($ordinary);
        $this->captureAuditChannel();

        $this->post(route('users.impersonate', $target))->assertForbidden();

        $entry = $this->entry('impersonation.denied');

        $this->assertNotNull($entry, 'A refused attempt must be recorded.');
        $this->assertSame('warning', $entry['level']);
        $this->assertSame($ordinary->id, $entry['context']['actor_id']);
        $this->assertSame($target->id, $entry['context']['data']['target_user_id']);
    }

    #[Test]
    public function stopping_impersonation_is_audited(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create(['name' => 'Target Person']);

        $this->actingAs($admin)->post(route('users.impersonate', $target));

        $this->captureAuditChannel();

        $this->delete(route('users.impersonate.stop'))->assertRedirect();

        $entry = $this->entry('impersonation.stopped');

        $this->assertNotNull($entry, 'Ending impersonation must be recorded.');
        $this->assertSame($admin->id, $entry['context']['data']['impersonator_id']);
        $this->assertSame($target->id, $entry['context']['data']['target_user_id']);
    }

    #[Test]
    public function stopping_without_an_active_session_is_audited_as_a_failure(): void
    {
        $this->actingAs(User::factory()->create());
        $this->captureAuditChannel();

        $this->delete(route('users.impersonate.stop'));

        $entry = $this->entry('impersonation.stop_failed');

        $this->assertNotNull($entry);
        $this->assertSame('warning', $entry['level']);
    }

    /**
     * The point of the whole feature: while impersonating, an audited action
     * must not be attributed solely to the person being impersonated.
     */
    #[Test]
    public function actions_taken_while_impersonating_name_the_real_operator(): void
    {
        $admin = $this->superAdmin();
        $target = User::factory()->create(['name' => 'Target Person']);

        $this->actingAs($admin)->post(route('users.impersonate', $target));

        $this->captureAuditChannel();

        app(AuditLogger::class)->record('backup.deleted', ['filename' => 'backup_x.sql']);

        $entry = $this->entry('backup.deleted');

        $this->assertNotNull($entry);
        $this->assertTrue($entry['context']['impersonated']);
        $this->assertSame($admin->id, $entry['context']['impersonator_id']);
        $this->assertSame('Root Admin', $entry['context']['impersonator_name']);

        // The effective user is still recorded, so both sides are visible.
        $this->assertSame($target->id, $entry['context']['actor_id']);
    }

    #[Test]
    public function ordinary_actions_carry_no_impersonation_markers(): void
    {
        $this->actingAs(User::factory()->create());
        $this->captureAuditChannel();

        app(AuditLogger::class)->record('backup.deleted', ['filename' => 'backup_x.sql']);

        $entry = $this->entry('backup.deleted');

        $this->assertArrayNotHasKey('impersonated', $entry['context']);
        $this->assertArrayNotHasKey('impersonator_id', $entry['context']);
    }
}
