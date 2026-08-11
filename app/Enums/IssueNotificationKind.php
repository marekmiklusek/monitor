<?php

declare(strict_types=1);

namespace App\Enums;

enum IssueNotificationKind: string
{
    case NewIssue = 'new';
    case Regression = 'regression';
}
