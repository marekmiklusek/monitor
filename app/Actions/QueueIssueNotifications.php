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
        $existing = collect($project->pending_issue_notifications ?? [])->keyBy('issue_id');

        $queuedAt = now()->toIso8601String();

        $pending = collect($events)
            ->keyBy('issue_id')
            ->map(fn (array $event, string $issueId): array => [
                ...$event,
                'queued_at' => $existing->get($issueId)['queued_at'] ?? $queuedAt,
            ]);

        $project->fill([
            'pending_issue_notifications' => $existing->merge($pending)->values()->all(),
        ])->save();

        $this->flushIssueNotifications->execute($project);
    }
}
