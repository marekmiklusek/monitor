<?php

declare(strict_types=1);

use App\Models\Project;
use App\Actions\CreateProject;

it('creates a project and returns the plain token', function (): void {
    $result = resolve(CreateProject::class)->execute('Checkout API', 'production');

    $project = Project::query()->sole();

    expect($result['project']->id)->toBe($project->id)
        ->and($project->name)->toBe('Checkout API')
        ->and($project->environment)->toBe('production')
        ->and($result['token'])->toHaveLength(64)
        ->and(Project::hashToken($result['token']))->toBe($project->token_hash);
});

it('starts a project without heartbeat data', function (): void {
    $result = resolve(CreateProject::class)->execute('Checkout API', 'production');

    expect($result['project']->last_heartbeat_at)->toBeNull()
        ->and($result['project']->heartbeat_alerted_at)->toBeNull();
});

it('never stores the plain token', function (): void {
    $result = resolve(CreateProject::class)->execute('Checkout API', 'production');

    expect(Project::query()->sole()->token_hash)->not->toBe($result['token']);
});

it('gives every project its own token', function (): void {
    $first = resolve(CreateProject::class)->execute('Alpha', 'production');
    $second = resolve(CreateProject::class)->execute('Beta', 'production');

    expect($first['token'])->not->toBe($second['token'])
        ->and($first['project']->token_hash)->not->toBe($second['project']->token_hash);
});
