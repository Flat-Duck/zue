<?php

namespace Tests\Feature;

use App\Jobs\GenerateYearlyAppraisalsJob;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AppraisalYearlyQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_yearly_aggregation_is_queued_when_a_worker_is_configured(): void
    {
        $admin = User::factory()->create(['email' => 'admin@admin.com']);
        $this->seed(PermissionsSeeder::class);
        $this->actingAs($admin);
        Queue::fake();
        config([
            'appraisals.yearly_queue_employee_threshold' => 0,
            'queue.default' => 'database',
        ]);

        $response = $this->post(route('appraisals.periods.generate-yearly'), ['year' => 2026]);

        $response->assertRedirect();
        Queue::assertPushed(GenerateYearlyAppraisalsJob::class, function (GenerateYearlyAppraisalsJob $job): bool {
            return $job->year === 2026;
        });
    }
}
