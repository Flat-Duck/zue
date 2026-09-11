<?php

namespace App\Http\Controllers;

use App\Contracts\AuditLoggerContract;
use App\Contracts\BackupServiceContract;
use App\Http\Requests\DatabaseExportRequest;
use App\Http\Requests\DatabaseImportRequest;
use App\Http\Requests\MaintenanceSettingsRequest;
use App\Jobs\PerformBackupJob;
use App\Models\BackupLog;
use App\Models\MaintenanceSetting;
use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaintenanceController extends Controller
{
    public function __construct(
        private readonly BackupServiceContract $backupService,
        private readonly AuditLoggerContract $auditLogger,
    ) {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->authorize('maintenance');

        $backups = Storage::disk('backups')->files();
        $backups = array_map(function ($file) {
            return [
                'name' => basename($file),
                'path' => $file,
                'size' => round(Storage::disk('backups')->size($file) / 1024, 2).' KB',
                'created_at' => Carbon::createFromTimestamp(Storage::disk('backups')->lastModified($file))->toDateTimeString(),
            ];
        }, $backups);

        usort($backups, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        $tables = $this->backupService->baseTableNames();

        $settings = [
            'auto_backup_enabled' => MaintenanceSetting::get('auto_backup_enabled', '0'),
            'backup_interval' => MaintenanceSetting::get('backup_interval', 'daily'),
            'backup_time' => MaintenanceSetting::get('backup_time', '00:00'),
            'keep_backups_count' => MaintenanceSetting::get('keep_backups_count', '10'),
        ];

        $totalSize = 0;
        foreach ($backups as $b) {
            $totalSize += (float) str_replace(' KB', '', $b['size']);
        }

        $logs = BackupLog::orderBy('created_at', 'desc')->limit(10)->get();

        return view('app.maintenance.index', [
            'page' => 'maintenance',
            'backups' => $backups,
            'tables' => $tables,
            'settings' => $settings,
            'logs' => $logs,
            'stats' => [
                'total_count' => count($backups),
                'total_size' => round($totalSize / 1024, 2).' MB',
                'storage_path' => storage_path('app/backups'),
            ],
        ]);
    }

    public function export(DatabaseExportRequest $request)
    {
        $type = $request->input('type', 'both');
        $selectedTables = $request->input('tables', []);

        if ($request->has('save_to_backups')) {
            $log = BackupLog::create([
                'type' => 'manual',
                'status' => 'pending',
                'started_at' => now(),
            ]);

            PerformBackupJob::dispatch($type, $selectedTables, $log->id);

            return redirect()->back()->with('success', "Backup job dispatched to queue (Task #{$log->id}).");
        }

        $sql = $this->backupService->performBackup($type, $selectedTables, false);
        $filename = 'export_'.now()->format('Ymd_His').'.sql';

        return response($sql, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function updateSettings(MaintenanceSettingsRequest $request): RedirectResponse
    {
        MaintenanceSetting::set('auto_backup_enabled', $request->has('auto_backup_enabled') ? '1' : '0');
        MaintenanceSetting::set('backup_interval', $request->backup_interval);
        MaintenanceSetting::set('backup_time', $request->backup_time);
        MaintenanceSetting::set('keep_backups_count', $request->keep_backups_count);

        return redirect()->back()->with('success', 'Maintenance settings updated successfully.');
    }

    public function runQuickBackup(): RedirectResponse
    {
        $this->authorize('maintenance');

        $log = BackupLog::create([
            'type' => 'quick',
            'status' => 'pending',
            'started_at' => now(),
        ]);

        PerformBackupJob::dispatch('both', [], $log->id);

        return redirect()->back()->with('success', "Quick backup job dispatched to queue (Task #{$log->id}).");
    }

    // runInBackground method removed in favor of Laravel Queues

    public function import(DatabaseImportRequest $request): RedirectResponse
    {
        $path = $request->file('sql_file')->getRealPath();
        $sql = file_get_contents($path);

        try {
            DB::unprepared($sql);

            return redirect()->back()->with('success', 'Import completed successfully.');
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'Import failed. Check the application logs for details.');
        }
    }

    public function restore($filename): RedirectResponse
    {
        $this->authorize('maintenance');

        // Only a dump is SQL; the archive of uploaded files beside it is not.
        abort_unless($this->isSafeBackupFilename($filename) && str_ends_with($filename, '.sql'), 404);

        if (! Storage::disk('backups')->exists($filename)) {
            return redirect()->back()->with('error', 'Backup file not found.');
        }

        $sql = Storage::disk('backups')->get($filename);
        $backupLog = BackupLog::query()->where('filename', $filename)->latest('created_at')->first();

        $backupLog?->update([
            'restore_verification_status' => 'running',
            'restore_verification_error' => null,
        ]);

        try {
            DB::unprepared($sql);

            $backupLog?->update([
                'restore_verification_status' => 'passed',
                'restore_verified_at' => now(),
                'restore_verification_error' => null,
            ]);

            $this->auditLogger->record('database.restored', [
                'filename' => $filename,
            ]);

            return redirect()->back()->with('success', 'Database restored successfully from '.$filename);
        } catch (\Exception $e) {
            report($e);

            $backupLog?->update([
                'restore_verification_status' => 'failed',
                'restore_verification_error' => 'Restore execution failed.',
            ]);

            $this->auditLogger->recordFailure('database.restore_failed', $e->getMessage(), [
                'filename' => $filename,
            ]);

            return redirect()->back()->with('error', 'Restore failed. Check the application logs for details.');
        }
    }

    public function download($filename)
    {
        $this->authorize('maintenance');

        abort_unless($this->isSafeBackupFilename($filename), 404);

        if (! Storage::disk('backups')->exists($filename)) {
            abort(404);
        }

        $this->auditLogger->record('backup.downloaded', [
            'filename' => $filename,
        ]);

        return response()->download(Storage::disk('backups')->path($filename));
    }

    public function delete($filename): RedirectResponse
    {
        $this->authorize('maintenance');

        abort_unless($this->isSafeBackupFilename($filename), 404);

        if (Storage::disk('backups')->exists($filename)) {
            Storage::disk('backups')->delete($filename);

            if (str_ends_with($filename, '.sql')) {
                Storage::disk('backups')->delete(BackupService::uploadsArchiveFor($filename));
            }

            $this->auditLogger->record('backup.deleted', [
                'filename' => $filename,
            ]);

            return redirect()->back()->with('success', 'Backup deleted.');
        }

        return redirect()->back()->with('error', 'Backup file not found.');
    }

    private function isSafeBackupFilename(string $filename): bool
    {
        return Str::is(['backup_*.sql', 'export_*.sql', 'backup_*.files.tar.gz'], $filename)
            && basename($filename) === $filename;
    }
}
