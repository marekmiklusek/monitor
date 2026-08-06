<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Project;
use App\Enums\OccurrenceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Date;

final readonly class ProcessIngestedOccurrences
{
    public function __construct(private RecordIssueOccurrence $recordIssueOccurrence)
    {
        // ...
    }

    /**
     * @param  array<int, array<string, mixed>>  $occurrences
     */
    public function execute(Project $project, array $occurrences): void
    {
        DB::transaction(function () use ($project, $occurrences): void {
            foreach ($occurrences as $occurrence) {
                $rawType = $occurrence['type'] ?? null;
                $rawOccurredAt = $occurrence['occurred_at'] ?? null;

                $type = OccurrenceType::from(is_string($rawType) ? $rawType : '');

                $occurredAt = Date::parse(is_string($rawOccurredAt) ? $rawOccurredAt : null);

                if ($type === OccurrenceType::Heartbeat) {
                    $project->forceFill(['last_heartbeat_at' => $occurredAt])->save();

                    continue;
                }

                $this->recordIssueOccurrence->execute($project, $type, $occurrence, $occurredAt);
            }
        });
    }
}
