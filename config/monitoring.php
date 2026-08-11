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
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Channels used for every monitoring notification, as a comma separated
    | list. Supported values are "mail" and "telegram".
    |
    */

    'channels' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env('MONITORING_CHANNELS', 'mail')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Telegram Chat
    |--------------------------------------------------------------------------
    |
    | The chat receiving Telegram notifications. The bot token itself lives in
    | services.telegram-bot-api.token, where the channel package reads it.
    |
    */

    'telegram_chat_id' => env('MONITORING_TELEGRAM_CHAT_ID'),

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

    /*
    |--------------------------------------------------------------------------
    | Issue Notification Throttle
    |--------------------------------------------------------------------------
    |
    | Minimum minutes between issue notification mails per project. Issues
    | opened within the window are collected into a digest mail.
    |
    */

    'issue_notification_throttle_minutes' => env('MONITORING_ISSUE_NOTIFICATION_THROTTLE_MINUTES', 15),

];
