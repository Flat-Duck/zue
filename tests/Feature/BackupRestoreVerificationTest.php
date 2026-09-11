<?php

namespace Tests\Feature;

use App\Models\BackupLog;
use App\Models\MaintenanceSetting;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use Tests\TestCase;

class BackupRestoreVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_validation_does_not_claim_a_restore_passed(): void
    {
        $log = BackupLog::create(['type' => 'both', 'status' => 'completed', 'verification_status' => 'passed']);
        $this->assertSame('pending', $log->fresh()->restore_verification_status);
        $this->assertNull($log->fresh()->restore_verified_at);
    }

    public function test_retention_prefers_restore_proof_over_newer_file_validation(): void
    {
        Storage::fake('backups');
        MaintenanceSetting::set('keep_backups_count', 1);
        foreach (['restored', 'validated'] as $name) {
            Storage::disk('backups')->put('backup_'.$name.'.sql', 'dump');
        }
        BackupLog::create([
            'type' => 'both', 'status' => 'completed', 'filename' => 'backup_restored.sql',
            'restore_verification_status' => 'passed', 'restore_verified_at' => now()->subDay(),
        ]);
        BackupLog::create([
            'type' => 'both', 'status' => 'completed', 'filename' => 'backup_validated.sql',
            'verification_status' => 'passed', 'verified_at' => now(),
        ]);
        (new ReflectionMethod(BackupService::class, 'cleanupOldBackups'))->invoke(app(BackupService::class));
        Storage::disk('backups')->assertExists('backup_restored.sql');
        Storage::disk('backups')->assertMissing('backup_validated.sql');
    }
}
