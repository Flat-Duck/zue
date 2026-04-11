<?php

return [
    'v2_read_enabled' => (bool) env('TIMESHEET_AUTH_V2_READ', false),
    'v2_write_enabled' => (bool) env('TIMESHEET_AUTH_V2_WRITE', false),
    'log_actor_fallback' => (bool) env('TIMESHEET_AUTH_LOG_ACTOR_FALLBACK', true),
];
