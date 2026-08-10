<?php

declare(strict_types=1);

use App\Models\Project;
use App\Actions\RegenerateProjectToken;

it('replaces the token hash of a project', function (): void {
    $project = Project::factory()->create();

    $originalHash = $project->token_hash;

    $plain = resolve(RegenerateProjectToken::class)->execute($project);

    expect($plain)->toHaveLength(64)
        ->and($project->refresh()->token_hash)->not->toBe($originalHash)
        ->and(Project::hashToken($plain))->toBe($project->token_hash);
});

it('keeps the rest of the project intact', function (): void {
    $project = Project::factory()->create([
        'name' => 'Checkout API',
        'last_heartbeat_at' => now()->subMinute(),
    ]);

    resolve(RegenerateProjectToken::class)->execute($project);

    expect($project->refresh()->name)->toBe('Checkout API')
        ->and($project->last_heartbeat_at)->not->toBeNull();
});
