<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Issue;
use App\Models\Project;
use App\Notifications\IssueDigest;
use App\Notifications\IssueOpened;
use App\Enums\IssueNotificationKind;
use App\Notifications\AdminNotifiable;
use Illuminate\Support\Facades\RateLimiter;

final readonly class FlushIssueNotifications
{
    public function execute(Project $project): void
    {
        $pending = $project->pending_issue_notifications ?? [];

        if ($pending === []) {
            return;
        }

        $entries = $this->entries($pending);

        if ($entries === []) {
            $project->fill(['pending_issue_notifications' => null])->save();

            return;
        }

        if (! $this->withinImmediateLimit($project)) {
            return;
        }

        $this->send($project, $entries);
    }

    /**
     * @param  array<int, array{issue_id: string, kind: string, queued_at?: string}>  $pending
     * @return array<int, array{issue: Issue, kind: IssueNotificationKind}>
     */
    private function entries(array $pending): array
    {
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

        return $entries;
    }

    private function withinImmediateLimit(Project $project): bool
    {
        return RateLimiter::attempt(
            "issue-notifications:{$project->id}",
            config()->integer('monitoring.max_immediate_notifications_per_minute'),
            fn (): bool => true,
            60,
        ) !== false;
    }

    /**
     * @param  array<int, array{issue: Issue, kind: IssueNotificationKind}>  $entries
     */
    private function send(Project $project, array $entries): void
    {
        $notification = count($entries) === 1
            ? new IssueOpened($project, $entries[0]['issue'], $entries[0]['kind'])
            : new IssueDigest($project, $entries);

        (new AdminNotifiable)->notify($notification);

        $project->fill(['pending_issue_notifications' => null])->save();
    }
}
