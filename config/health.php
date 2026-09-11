<?php

/*
 * When the status page starts worrying.
 */
return [
    // A heartbeat older than this means the process behind it has stopped.
    'stale_after_minutes' => (int) env('HEALTH_STALE_MINUTES', 5),

    // Backups run nightly; one older than this was missed.
    'backup_stale_after_hours' => (int) env('HEALTH_BACKUP_STALE_HOURS', 26),

    'disk_warning_percent' => (int) env('HEALTH_DISK_WARNING', 85),
    'disk_failing_percent' => (int) env('HEALTH_DISK_FAILING', 95),
];
