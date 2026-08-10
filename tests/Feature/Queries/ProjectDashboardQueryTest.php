<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use App\Enums\IssueStatus;
use App\Queries\ProjectDashboardQuery;

it('counts only the open issues of a project', function (): void {
    $project = Project::factory()->create();

    Issue::factory()->count(2)->create([
        'project_id' => $project->id,
        'status' => IssueStatus::Open,
    ]);

    Issue::factory()->create([
        'project_id' => $project->id,
        'status' => IssueStatus::Resolved,
    ]);

    Issue::factory()->create([
        'project_id' => $project->id,
        'status' => IssueStatus::Ignored,
    ]);

    $projects = resolve(ProjectDashboardQuery::class)->builder()->get();

    expect($projects)->toHaveCount(1)
        ->and($projects->sole()->open_issues_count)->toBe(2);
});

it('reports zero for a project without issues', function (): void {
    Project::factory()->create();

    $projects = resolve(ProjectDashboardQuery::class)->builder()->get();

    expect($projects->sole()->open_issues_count)->toBe(0);
});

it('does not count issues of another project', function (): void {
    $project = Project::factory()->create(['name' => 'Alpha']);

    Issue::factory()->create(['project_id' => $project->id, 'status' => IssueStatus::Open]);
    Issue::factory()->create(['status' => IssueStatus::Open]);

    $projects = resolve(ProjectDashboardQuery::class)->builder()->get();

    expect($projects->sole('name', 'Alpha')->open_issues_count)->toBe(1);
});

it('orders projects by name', function (): void {
    Project::factory()->create(['name' => 'Zulu']);
    Project::factory()->create(['name' => 'Alpha']);
    Project::factory()->create(['name' => 'Mike']);

    $projects = resolve(ProjectDashboardQuery::class)->builder()->get();

    expect($projects->pluck('name')->all())->toBe(['Alpha', 'Mike', 'Zulu']);
});
