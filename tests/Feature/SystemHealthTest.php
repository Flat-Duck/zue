<?php

namespace Tests\Feature;

use App\Jobs\HeartbeatJob;
use App\Models\MaintenanceSetting;
use App\Models\User;
use App\Services\Health\HealthCheck;
use App\Services\Health\Heartbeat;
use App\Services\Health\SystemHealth;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The server saying whether it is well.
 *
 * With no mail on the network, a dead worker or a full disk is discovered when
 * somebody notices approvals have stopped. These checks are the alternative:
 * the status page for a person, `/health` for a monitor, and a log line every
 * ten minutes for whoever reads the log.
 */
class SystemHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Storage::fake('backups');
        config([
            'queue.default' => 'database',
            // The machine running the tests may itself be nearly full.
            'health.disk_warning_percent' => 100,
            'health.disk_failing_percent' => 101,
        ]);
    }

    private function admin(): User
    {
        $this->seed(PermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    /**
     * @return array<string, HealthCheck>
     */
    private function checks(): array
    {
        return collect(app(SystemHealth::class)->checks())->keyBy('key')->all();
    }

    private function everythingWell(): void
    {
        $heartbeat = app(Heartbeat::class);
        $heartbeat->beat(Heartbeat::SCHEDULER);
        $heartbeat->beat(Heartbeat::QUEUE);

        Storage::disk('backups')->put('backup_now.sql', 'x');
        MaintenanceSetting::set('auto_backup_enabled', '1');
    }

    #[Test]
    public function a_process_that_has_never_beaten_is_reported_as_down(): void
    {
        $checks = $this->checks();

        $this->assertSame('failing', $checks['scheduler']->status);
        $this->assertSame('failing', $checks['queue_worker']->status);
        $this->assertStringContainsString('never seen', $checks['queue_worker']->detail);
    }

    #[Test]
    public function a_stale_heartbeat_is_reported_as_down_and_a_fresh_one_as_up(): void
    {
        $heartbeat = app(Heartbeat::class);

        $this->travelTo(now()->subMinutes(config('health.stale_after_minutes') + 1));
        $heartbeat->beat(Heartbeat::SCHEDULER);
        $this->travelBack();

        $this->assertSame('failing', $this->checks()['scheduler']->status);

        $heartbeat->beat(Heartbeat::SCHEDULER);

        $this->assertSame('ok', $this->checks()['scheduler']->status);
    }

    #[Test]
    public function the_heartbeat_job_is_what_proves_the_worker_is_alive(): void
    {
        $this->assertSame('failing', $this->checks()['queue_worker']->status);

        (new HeartbeatJob)->handle(app(Heartbeat::class));

        $this->assertSame('ok', $this->checks()['queue_worker']->status);
    }

    #[Test]
    public function a_synchronous_queue_is_a_warning_not_a_failure(): void
    {
        config(['queue.default' => 'sync']);

        $this->assertSame('warning', $this->checks()['queue_worker']->status);
    }

    #[Test]
    public function failed_jobs_are_a_failure(): void
    {
        $this->assertSame('ok', $this->checks()['failed_jobs']->status);

        DB::table('failed_jobs')->insert([
            'uuid' => 'x', 'connection' => 'database', 'queue' => 'default',
            'payload' => '{}', 'exception' => 'boom', 'failed_at' => now(),
        ]);

        $check = $this->checks()['failed_jobs'];
        $this->assertSame('failing', $check->status);
        $this->assertStringContainsString('1 job(s) failed', $check->detail);
    }

    #[Test]
    public function a_missing_or_stale_backup_is_a_failure_and_switched_off_backups_are_a_warning(): void
    {
        $this->assertSame('failing', $this->checks()['last_backup']->status);

        Storage::disk('backups')->put('backup_old.sql', 'x');
        MaintenanceSetting::set('auto_backup_enabled', '1');
        touch(Storage::disk('backups')->path('backup_old.sql'), time() - (config('health.backup_stale_after_hours') + 1) * 3600);

        $stale = $this->checks()['last_backup'];
        $this->assertSame('failing', $stale->status);
        $this->assertStringContainsString('missed', $stale->detail);

        Storage::disk('backups')->put('backup_fresh.sql', 'x');
        $this->assertSame('ok', $this->checks()['last_backup']->status);

        MaintenanceSetting::set('auto_backup_enabled', '0');
        $this->assertSame('warning', $this->checks()['last_backup']->status);
    }

    #[Test]
    public function the_monitor_endpoint_answers_503_when_something_fails_and_says_nothing_to_strangers(): void
    {
        $response = $this->getJson(route('health'));

        $response->assertStatus(503)->assertExactJson(['status' => 'failing']);

        $this->everythingWell();

        $this->getJson(route('health'))->assertOk()->assertExactJson(['status' => 'ok']);
    }

    #[Test]
    public function the_monitor_endpoint_gives_detail_to_someone_with_the_maintenance_permission(): void
    {
        $this->everythingWell();

        $this->actingAs($this->admin())
            ->getJson(route('health'))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonFragment(['key' => 'queue_worker', 'status' => 'ok']);
    }

    #[Test]
    public function the_status_page_shows_every_check(): void
    {
        $this->everythingWell();

        $this->actingAs($this->admin())
            ->get(route('maintenance.status'))
            ->assertOk()
            ->assertSee(__('maintenance.status_all_well'))
            ->assertSee(__('maintenance.check_queue_worker'))
            ->assertSee(__('maintenance.check_last_backup'));
    }

    #[Test]
    public function the_status_page_is_for_maintainers_only(): void
    {
        $this->seed(PermissionsSeeder::class);

        $this->actingAs(User::factory()->create())
            ->get(route('maintenance.status'))
            ->assertForbidden();
    }

    /**
     * The scheduled report is the alert: with no mail, the log is the only place
     * a failure can be written down.
     */
    #[Test]
    public function the_scheduled_report_writes_each_failing_check_to_the_log(): void
    {
        Log::shouldReceive('critical')->atLeast()->once()->withArgs(fn (string $line): bool => str_contains($line, 'queue_worker'));
        Log::shouldReceive('critical')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();

        app(SystemHealth::class)->report();
    }
}
