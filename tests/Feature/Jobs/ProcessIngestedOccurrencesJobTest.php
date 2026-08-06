<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use App\Enums\OccurrenceType;
use App\Jobs\ProcessIngestedOccurrences;

it('processes the ingested occurrences of the project', function (): void {
    $project = Project::factory()->create();

    $job = new ProcessIngestedOccurrences($project->id, [
        [
            'type' => OccurrenceType::Exception->value,
            'occurred_at' => now()->toIso8601String(),
            'exception_class' => 'RuntimeException',
            'message' => 'Order 1234 failed',
            'file' => '/app/Http/Controllers/OrderController.php',
            'line' => 42,
        ],
    ]);

    $job->handle(resolve(App\Actions\ProcessIngestedOccurrences::class));

    $issue = Issue::query()->sole();

    expect($issue->project_id)->toBe($project->id)
        ->and($issue->occurrences_count)->toBe(1);
});

it('does nothing when the project no longer exists', function (): void {
    $project = Project::factory()->create();

    $projectId = $project->id;
    $project->delete();

    $job = new ProcessIngestedOccurrences($projectId, [
        [
            'type' => OccurrenceType::Exception->value,
            'occurred_at' => now()->toIso8601String(),
            'exception_class' => 'RuntimeException',
        ],
    ]);

    $job->handle(resolve(App\Actions\ProcessIngestedOccurrences::class));

    expect(Issue::query()->count())->toBe(0);
});
