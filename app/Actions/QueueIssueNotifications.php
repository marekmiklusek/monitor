<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;

final readonly class QueueIssueNotifications
{
    public function __construct(private FlushIssueNotifications $flushIssueNotifications)
    {
        // ...
    }

    /**
     * @param  array<int, array{issue_id: string, kind: string}>  $events
     */
    public function execute(Project $project, array $events): void
    {
        $pending = collect([...$project->pending_issue_notifications ?? [], ...$events])
            ->keyBy('issue_id')
            ->values()
            ->all();

        $project->fill(['pending_issue_notifications' => $pending])->save();

        $this->flushIssueNotifications->execute($project);
    }
}
