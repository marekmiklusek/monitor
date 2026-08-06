<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use App\Models\Occurrence;

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

it('relates an issue to its project and occurrences', function (): void {
    $issue = Issue::factory()->create();
    $occurrence = Occurrence::factory()->create(['issue_id' => $issue->id]);

    expect($issue->project->id)->toBe($issue->project_id)
        ->and($issue->occurrences->pluck('id')->all())->toBe([$occurrence->id]);
});

it('relates an occurrence back to its issue', function (): void {
    $occurrence = Occurrence::factory()->create();

    expect($occurrence->issue->id)->toBe($occurrence->issue_id);
});
