<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * The audit trail records who did what, without duplicating the sensitive
 * content itself into a log file that outlives the data.
 */
class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<array{level: string, message: string, context: array<string, mixed>}> */
    private array $entries = [];

    /**
     * Capture what would be written to the audit channel.
     */
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

    #[Test]
    public function the_audit_channel_is_configured_with_long_retention(): void
    {
        $channel = config('logging.channels.audit');

        $this->assertNotNull($channel, 'A dedicated audit channel must exist.');
        $this->assertSame('daily', $channel['driver']);
        $this->assertGreaterThanOrEqual(365, $channel['days'], 'Audit history must outlive the ordinary application log.');
        $this->assertStringContainsString('audit.log', $channel['path']);
    }

    #[Test]
    public function an_event_records_the_actor_and_the_action(): void
    {
        $user = User::factory()->create(['name' => 'Auditor']);
        $this->actingAs($user);

        $this->captureAuditChannel();

        app(AuditLogger::class)->record('backup.deleted', ['filename' => 'backup_x.sql']);

        $this->assertCount(1, $this->entries);
        $entry = $this->entries[0];

        $this->assertSame('backup.deleted', $entry['message']);
        $this->assertSame($user->id, $entry['context']['actor_id']);
        $this->assertSame('Auditor', $entry['context']['actor_name']);
        $this->assertSame('backup_x.sql', $entry['context']['data']['filename']);
        $this->assertArrayHasKey('at', $entry['context']);
    }

    #[Test]
    public function failures_are_recorded_at_warning_level_with_a_reason(): void
    {
        $this->actingAs(User::factory()->create());
        $this->captureAuditChannel();

        app(AuditLogger::class)->recordFailure('database.restore_failed', 'syntax error', [
            'filename' => 'backup_y.sql',
        ]);

        $entry = $this->entries[0];

        $this->assertSame('warning', $entry['level']);
        $this->assertSame('syntax error', $entry['context']['reason']);
    }

    #[Test]
    public function sensitive_values_are_redacted(): void
    {
        $this->actingAs(User::factory()->create());
        $this->captureAuditChannel();

        app(AuditLogger::class)->record('user.created', [
            'user_id' => 7,
            'password' => 'super-secret',
            'diagnosis' => 'confidential finding',
            'prescription' => 'confidential treatment',
            'national_id' => '1234567890',
        ]);

        $data = $this->entries[0]['context']['data'];

        $this->assertSame(7, $data['user_id'], 'Non-sensitive values are kept.');

        foreach (['password', 'diagnosis', 'prescription', 'national_id'] as $key) {
            $this->assertSame(AuditLogger::REDACTED, $data[$key], "[$key] must be redacted.");
        }
    }

    #[Test]
    public function redaction_reaches_nested_values(): void
    {
        $this->actingAs(User::factory()->create());
        $this->captureAuditChannel();

        app(AuditLogger::class)->record('import.completed', [
            'rows' => [
                ['name' => 'Someone', 'password' => 'nested-secret'],
            ],
        ]);

        $row = $this->entries[0]['context']['data']['rows'][0];

        $this->assertSame('Someone', $row['name']);
        $this->assertSame(AuditLogger::REDACTED, $row['password']);
    }

    #[Test]
    public function deleting_a_backup_is_audited_through_the_real_request(): void
    {
        Storage::fake('backups');
        Storage::disk('backups')->put('backup_20260101_000000_a.sql', 'SET FOREIGN_KEY_CHECKS=0;');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Permission::findOrCreate('maintenance', 'web');
        $user = User::factory()->create();
        $user->givePermissionTo('maintenance');

        $this->actingAs($user);
        $this->captureAuditChannel();

        $this->delete(route('maintenance.delete', ['filename' => 'backup_20260101_000000_a.sql']));

        $messages = array_column($this->entries, 'message');
        $this->assertContains('backup.deleted', $messages);
    }

    #[Test]
    public function sensitive_inputs_are_never_flashed_back_to_the_session(): void
    {
        $handler = new Handler(app());

        $reflection = new \ReflectionProperty($handler, 'dontFlash');
        $reflection->setAccessible(true);
        $dontFlash = $reflection->getValue($handler);

        foreach (['password', 'password_confirmation', 'diagnosis', 'prescription', 'national_id'] as $key) {
            $this->assertContains($key, $dontFlash, "[$key] must never be flashed.");
        }
    }
}
