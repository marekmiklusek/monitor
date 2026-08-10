<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Issue;
use App\Models\Project;
use App\Enums\IssueStatus;
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
