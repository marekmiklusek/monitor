<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Issue;
use App\Enums\IssueStatus;

it('rejects guests', function (): void {
    $issue = Issue::factory()->create();

    $this->post(route('issues.status.store', $issue), ['status' => 'resolved'])
        ->assertRedirect(route('login'));
});

it('changes the status of an issue', function (IssueStatus $status): void {
    $issue = Issue::factory()->create(['status' => IssueStatus::Open]);

    $this->actingAs(User::factory()->create())
        ->from(route('issues.show', $issue))
        ->post(route('issues.status.store', $issue), ['status' => $status->value])
        ->assertRedirect(route('issues.show', $issue));

    expect($issue->refresh()->status)->toBe($status);
})->with(IssueStatus::cases());

it('reopens a resolved issue', function (): void {
    $issue = Issue::factory()->resolved()->create();

    $this->actingAs(User::factory()->create())
        ->from(route('issues.show', $issue))
        ->post(route('issues.status.store', $issue), ['status' => IssueStatus::Open->value]);

    expect($issue->refresh()->status)->toBe(IssueStatus::Open);
});

it('rejects an unknown status', function (): void {
    $issue = Issue::factory()->create();

    $this->actingAs(User::factory()->create())
        ->from(route('issues.show', $issue))
        ->post(route('issues.status.store', $issue), ['status' => 'exploded'])
        ->assertSessionHasErrors('status');

    expect($issue->refresh()->status)->toBe(IssueStatus::Open);
});

it('requires a status', function (): void {
    $issue = Issue::factory()->create();

    $this->actingAs(User::factory()->create())
        ->from(route('issues.show', $issue))
        ->post(route('issues.status.store', $issue), [])
        ->assertSessionHasErrors('status');
});
