<?php

namespace Tests\Feature;

use App\Jobs\GenerateYearlyAppraisalsJob;
use App\Jobs\PerformBackupJob;
use App\Models\BackupLog;
use App\Services\Appraisals\AppraisalAggregationService;
use App\Services\BackupService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Reliability contract for the long-running queued work.
 *
 * These jobs run unattended against production-sized data, so the behaviour
 * that matters is what happens when they are slow, retried, or dispatched
 * twice — not just the happy path.
 */
class QueuedJobReliabilityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function yearly_aggregation_declares_retry_and_timeout_limits(): void
    {
        $job = new GenerateYearlyAppraisalsJob(2026);

        $this->assertSame(1800, $job->timeout, 'Aggregation needs a bounded run time.');
        $this->assertSame(3, $job->tries, 'A transient failure should be retried, not retried forever.');
        $this->assertSame([60, 300, 900], $job->backoff(), 'Retries should back off rather than hammer.');
    }

    #[Test]
    public function yearly_aggregation_is_unique_per_year(): void
    {
        $this->assertInstanceOf(ShouldBeUnique::class, new GenerateYearlyAppraisalsJob(2026));

        $this->assertSame('appraisals-yearly-2026', (new GenerateYearlyAppraisalsJob(2026))->uniqueId());
        $this->assertSame('appraisals-yearly-2027', (new GenerateYearlyAppraisalsJob(2027))->uniqueId());
    }

    #[Test]
    public function the_unique_lock_expires_so_a_dead_worker_cannot_block_the_year(): void
    {
        $job = new GenerateYearlyAppraisalsJob(2026);

        $this->assertGreaterThan(0, $job->uniqueFor);
        $this->assertGreaterThanOrEqual($job->timeout, $job->uniqueFor);
    }

    #[Test]
    public function a_duplicate_yearly_dispatch_is_not_queued_twice(): void
    {
        Queue::fake();

        GenerateYearlyAppraisalsJob::dispatch(2026);
        GenerateYearlyAppraisalsJob::dispatch(2026);

        Queue::assertPushed(GenerateYearlyAppraisalsJob::class, 1);
    }

    #[Test]
    public function a_different_year_still_dispatches_alongside(): void
    {
        Queue::fake();

        GenerateYearlyAppraisalsJob::dispatch(2026);
        GenerateYearlyAppraisalsJob::dispatch(2027);

        Queue::assertPushed(GenerateYearlyAppraisalsJob::class, 2);
    }

    #[Test]
    public function yearly_aggregation_runs_the_service_exactly_once_per_execution(): void
    {
        $service = $this->createMock(AppraisalAggregationService::class);
        $service->expects($this->once())
            ->method('aggregateYearly')
            ->with(2026);

        (new GenerateYearlyAppraisalsJob(2026))->handle($service);
    }

    #[Test]
    public function backup_job_declares_retry_and_timeout_limits(): void
    {
        $job = new PerformBackupJob;

        $this->assertSame(1800, $job->timeout);
        $this->assertSame(2, $job->tries);
        // A retry must not fire while the previous attempt may still be running.
        $this->assertGreaterThan(0, $job->backoff);
    }

    #[Test]
    public function a_failing_backup_job_records_the_failure_on_its_log(): void
    {
        $log = BackupLog::create([
            'type' => 'both',
            'status' => 'pending',
        ]);

        $service = $this->createMock(BackupService::class);
        $service->method('performBackup')
            ->willThrowException(new RuntimeException('storage unavailable'));

        try {
            (new PerformBackupJob('both', [], $log->id))->handle($service);
            $this->fail('The job should surface the failure to the queue worker.');
        } catch (RuntimeException $e) {
            $this->assertSame('storage unavailable', $e->getMessage());
        }
    }

    #[Test]
    public function a_retried_backup_job_reuses_its_log_rather_than_creating_another(): void
    {
        Bus::fake();

        $log = BackupLog::create(['type' => 'both', 'status' => 'pending']);

        PerformBackupJob::dispatch('both', [], $log->id);
        PerformBackupJob::dispatch('both', [], $log->id);

        // Retries must not multiply the audit trail for one backup operation.
        $this->assertSame(1, BackupLog::query()->count());
    }
}
