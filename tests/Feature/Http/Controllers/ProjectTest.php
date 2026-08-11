<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Project;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to the login page', function (): void {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
});

it('lists projects', function (): void {
    Project::factory()->create(['name' => 'Checkout API', 'environment' => 'production']);

    $this->actingAs(User::factory()->create())
        ->get(route('projects.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->component('projects/index')
            ->has('projects', 1)
            ->where('projects.0.name', 'Checkout API')
            ->where('projects.0.environment', 'production')
            ->where('revealedToken', null),
        );
});

it('creates a project and reveals the token once', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('projects.store'), [
            'name' => 'Checkout API',
            'environment' => 'production',
        ])
        ->assertRedirect(route('projects.index'));

    $project = Project::query()->sole();

    expect($project->name)->toBe('Checkout API')
        ->and($project->environment)->toBe('production')
        ->and($project->last_heartbeat_at)->toBeNull();

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('revealedToken', fn (mixed $token): bool => is_string($token)
                && Project::hashToken($token) === $project->token_hash),
        );

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page): Assert => $page->where('revealedToken', null));
});

it('validates the project payload', function (array $payload, string $field): void {
    $this->actingAs(User::factory()->create())
        ->from(route('projects.index'))
        ->post(route('projects.store'), $payload)
        ->assertSessionHasErrors($field);

    expect(Project::query()->count())->toBe(0);
})->with([
    'missing name' => [['environment' => 'production'], 'name'],
    'missing environment' => [['name' => 'Checkout API'], 'environment'],
    'blank name' => [['name' => '', 'environment' => 'production'], 'name'],
    'long name' => [[
        'name' => str_repeat('a', 256),
        'environment' => 'production',
    ], 'name'],
]);

it('rejects a duplicate project name', function (): void {
    Project::factory()->create(['name' => 'Checkout API']);

    $this->actingAs(User::factory()->create())
        ->from(route('projects.index'))
        ->post(route('projects.store'), [
            'name' => 'Checkout API',
            'environment' => 'staging',
        ])
        ->assertSessionHasErrors('name');

    expect(Project::query()->count())->toBe(1);
});

it('regenerates the token of a project', function (): void {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $originalHash = $project->token_hash;

    $this->actingAs($user)
        ->post(route('projects.token.store', $project))
        ->assertRedirect(route('projects.index'));

    $project->refresh();

    expect($project->token_hash)->not->toBe($originalHash);

    $this->actingAs($user)
        ->get(route('projects.index'))
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where('revealedToken', fn (mixed $token): bool => is_string($token)
                && Project::hashToken($token) === $project->token_hash),
        );
});

it('invalidates the previous token after regeneration', function (): void {
    $token = Project::generateToken();
    $project = Project::factory()->withToken($token['plain'])->create();

    $this->actingAs(User::factory()->create())
        ->post(route('projects.token.store', $project));

    $this->withToken($token['plain'])
        ->postJson(route('api.ingest'), [
            'schema_version' => 1,
            'sent_at' => now()->toIso8601String(),
            'environment' => 'production',
            'occurrences' => [[
                'type' => 'heartbeat',
                'occurred_at' => now()->toIso8601String(),
            ]],
        ])
        ->assertUnauthorized();
});
