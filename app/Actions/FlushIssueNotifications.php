<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Issue;
use App\Models\Project;
use App\Notifications\IssueDigest;
use App\Notifications\IssueOpened;
use App\Enums\IssueNotificationKind;
use App\Notifications\AdminNotifiable;

final readonly class FlushIssueNotifications
{
    public function execute(Project $project): void
    {
        $pending = $project->pending_issue_notifications ?? [];

        if ($pending === []) {
            return;
        }

        $throttledUntil = now()->subMinutes(
            config()->integer('monitoring.issue_notification_throttle_minutes'),
        );

        if ($project->issues_notified_at !== null && $project->issues_notified_at->gt($throttledUntil)) {
            return;
        }

        $issues = Issue::query()
            ->whereIn('id', array_column($pending, 'issue_id'))
            ->get()
            ->keyBy('id');

        /** @var array<int, array{issue: Issue, kind: IssueNotificationKind}> $entries */
        $entries = [];

        foreach ($pending as $event) {
            $issue = $issues->get($event['issue_id']);

            if ($issue === null) {
                continue;
            }

            $entries[] = [
                'issue' => $issue,
                'kind' => IssueNotificationKind::from($event['kind']),
            ];
        }

        if ($entries === []) {
            $project->fill(['pending_issue_notifications' => null])->save();

            return;
        }

        $notification = count($entries) === 1
            ? new IssueOpened($project, $entries[0]['issue'], $entries[0]['kind'])
            : new IssueDigest($project, $entries);

        (new AdminNotifiable)->notify($notification);

        $project->fill([
            'issues_notified_at' => now(),
            'pending_issue_notifications' => null,
        ])->save();
    }
}
