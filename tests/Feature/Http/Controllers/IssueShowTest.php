<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Issue;
use App\Models\Project;
use App\Models\Occurrence;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to the login page', function (): void {
    $issue = Issue::factory()->create();

    $this->get(route('issues.show', $issue))->assertRedirect(route('login'));
});

it('shows the issue with its project and occurrences', function (): void {
    $project = Project::factory()->create(['name' => 'Checkout API']);

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'title' => 'RuntimeException',
        'file' => '/app/Orders.php',
        'line' => 42,
    ]);

    Occurrence::factory()->count(2)->create(['issue_id' => $issue->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('issues.show', $issue))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('issues/show')
            ->where('issue.title', 'RuntimeException')
            ->where('issue.file', '/app/Orders.php')
            ->where('issue.line', 42)
            ->where('issue.project.name', 'Checkout API')
            ->has('occurrences', 2)
            ->has('statuses', 3),
        );
});

it('orders occurrences newest first and caps them at fifty', function (): void {
    $issue = Issue::factory()->create();

    Occurrence::factory()->create([
        'issue_id' => $issue->id,
        'occurred_at' => now()->subDay(),
    ]);

    $newest = Occurrence::factory()->create([
        'issue_id' => $issue->id,
        'occurred_at' => now(),
    ]);

    Occurrence::factory()->count(55)->create([
        'issue_id' => $issue->id,
        'occurred_at' => now()->subWeek(),
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('issues.show', $issue))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->has('occurrences', 50)
            ->where('occurrences.0.id', $newest->id),
        );
});

it('returns the payload of an occurrence as json', function (): void {
    $issue = Issue::factory()->create();

    $occurrence = Occurrence::factory()->create([
        'issue_id' => $issue->id,
        'payload' => ['type' => 'exception', 'message' => 'Boom'],
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('occurrences.show', ['issue' => $issue, 'occurrence' => $occurrence]))
        ->assertOk()
        ->assertJsonPath('id', $occurrence->id)
        ->assertJsonPath('payload.message', 'Boom');
});

it('does not expose an occurrence belonging to another issue', function (): void {
    $issue = Issue::factory()->create();
    $occurrence = Occurrence::factory()->create();

    $this->actingAs(User::factory()->create())
        ->getJson(route('occurrences.show', ['issue' => $issue, 'occurrence' => $occurrence]))
        ->assertNotFound();
});
