<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Issue;
use App\Models\Project;
use App\Enums\IssueStatus;
use App\Models\Occurrence;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('lists projects with their open issue counts', function (): void {
    $project = Project::factory()->create(['name' => 'Checkout API']);

    Issue::factory()->count(3)->create([
        'project_id' => $project->id,
        'status' => IssueStatus::Open,
    ]);

    Issue::factory()->create([
        'project_id' => $project->id,
        'status' => IssueStatus::Resolved,
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('dashboard')
            ->has('projects', 1)
            ->where('projects.0.name', 'Checkout API')
            ->where('projects.0.open_issues_count', 3),
        );
});

it('reports zero recent occurrences for a project with only old activity', function (): void {
    $issue = Issue::factory()->create();

    Occurrence::factory()->count(2)->create([
        'issue_id' => $issue->id,
        'occurred_at' => now()->subDays(2),
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('projects.0.recent_occurrences_count', 0),
        );
});

it('reports the occurrences of the last day', function (): void {
    $issue = Issue::factory()->create();

    Occurrence::factory()->count(3)->create([
        'issue_id' => $issue->id,
        'occurred_at' => now()->subHours(3),
    ]);

    Occurrence::factory()->create([
        'issue_id' => $issue->id,
        'occurred_at' => now()->subDays(2),
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('projects.0.recent_occurrences_count', 3),
        );
});

it('does not run a query per project', function (): void {
    $user = User::factory()->create();

    $countQueries = function () use ($user): int {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $queries;
    };

    Project::factory()->count(2)->create();

    $withTwo = $countQueries();

    Project::factory()->count(8)->create();

    expect($countQueries())->toBe($withTwo);
});

it('marks a project without recent heartbeats as stale', function (): void {
    Project::factory()->create(['last_heartbeat_at' => now()->subMinutes(20)]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('projects.0.heartbeat_status', 'stale'));
});

it('marks a project with a fresh heartbeat as ok', function (): void {
    Project::factory()->create(['last_heartbeat_at' => now()->subMinute()]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('projects.0.heartbeat_status', 'ok'));
});

it('marks a project that never sent a heartbeat as missing', function (): void {
    Project::factory()->create(['last_heartbeat_at' => null]);

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('projects.0.heartbeat_status', 'missing')
            ->where('projects.0.last_heartbeat_at', null),
        );
});
