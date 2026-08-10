<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Issue;
use Inertia\Response;
use App\Models\Project;
use App\Enums\IssueStatus;
use App\Models\Occurrence;
use App\Queries\IssueInboxQuery;
use App\Http\Requests\IssueIndexRequest;

final readonly class IssueController
{
    public function __construct(private IssueInboxQuery $issueInboxQuery)
    {
        // ...
    }

    public function index(IssueIndexRequest $request): Response
    {
        $status = $request->status();
        $projectId = $request->projectId();

        $issues = $this->issueInboxQuery
            ->builder($status, $projectId)
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('issues/index', [
            'issues' => [
                'data' => collect($issues->items())->map(fn (Issue $issue): array => $this->issueRow($issue))->all(),
                'current_page' => $issues->currentPage(),
                'last_page' => $issues->lastPage(),
                'total' => $issues->total(),
            ],
            'projects' => Project::query()
                ->orderBy('name')
                ->get()
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'name' => $project->name,
                ])->all(),
            'filters' => [
                'status' => $status instanceof IssueStatus ? $status->value : 'all',
                'project' => $projectId,
            ],
            'recent_threshold' => now()
                ->subMinutes(config()->integer('monitoring.recent_occurrence_minutes'))
                ->toIso8601String(),
        ]);
    }

    public function show(Issue $issue): Response
    {
        $issue->load('project');

        $occurrences = $issue->occurrences()
            ->latest('occurred_at')
            ->limit(50)
            ->get();

        return Inertia::render('issues/show', [
            'issue' => [
                'id' => $issue->id,
                'type' => $issue->type->value,
                'title' => $issue->title,
                'message' => $issue->message,
                'file' => $issue->file,
                'line' => $issue->line,
                'status' => $issue->status->value,
                'occurrences_count' => $issue->occurrences_count,
                'first_seen_at' => $issue->first_seen_at->toIso8601String(),
                'last_seen_at' => $issue->last_seen_at->toIso8601String(),
                'project' => [
                    'id' => $issue->project->id,
                    'name' => $issue->project->name,
                    'environment' => $issue->project->environment,
                ],
            ],
            'occurrences' => $occurrences->map(fn (Occurrence $occurrence): array => [
                'id' => $occurrence->id,
                'occurred_at' => $occurrence->occurred_at->toIso8601String(),
            ])->all(),
            'statuses' => array_column(IssueStatus::cases(), 'value'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function issueRow(Issue $issue): array
    {
        return [
            'id' => $issue->id,
            'type' => $issue->type->value,
            'title' => $issue->title,
            'message' => $issue->message,
            'status' => $issue->status->value,
            'occurrences_count' => $issue->occurrences_count,
            'last_seen_at' => $issue->last_seen_at->toIso8601String(),
            'project' => [
                'id' => $issue->project->id,
                'name' => $issue->project->name,
                'environment' => $issue->project->environment,
            ],
        ];
    }
}
