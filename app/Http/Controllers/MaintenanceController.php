<?php

namespace App\Http\Controllers;

use App\Jobs\PerformBackupJob;
use App\Models\BackupLog;
use App\Models\MaintenanceSetting;
use App\Services\BackupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaintenanceController extends Controller
{
    protected $backupService;

    public function __construct(BackupService $backupService)
    {
        $this->middleware('auth');
        $this->backupService = $backupService;
    }

    public function index()
    {
        $this->authorize('maintenance');

        $backups = Storage::disk('local')->files('backups');
        $backups = array_map(function ($file) {
            return [
                'name' => basename($file),
                'path' => $file,
                'size' => round(Storage::disk('local')->size($file) / 1024, 2).' KB',
                'created_at' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file))->toDateTimeString(),
            ];
        }, $backups);

        usort($backups, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        $tables = DB::select('SHOW TABLES');
        $dbName = config('database.connections.mysql.database');
        $tableKey = 'Tables_in_'.$dbName;
        $tables = array_map(function ($table) use ($tableKey) {
            return $table->$tableKey;
        }, $tables);

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

    public function export(Request $request)
    {
        $this->authorize('maintenance');

        $request->validate([
            'type' => ['nullable', 'in:structure,data,both'],
            'tables' => ['nullable', 'array'],
            'tables.*' => ['string', 'regex:/^[A-Za-z0-9_]+$/'],
        ]);

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

    public function updateSettings(Request $request)
    {
        $this->authorize('maintenance');

        $request->validate([
            'backup_interval' => 'required|in:daily,weekly,monthly',
            'backup_time' => 'required',
            'keep_backups_count' => 'required|integer|min:1',
        ]);

        MaintenanceSetting::set('auto_backup_enabled', $request->has('auto_backup_enabled') ? '1' : '0');
        MaintenanceSetting::set('backup_interval', $request->backup_interval);
        MaintenanceSetting::set('backup_time', $request->backup_time);
        MaintenanceSetting::set('keep_backups_count', $request->keep_backups_count);

        return redirect()->back()->with('success', 'Maintenance settings updated successfully.');
    }

    public function runQuickBackup()
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

    public function import(Request $request)
    {
        $request->validate([
            'sql_file' => ['required', 'file', 'max:512000', 'mimetypes:text/plain,application/sql,application/octet-stream'],
        ]);

        $this->authorize('maintenance');

        $path = $request->file('sql_file')->getRealPath();
        $sql = file_get_contents($path);

        try {
            DB::unprepared($sql);

            return redirect()->back()->with('success', 'Import completed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    public function restore($filename)
    {
        $this->authorize('maintenance');

        abort_unless($this->isSafeBackupFilename($filename), 404);

        if (! Storage::disk('local')->exists('backups/'.$filename)) {
            return redirect()->back()->with('error', 'Backup file not found.');
        }

        $sql = Storage::disk('local')->get('backups/'.$filename);

        try {
            DB::unprepared($sql);

            return redirect()->back()->with('success', 'Database restored successfully from '.$filename);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Restore failed: '.$e->getMessage());
        }
    }

    public function download($filename)
    {
        $this->authorize('maintenance');

        abort_unless($this->isSafeBackupFilename($filename), 404);

        if (! Storage::disk('local')->exists('backups/'.$filename)) {
            abort(404);
        }

        return response()->download(storage_path('app/backups/'.$filename));
    }

    public function delete($filename)
    {
        $this->authorize('maintenance');

        abort_unless($this->isSafeBackupFilename($filename), 404);

        if (Storage::disk('local')->exists('backups/'.$filename)) {
            Storage::disk('local')->delete('backups/'.$filename);

            return redirect()->back()->with('success', 'Backup deleted.');
        }

        return redirect()->back()->with('error', 'Backup file not found.');
    }

    private function isSafeBackupFilename(string $filename): bool
    {
        return Str::is(['backup_*.sql', 'export_*.sql'], $filename)
            && basename($filename) === $filename;
    }
}
