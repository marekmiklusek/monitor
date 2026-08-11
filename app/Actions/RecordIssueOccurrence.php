<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Issue;
use App\Models\Project;
use App\Enums\IssueStatus;
use App\Models\Occurrence;
use Carbon\CarbonInterface;
use App\Enums\OccurrenceType;
use App\Enums\IssueNotificationKind;

final readonly class RecordIssueOccurrence
{
    public function __construct(private CalculateOccurrenceFingerprint $calculateOccurrenceFingerprint)
    {
        // ...
    }

    /**
     * @param  array<string, mixed>  $occurrence
     * @return array{issue: Issue, notification: IssueNotificationKind|null}
     */
    public function execute(Project $project, OccurrenceType $type, array $occurrence, CarbonInterface $occurredAt): array
    {
        $fingerprint = $this->calculateOccurrenceFingerprint->execute($type, $occurrence);

        $issue = Issue::query()->firstOrNew([
            'project_id' => $project->id,
            'fingerprint' => $fingerprint,
        ]);

        $notification = $this->notification($issue);

        $line = $occurrence['line'] ?? null;

        $issue->fill([
            'type' => $type,
            'title' => $this->title($occurrence, $type),
            'message' => $this->string($occurrence, 'message'),
            'file' => $this->string($occurrence, 'file'),
            'line' => is_int($line) ? $line : null,
            'occurrences_count' => $issue->exists ? $issue->occurrences_count + 1 : 1,
            'status' => $this->status($issue),
            'first_seen_at' => $issue->exists ? $issue->first_seen_at : $occurredAt,
            'last_seen_at' => $occurredAt,
        ]);

        $issue->save();

        $issue->occurrences()->create([
            'payload' => $occurrence,
            'occurred_at' => $occurredAt,
        ]);

        $this->pruneOccurrences($issue);

        return [
            'issue' => $issue,
            'notification' => $notification,
        ];
    }

    private function notification(Issue $issue): ?IssueNotificationKind
    {
        if (! $issue->exists) {
            return IssueNotificationKind::NewIssue;
        }

        return $issue->status === IssueStatus::Resolved
            ? IssueNotificationKind::Regression
            : null;
    }

    private function status(Issue $issue): IssueStatus
    {
        if (! $issue->exists) {
            return IssueStatus::Open;
        }

        return $issue->status === IssueStatus::Resolved
            ? IssueStatus::Open
            : $issue->status;
    }

    /**
     * @param  array<string, mixed>  $occurrence
     */
    private function title(array $occurrence, OccurrenceType $type): string
    {
        $exceptionClass = $this->string($occurrence, 'exception_class');

        if ($exceptionClass !== null) {
            return mb_substr($exceptionClass, 0, 255);
        }

        $channel = $this->string($occurrence, 'channel');

        if ($type === OccurrenceType::Log && $channel !== null) {
            return mb_substr($channel, 0, 255);
        }

        $message = $this->string($occurrence, 'message');

        return $message !== null ? mb_substr($message, 0, 255) : $type->value;
    }

    /**
     * @param  array<string, mixed>  $occurrence
     */
    private function string(array $occurrence, string $key): ?string
    {
        $value = $occurrence[$key] ?? null;

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    private function pruneOccurrences(Issue $issue): void
    {
        $keptIds = $issue->occurrences()
            ->reorder('occurred_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->pluck('id')
            ->all();

        Occurrence::query()
            ->where('issue_id', $issue->id)
            ->whereNotIn('id', $keptIds)
            ->delete();
    }
}
