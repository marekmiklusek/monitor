<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use App\Enums\IssueStatus;
use App\Models\Occurrence;
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

it('reports zero recent occurrences when they are older than a day', function (): void {
    $issue = Issue::factory()->create();

    Occurrence::factory()->count(3)->create([
        'issue_id' => $issue->id,
        'occurred_at' => now()->subDays(2),
    ]);

    $projects = resolve(ProjectDashboardQuery::class)->builder()->get();

    expect($projects->sole()->recent_occurrences_count)->toBe(0);
});

it('counts the occurrences of the last day across every issue status', function (): void {
    $project = Project::factory()->create();

    $open = Issue::factory()->create(['project_id' => $project->id, 'status' => IssueStatus::Open]);
    $resolved = Issue::factory()->resolved()->create(['project_id' => $project->id]);

    Occurrence::factory()->count(2)->create(['issue_id' => $open->id, 'occurred_at' => now()->subHour()]);
    Occurrence::factory()->create(['issue_id' => $resolved->id, 'occurred_at' => now()->subHours(23)]);
    Occurrence::factory()->create(['issue_id' => $open->id, 'occurred_at' => now()->subDays(2)]);

    $projects = resolve(ProjectDashboardQuery::class)->builder()->get();

    expect($projects->sole()->recent_occurrences_count)->toBe(3);
});

it('does not count recent occurrences of another project', function (): void {
    $project = Project::factory()->create(['name' => 'Alpha']);

    $issue = Issue::factory()->create(['project_id' => $project->id]);
    $foreign = Issue::factory()->create();

    Occurrence::factory()->create(['issue_id' => $issue->id, 'occurred_at' => now()->subHour()]);
    Occurrence::factory()->create(['issue_id' => $foreign->id, 'occurred_at' => now()->subHour()]);

    $projects = resolve(ProjectDashboardQuery::class)->builder()->get();

    expect($projects->sole('name', 'Alpha')->recent_occurrences_count)->toBe(1);
});
