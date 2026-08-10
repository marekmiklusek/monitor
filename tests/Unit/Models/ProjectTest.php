<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use App\Enums\HeartbeatStatus;

it('generates a token with a matching hash', function (): void {
    $token = Project::generateToken();

    expect($token['plain'])->toHaveLength(64)
        ->and($token['hash'])->toBe(Project::hashToken($token['plain']))
        ->and($token['hash'])->not->toBe($token['plain']);
});

it('generates a different token on every call', function (): void {
    expect(Project::generateToken()['plain'])->not->toBe(Project::generateToken()['plain']);
});

it('hides the token hash when serialized', function (): void {
    $project = Project::factory()->create();

    expect($project->toArray())->not->toHaveKey('token_hash');
});

it('relates a project to its issues', function (): void {
    $project = Project::factory()->create();
    $issue = Issue::factory()->create(['project_id' => $project->id]);

    expect($project->issues->pluck('id')->all())->toBe([$issue->id]);
});

it('reports the heartbeat status', function (?int $minutesAgo, HeartbeatStatus $expected): void {
    $project = Project::factory()->create([
        'last_heartbeat_at' => $minutesAgo === null ? null : now()->subMinutes($minutesAgo),
    ]);

    expect($project->heartbeatStatus())->toBe($expected);
})->with([
    'never seen' => [null, HeartbeatStatus::Missing],
    'just now' => [0, HeartbeatStatus::Ok],
    'within the threshold' => [14, HeartbeatStatus::Ok],
    'past the threshold' => [16, HeartbeatStatus::Stale],
]);

it('derives the heartbeat threshold from the configuration', function (): void {
    config()->set('monitoring.heartbeat_threshold_minutes', 30);

    expect(Project::heartbeatThreshold()->toIso8601String())
        ->toBe(now()->subMinutes(30)->toIso8601String());
});
