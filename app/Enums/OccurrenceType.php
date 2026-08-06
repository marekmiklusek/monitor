<?php

declare(strict_types=1);

namespace App\Enums;

enum OccurrenceType: string
{
    case Exception = 'exception';
    case FailedJob = 'failed_job';
    case SlowQuery = 'slow_query';
    case Heartbeat = 'heartbeat';
    case Log = 'log';
}
