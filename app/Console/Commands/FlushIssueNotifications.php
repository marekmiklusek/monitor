<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;
use App\Actions\FlushIssueNotifications as FlushIssueNotificationsAction;

#[Signature('monitor:flush-issue-notifications')]
#[Description('Send the issue notifications that were held back by the throttle window')]
final class FlushIssueNotifications extends Command
{
    public function handle(FlushIssueNotificationsAction $flushIssueNotifications): int
    {
        Project::query()
            ->whereNotNull('pending_issue_notifications')
            ->each(function (Project $project) use ($flushIssueNotifications): void {
                $flushIssueNotifications->execute($project);
            });

        return self::SUCCESS;
    }
}
