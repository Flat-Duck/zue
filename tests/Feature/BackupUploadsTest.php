<?php

namespace Tests\Feature;

use App\Models\MaintenanceSetting;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A backup is the database *and* the files people uploaded.
 *
 * Signatures live on disk, not in a table. A restore that brings the rows back
 * without the images means every signature has to be collected again, so the
 * dump and an archive of the uploads are written as a pair and kept as a pair.
 */
class BackupUploadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('backups');
        Storage::fake('public');
    }

    private function admin(): User
    {
        $this->seed(PermissionsSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super-admin');

        return $user;
    }

    #[Test]
    public function a_backup_archives_the_uploaded_files_beside_the_dump(): void
    {
        Storage::disk('public')->put('signatures/one.png', 'png-bytes');
        Storage::disk('public')->put('signatures/two.png', 'more-png-bytes');

        $dump = app(BackupService::class)->performBackup('structure', ['users']);
        $archive = BackupService::uploadsArchiveFor($dump);

        Storage::disk('backups')->assertExists($dump);
        Storage::disk('backups')->assertExists($archive);

        // The archive really contains the files, not just a name that suggests it.
        $tar = new \PharData(Storage::disk('backups')->path($archive));
        $names = [];
        foreach (new \RecursiveIteratorIterator($tar) as $file) {
            $names[] = str_replace('\\', '/', substr((string) $file->getPathname(), strpos((string) $file->getPathname(), '.tar.gz') + 8));
        }

        $this->assertContains('signatures/one.png', $names);
        $this->assertContains('signatures/two.png', $names);
    }

    #[Test]
    public function with_nothing_uploaded_there_is_no_archive_and_the_backup_still_succeeds(): void
    {
        $dump = app(BackupService::class)->performBackup('structure', ['users']);

        Storage::disk('backups')->assertExists($dump);
        Storage::disk('backups')->assertMissing(BackupService::uploadsArchiveFor($dump));
    }

    #[Test]
    public function retention_removes_the_archive_with_its_dump(): void
    {
        MaintenanceSetting::set('keep_backups_count', 1);

        foreach ([['old', 300], ['new', 100]] as [$stem, $age]) {
            $dump = "backup_20260101_000000_{$stem}.sql";
            Storage::disk('backups')->put($dump, "SET FOREIGN_KEY_CHECKS=0;\nSET FOREIGN_KEY_CHECKS=1;\n");
            Storage::disk('backups')->put(BackupService::uploadsArchiveFor($dump), 'tar');
            touch(Storage::disk('backups')->path($dump), now()->subMinutes($age)->timestamp);
        }

        $method = new ReflectionMethod(BackupService::class, 'cleanupOldBackups');
        $method->setAccessible(true);
        $method->invoke(app(BackupService::class));

        Storage::disk('backups')->assertMissing('backup_20260101_000000_old.sql');
        Storage::disk('backups')->assertMissing('backup_20260101_000000_old.files.tar.gz');
        Storage::disk('backups')->assertExists('backup_20260101_000000_new.sql');
        Storage::disk('backups')->assertExists('backup_20260101_000000_new.files.tar.gz');
    }

    #[Test]
    public function deleting_a_dump_from_the_screen_deletes_its_archive(): void
    {
        Storage::disk('backups')->put('backup_20260101_000000_x.sql', 'SET FOREIGN_KEY_CHECKS=0;');
        Storage::disk('backups')->put('backup_20260101_000000_x.files.tar.gz', 'tar');

        $this->actingAs($this->admin())
            ->delete(route('maintenance.delete', 'backup_20260101_000000_x.sql'))
            ->assertRedirect();

        Storage::disk('backups')->assertMissing('backup_20260101_000000_x.sql');
        Storage::disk('backups')->assertMissing('backup_20260101_000000_x.files.tar.gz');
    }

    #[Test]
    public function the_archive_can_be_downloaded_but_never_restored_as_sql(): void
    {
        Storage::disk('backups')->put('backup_20260101_000000_x.files.tar.gz', 'tar-bytes');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('maintenance.download', 'backup_20260101_000000_x.files.tar.gz'))
            ->assertOk()
            ->assertDownload('backup_20260101_000000_x.files.tar.gz');

        $this->actingAs($admin)
            ->post(route('maintenance.restore', 'backup_20260101_000000_x.files.tar.gz'))
            ->assertNotFound();
    }

    /**
     * A downloaded backup once arrived as a 0-byte file. The response has to
     * carry the file's actual bytes, not just its name.
     */
    #[Test]
    public function a_downloaded_dump_carries_its_bytes(): void
    {
        $content = "SET FOREIGN_KEY_CHECKS=0;\nINSERT INTO `users` (`id`) VALUES (1);\nSET FOREIGN_KEY_CHECKS=1;\n";
        Storage::disk('backups')->put('backup_20260101_000000_y.sql', $content);

        $response = $this->actingAs($this->admin())
            ->get(route('maintenance.download', 'backup_20260101_000000_y.sql'));

        $response->assertOk()->assertDownload('backup_20260101_000000_y.sql');

        $file = $response->baseResponse->getFile();
        $this->assertSame(strlen($content), $file->getSize(), 'The download must be the whole file.');
        $this->assertSame($content, file_get_contents($file->getPathname()));
    }
}
