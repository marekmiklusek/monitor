<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Enums\IssueStatus;
use App\Actions\ChangeIssueStatus;

it('changes the status of an issue', function (IssueStatus $status): void {
    $issue = Issue::factory()->create(['status' => IssueStatus::Open]);

    $result = resolve(ChangeIssueStatus::class)->execute($issue, $status);

    expect($result->status)->toBe($status)
        ->and($issue->refresh()->status)->toBe($status);
})->with(IssueStatus::cases());

it('reopens a resolved issue', function (): void {
    $issue = Issue::factory()->resolved()->create();

    resolve(ChangeIssueStatus::class)->execute($issue, IssueStatus::Open);

    expect($issue->refresh()->status)->toBe(IssueStatus::Open);
});

it('leaves the other attributes untouched', function (): void {
    $issue = Issue::factory()->create([
        'status' => IssueStatus::Open,
        'occurrences_count' => 4,
        'title' => 'RuntimeException',
    ]);

    resolve(ChangeIssueStatus::class)->execute($issue, IssueStatus::Ignored);

    expect($issue->refresh()->occurrences_count)->toBe(4)
        ->and($issue->title)->toBe('RuntimeException');
});
