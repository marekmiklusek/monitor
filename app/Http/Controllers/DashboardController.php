<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Models\Project;
use App\Queries\ProjectDashboardQuery;

final readonly class DashboardController
{
    public function __construct(private ProjectDashboardQuery $projectDashboardQuery)
    {
        // ...
    }

    public function __invoke(): Response
    {
        $projects = $this->projectDashboardQuery->builder()->get();

        return Inertia::render('dashboard', [
            'projects' => $projects->map(fn (Project $project): array => [
                'id' => $project->id,
                'name' => $project->name,
                'environment' => $project->environment,
                'open_issues_count' => $project->open_issues_count,
                'recent_occurrences_count' => $project->recent_occurrences_count,
                'heartbeat_status' => $project->heartbeatStatus()->value,
                'last_heartbeat_at' => $project->last_heartbeat_at?->toIso8601String(),
            ])->all(),
        ]);
    }
}
