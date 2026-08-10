<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Email
    |--------------------------------------------------------------------------
    |
    | The address receiving heartbeat outage and recovery notifications.
    |
    */

    'admin_email' => env('MONITORING_ADMIN_EMAIL', 'admin@example.com'),

    /*
    |--------------------------------------------------------------------------
    | Heartbeat Threshold
    |--------------------------------------------------------------------------
    |
    | Minutes without a heartbeat before a project is considered stale.
    |
    */

    'heartbeat_threshold_minutes' => env('MONITORING_HEARTBEAT_THRESHOLD_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Recent Occurrence Threshold
    |--------------------------------------------------------------------------
    |
    | Minutes within which an issue is highlighted as recently seen.
    |
    */

    'recent_occurrence_minutes' => env('MONITORING_RECENT_OCCURRENCE_MINUTES', 30),

];
