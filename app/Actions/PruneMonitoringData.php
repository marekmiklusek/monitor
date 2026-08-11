<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Issue;
use App\Enums\IssueStatus;
use App\Models\Occurrence;

final readonly class PruneMonitoringData
{
    /**
     * @return array{issues: int, occurrences: int}
     */
    public function execute(): array
    {
        $chunk = config()->integer('monitoring.prune_chunk_size');

        return [
            'issues' => $this->pruneIssues($chunk),
            'occurrences' => $this->pruneOccurrences($chunk),
        ];
    }

    private function pruneIssues(int $chunk): int
    {
        $cutoff = now()->subDays(config()->integer('monitoring.issue_retention_days'));

        $deleted = 0;

        do {
            $ids = Issue::query()
                ->whereIn('status', [IssueStatus::Resolved, IssueStatus::Ignored])
                ->where('last_seen_at', '<', $cutoff)
                ->limit($chunk)
                ->pluck('id')
                ->all();

            if ($ids === []) {
                break;
            }

            Occurrence::query()->whereIn('issue_id', $ids)->delete();

            Issue::query()->whereIn('id', $ids)->delete();

            $deleted += count($ids);
        } while (true);

        return $deleted;
    }

    private function pruneOccurrences(int $chunk): int
    {
        $cutoff = now()->subDays(config()->integer('monitoring.occurrence_retention_days'));

        $deleted = 0;

        do {
            $ids = Occurrence::query()
                ->where('occurred_at', '<', $cutoff)
                ->limit($chunk)
                ->pluck('id')
                ->all();

            if ($ids === []) {
                break;
            }

            Occurrence::query()->whereIn('id', $ids)->delete();

            $deleted += count($ids);
        } while (true);

        return $deleted;
    }
}
