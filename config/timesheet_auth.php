<?php

return [
    // V2 is the canonical authorization store. Set the environment flags to
    // false only during a controlled rollback or data migration.
    'v2_read_enabled' => (bool) env('TIMESHEET_AUTH_V2_READ', true),
    'v2_write_enabled' => (bool) env('TIMESHEET_AUTH_V2_WRITE', true),
    'log_actor_fallback' => (bool) env('TIMESHEET_AUTH_LOG_ACTOR_FALLBACK', true),
];
