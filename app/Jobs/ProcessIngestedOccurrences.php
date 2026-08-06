<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Actions\ProcessIngestedOccurrences as ProcessIngestedOccurrencesAction;

final class ProcessIngestedOccurrences implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array<string, mixed>>  $occurrences
     */
    public function __construct(public readonly string $projectId, public readonly array $occurrences)
    {
        // ...
    }

    public function handle(ProcessIngestedOccurrencesAction $processIngestedOccurrences): void
    {
        $project = Project::query()->find($this->projectId);

        if ($project === null) {
            return;
        }

        $processIngestedOccurrences->execute($project, $this->occurrences);
    }
}
