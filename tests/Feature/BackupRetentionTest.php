<?php

namespace Tests\Feature;

use App\Models\BackupLog;
use App\Models\MaintenanceSetting;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * Retention and verification behaviour for the backup subsystem.
 *
 * The rule under test: a backup that has not been verified must never be
 * allowed to displace one that has. Losing the last known-good backup is the
 * failure that makes every other backup feature pointless.
 */
class BackupRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('backups');
    }

    private function writeBackup(string $filename, int $ageInMinutes = 0): string
    {
        $path = $filename;

        Storage::disk('backups')->put($path, "SET FOREIGN_KEY_CHECKS=0;\nSET FOREIGN_KEY_CHECKS=1;\n");

        // Older files must sort earlier for the mtime-ordered retention pass.
        touch(Storage::disk('backups')->path($path), now()->subMinutes($ageInMinutes)->timestamp);

        return $path;
    }

    private function runCleanup(): void
    {
        $method = new ReflectionMethod(BackupService::class, 'cleanupOldBackups');
        $method->setAccessible(true);
        $method->invoke(app(BackupService::class));
    }

    #[Test]
    public function retention_keeps_the_newest_backups_when_nothing_is_verified(): void
    {
        MaintenanceSetting::set('keep_backups_count', 2);

        $oldest = $this->writeBackup('backup_20260101_000000_a.sql', 300);
        $middle = $this->writeBackup('backup_20260102_000000_b.sql', 200);
        $newest = $this->writeBackup('backup_20260103_000000_c.sql', 100);

        $this->runCleanup();

        Storage::disk('backups')->assertMissing($oldest);
        Storage::disk('backups')->assertExists($middle);
        Storage::disk('backups')->assertExists($newest);
    }

    #[Test]
    public function retention_never_deletes_the_newest_verified_backup(): void
    {
        MaintenanceSetting::set('keep_backups_count', 1);

        $verified = $this->writeBackup('backup_20260101_000000_verified.sql', 300);
        $newerA = $this->writeBackup('backup_20260102_000000_a.sql', 200);
        $newerB = $this->writeBackup('backup_20260103_000000_b.sql', 100);

        BackupLog::create([
            'type' => 'both',
            'status' => 'completed',
            'filename' => 'backup_20260101_000000_verified.sql',
            'verification_status' => 'passed',
            'verified_at' => now()->subMinutes(300),
        ]);

        $this->runCleanup();

        // The verified backup is the oldest file, so mtime-only retention would
        // have deleted it first. With keep_backups_count = 1 it must be the one
        // file that survives.
        Storage::disk('backups')->assertExists($verified);
        Storage::disk('backups')->assertMissing($newerA);
        Storage::disk('backups')->assertMissing($newerB);
        $this->assertCount(1, Storage::disk('backups')->files());
    }

    #[Test]
    public function retention_keeps_previous_restore_verified_backup_when_newer_backup_is_not_restore_verified(): void
    {
        MaintenanceSetting::set('keep_backups_count', 1);

        $restoreVerified = $this->writeBackup('backup_20260101_000000_restore_verified.sql', 300);
        $newerPending = $this->writeBackup('backup_20260102_000000_pending.sql', 100);

        BackupLog::create([
            'type' => 'both',
            'status' => 'completed',
            'filename' => 'backup_20260101_000000_restore_verified.sql',
            'verification_status' => 'passed',
            'verified_at' => now()->subMinutes(300),
            'restore_verification_status' => 'passed',
            'restore_verified_at' => now()->subMinutes(300),
        ]);

        BackupLog::create([
            'type' => 'both',
            'status' => 'completed',
            'filename' => 'backup_20260102_000000_pending.sql',
            'verification_status' => 'passed',
            'verified_at' => now()->subMinutes(100),
            'restore_verification_status' => 'pending',
        ]);

        $this->runCleanup();

        Storage::disk('backups')->assertExists($restoreVerified);
        Storage::disk('backups')->assertMissing($newerPending);
    }

    #[Test]
    public function retention_still_reduces_the_backup_count_around_the_protected_file(): void
    {
        MaintenanceSetting::set('keep_backups_count', 2);

        $this->writeBackup('backup_20260101_000000_verified.sql', 400);
        $this->writeBackup('backup_20260102_000000_a.sql', 300);
        $this->writeBackup('backup_20260103_000000_b.sql', 200);
        $this->writeBackup('backup_20260104_000000_c.sql', 100);

        BackupLog::create([
            'type' => 'both',
            'status' => 'completed',
            'filename' => 'backup_20260101_000000_verified.sql',
            'verification_status' => 'passed',
            'verified_at' => now()->subMinutes(400),
        ]);

        $this->runCleanup();

        $remaining = collect(Storage::disk('backups')->files())->values();

        $this->assertCount(2, $remaining);
        $this->assertContains('backup_20260101_000000_verified.sql', $remaining->all());
    }

    #[Test]
    public function a_backup_failing_verification_is_discarded_and_recorded(): void
    {
        $log = BackupLog::create([
            'type' => 'both',
            'status' => 'pending',
        ]);

        // A dump missing its foreign-key guards must not be accepted.
        $service = new class extends BackupService
        {
            protected function performBackupWithLock(string $type, array $selectedTables, bool $saveToBackups, ?int $logId): string
            {
                $filename = 'backup_20260101_000000_corrupt.sql';
                Storage::disk('backups')->put($filename, '-- truncated dump with no guards');

                $log = BackupLog::query()->find($logId);

                try {
                    $this->verifyStoredBackup($filename);
                } catch (\Throwable $exception) {
                    Storage::disk('backups')->delete($filename);

                    $log?->update([
                        'verification_status' => 'failed',
                        'verification_error' => $exception->getMessage(),
                    ]);

                    throw $exception;
                }

                return $filename;
            }
        };

        try {
            $service->performBackup('both', [], true, $log->id);
            $this->fail('Verification should have rejected the corrupt backup.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('verification failed', strtolower($e->getMessage()));
        }

        Storage::disk('backups')->assertMissing('backup_20260101_000000_corrupt.sql');

        $this->assertSame('failed', $log->fresh()->verification_status);
        $this->assertNotNull($log->fresh()->verification_error);
    }

    #[Test]
    public function an_empty_stored_backup_fails_verification(): void
    {
        Storage::disk('backups')->put('backup_empty.sql', '');

        $method = new ReflectionMethod(BackupService::class, 'verifyStoredBackup');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);

        $method->invoke(app(BackupService::class), 'backup_empty.sql');
    }

    #[Test]
    public function a_missing_stored_backup_fails_verification(): void
    {
        $method = new ReflectionMethod(BackupService::class, 'verifyStoredBackup');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);

        $method->invoke(app(BackupService::class), 'does_not_exist.sql');
    }

    #[Test]
    public function a_second_concurrent_backup_is_refused_while_one_holds_the_lock(): void
    {
        $lock = Cache::lock('database-backup', 1800);
        $this->assertTrue($lock->get(), 'Precondition: the lock is available.');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Another database backup is already running.');

            app(BackupService::class)->performBackup('both');
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function the_lock_is_released_again_once_a_backup_fails(): void
    {
        try {
            app(BackupService::class)->performBackup('not-a-type');
        } catch (RuntimeException $e) {
            // Expected: an invalid type is rejected inside the lock.
        }

        // A failed run must not strand the lock and block every later backup.
        $lock = Cache::lock('database-backup', 1800);
        $this->assertTrue($lock->get(), 'The lock should have been released by the failed run.');
        $lock->release();
    }

    #[Test]
    public function a_table_that_cannot_be_exported_aborts_the_backup_and_leaves_no_partial_file(): void
    {
        $log = BackupLog::create(['type' => 'both', 'status' => 'pending']);

        // A table that passes the allowlist but fails mid-export.
        $service = new class extends BackupService
        {
            public function baseTableNames(): array
            {
                return ['definitely_not_a_real_table'];
            }
        };

        try {
            $service->performBackup('both', ['definitely_not_a_real_table'], true, $log->id);
            $this->fail('Exporting a broken table should abort the backup.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Failed exporting table', $e->getMessage());
        }

        // No half-written dump may be left behind pretending to be a backup.
        $this->assertSame([], Storage::disk('backups')->files());

        $fresh = $log->fresh();
        $this->assertSame('failed', $fresh->status);
        $this->assertNotNull($fresh->error);
    }

    #[Test]
    public function storage_write_failure_marks_the_log_failed_and_leaves_no_backup_file(): void
    {
        $log = BackupLog::create(['type' => 'both', 'status' => 'pending']);

        Storage::shouldReceive('disk')->with('backups')->andReturn(new class
        {
            public function put(string $path, mixed $contents): bool
            {
                return false;
            }

            public function files(): array
            {
                return [];
            }
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to write backup file to storage.');

        try {
            app(BackupService::class)->performBackup('both', ['migrations'], true, $log->id);
        } finally {
            $fresh = $log->fresh();
            $this->assertSame('failed', $fresh->status);
            $this->assertSame('Unable to write backup file to storage.', $fresh->error);
        }
    }

    #[Test]
    public function a_table_outside_the_allowlist_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid backup table selection.');

        app(BackupService::class)->performBackup('both', ['users; DROP TABLE users']);
    }

    #[Test]
    public function an_invalid_backup_type_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid backup type.');

        app(BackupService::class)->performBackup('not-a-type');
    }
}
