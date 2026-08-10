<?php

declare(strict_types=1);

namespace App\Enums;

enum HeartbeatStatus: string
{
    case Ok = 'ok';
    case Stale = 'stale';
    case Missing = 'missing';
}
