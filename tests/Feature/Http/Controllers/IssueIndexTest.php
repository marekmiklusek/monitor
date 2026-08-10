<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Issue;
use App\Models\Project;
use App\Enums\IssueStatus;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to the login page', function (): void {
    $this->get(route('issues.index'))->assertRedirect(route('login'));
});

it('shows only open issues by default', function (): void {
    Issue::factory()->create(['status' => IssueStatus::Open, 'title' => 'Open one']);
    Issue::factory()->create(['status' => IssueStatus::Resolved, 'title' => 'Resolved one']);
    Issue::factory()->create(['status' => IssueStatus::Ignored, 'title' => 'Ignored one']);

    $this->actingAs(User::factory()->create())
        ->get(route('issues.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('issues/index')
            ->has('issues.data', 1)
            ->where('issues.data.0.title', 'Open one')
            ->where('filters.status', 'open'),
        );
});

it('filters issues by status', function (IssueStatus $status): void {
    foreach (IssueStatus::cases() as $case) {
        Issue::factory()->create(['status' => $case]);
    }

    $this->actingAs(User::factory()->create())
        ->get(route('issues.index', ['status' => $status->value]))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('issues.data', 1)
            ->where('issues.data.0.status', $status->value),
        );
})->with(IssueStatus::cases());

it('shows every issue when the status filter is all', function (): void {
    foreach (IssueStatus::cases() as $case) {
        Issue::factory()->create(['status' => $case]);
    }

    $this->actingAs(User::factory()->create())
        ->get(route('issues.index', ['status' => 'all']))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('issues.data', 3)
            ->where('filters.status', 'all'),
        );
});

it('filters issues by project', function (): void {
    $project = Project::factory()->create();

    Issue::factory()->create(['project_id' => $project->id, 'status' => IssueStatus::Open]);
    Issue::factory()->create(['status' => IssueStatus::Open]);

    $this->actingAs(User::factory()->create())
        ->get(route('issues.index', ['project' => $project->id]))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('issues.data', 1)
            ->where('issues.data.0.project.id', $project->id)
            ->where('filters.project', $project->id),
        );
});

it('rejects an unknown status filter', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('issues.index', ['status' => 'exploded']))
        ->assertSessionHasErrors('status');
});

it('rejects an unknown project filter', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('issues.index', ['project' => '019fec28-3035-7014-af37-eef73cb46d1d']))
        ->assertSessionHasErrors('project');
});

it('orders issues by last seen descending', function (): void {
    Issue::factory()->create(['title' => 'Older', 'last_seen_at' => now()->subHour()]);
    Issue::factory()->create(['title' => 'Newer', 'last_seen_at' => now()]);

    $this->actingAs(User::factory()->create())
        ->get(route('issues.index'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('issues.data.0.title', 'Newer')
            ->where('issues.data.1.title', 'Older'),
        );
});

it('paginates issues', function (): void {
    Issue::factory()->count(30)->create(['status' => IssueStatus::Open]);

    $this->actingAs(User::factory()->create())
        ->get(route('issues.index'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('issues.data', 25)
            ->where('issues.current_page', 1)
            ->where('issues.last_page', 2)
            ->where('issues.total', 30),
        );

    $this->actingAs(User::factory()->create())
        ->get(route('issues.index', ['page' => 2]))
        ->assertInertia(fn (Assert $page): Assert => $page->has('issues.data', 5));
});

it('exposes the recent occurrence threshold', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('issues.index'))
        ->assertInertia(fn (Assert $page): Assert => $page->where(
            'recent_threshold',
            now()->subMinutes(config()->integer('monitoring.recent_occurrence_minutes'))->toIso8601String(),
        ));
});

it('does not run a query per issue when loading projects', function (): void {
    $user = User::factory()->create();

    $countQueries = function () use ($user): int {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)->get(route('issues.index'))->assertOk();

        $queries = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $queries;
    };

    Issue::factory()->count(3)->create(['status' => IssueStatus::Open]);

    $withThree = $countQueries();

    Issue::factory()->count(12)->create(['status' => IssueStatus::Open]);

    expect($countQueries())->toBe($withThree);
});
