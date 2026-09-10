<?php

namespace App\Providers;

use App\Contracts\AuditLoggerContract;
use App\Contracts\BackupServiceContract;
use App\Contracts\FlightDispatchContract;
use App\Models\TimeSheet;
use App\Observers\TimeSheetObserver;
use App\Services\AuditLogger;
use App\Services\BackupService;
use App\Services\Flights\FlightDispatchService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Contracts bound to their implementations.
     *
     * These are the application's real seams: a cross-cutting audit trail, the
     * boundary with the filesystem and database server, and the one place
     * flight manifests change. Binding them means the rest of the code depends
     * on the contract and a test can substitute its own.
     *
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        AuditLoggerContract::class => AuditLogger::class,
        BackupServiceContract::class => BackupService::class,
        FlightDispatchContract::class => FlightDispatchService::class,
    ];

    public function register(): void
    {
        Paginator::useBootstrap();

        // Auditing carries per-request context, so one instance per request.
        $this->app->scoped(AuditLoggerContract::class, AuditLogger::class);
    }

    public function boot(): void
    {
        TimeSheet::observe(TimeSheetObserver::class);

        //
    }
}
