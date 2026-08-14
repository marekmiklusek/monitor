<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use App\Enums\IssueStatus;
use App\Enums\OccurrenceType;
use App\Notifications\IssueDigest;
use App\Notifications\IssueOpened;
use App\Notifications\AdminNotifiable;
use App\Actions\FlushIssueNotifications;
use App\Actions\ProcessIngestedOccurrences;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function notifiableOccurrence(array $overrides = []): array
{
    return [
        'type' => OccurrenceType::Exception->value,
        'occurred_at' => now()->toIso8601String(),
        'exception_class' => 'RuntimeException',
        'message' => 'Order 1234 failed',
        'file' => '/app/Http/Controllers/OrderController.php',
        'line' => 42,
        ...$overrides,
    ];
}

it('notifies about a new issue', function (): void {
    Notification::fake();

    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Notification::assertSentTimes(IssueOpened::class, 1);

    expect($project->refresh()->pending_issue_notifications)->toBeNull();
});

it('does not notify about a repeated occurrence of an open issue', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Notification::fake();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Notification::assertNothingSent();

    expect(Issue::query()->sole()->occurrences_count)->toBe(2);
});

it('notifies about a regression when a resolved issue reopens', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Issue::query()->sole()->fill(['status' => IssueStatus::Resolved])->save();

    Notification::fake();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Notification::assertSentTimes(IssueOpened::class, 1);

    expect(Issue::query()->sole()->status)->toBe(IssueStatus::Open);
});

it('does not notify about a repeated occurrence of an ignored issue', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Issue::query()->sole()->fill(['status' => IssueStatus::Ignored])->save();

    Notification::fake();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Notification::assertNothingSent();
});

it('notifies about a new issue immediately, even right after a previous notification', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Notification::fake();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 99]),
    ]);

    Notification::assertSentTimes(IssueOpened::class, 1);

    expect($project->refresh()->pending_issue_notifications)->toBeNull();
});

it('notifies about a regression immediately, even right after a previous notification', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Issue::query()->sole()->fill(['status' => IssueStatus::Resolved])->save();

    Notification::fake();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Notification::assertSentTimes(IssueOpened::class, 1);

    expect($project->refresh()->pending_issue_notifications)->toBeNull();
});

it('merges issues opened in a single batch into one digest', function (): void {
    Notification::fake();

    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 11]),
        notifiableOccurrence(['line' => 22]),
        notifiableOccurrence(['line' => 33]),
    ]);

    Notification::assertSentTimes(IssueDigest::class, 1);
    Notification::assertNotSentTo(new AdminNotifiable, IssueOpened::class);

    expect($project->refresh()->pending_issue_notifications)->toBeNull();
});

it('holds back the issues that exceed the immediate limit', function (): void {
    config()->set('monitoring.max_immediate_notifications_per_minute', 2);

    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);
    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence(['line' => 11])]);

    Notification::fake();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence(['line' => 22])]);

    Notification::assertNothingSent();

    expect($project->refresh()->pending_issue_notifications)->toHaveCount(1);
});

it('sends the rate limited issues as a digest once the limiter decays', function (): void {
    config()->set('monitoring.max_immediate_notifications_per_minute', 1);

    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 11]),
        notifiableOccurrence(['line' => 22]),
    ]);

    expect($project->refresh()->pending_issue_notifications)->toHaveCount(2);

    Notification::fake();

    $this->travel(2)->minutes();

    $this->artisan('monitor:flush-issue-notifications');

    Notification::assertSentTimes(IssueDigest::class, 1);

    expect($project->refresh()->pending_issue_notifications)->toBeNull();
});

it('sends a single notification when only one rate limited issue was held back', function (): void {
    config()->set('monitoring.max_immediate_notifications_per_minute', 1);

    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 99]),
    ]);

    Notification::fake();

    $this->travel(2)->minutes();

    $this->artisan('monitor:flush-issue-notifications');

    Notification::assertSentTimes(IssueOpened::class, 1);
    Notification::assertNotSentTo(new AdminNotifiable, IssueDigest::class);
});

it('keeps the rate limited issues pending while the limiter is still exhausted', function (): void {
    config()->set('monitoring.max_immediate_notifications_per_minute', 1);

    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 99]),
    ]);

    Notification::fake();

    $this->artisan('monitor:flush-issue-notifications');

    Notification::assertNothingSent();

    expect($project->refresh()->pending_issue_notifications)->toHaveCount(1);
});

it('queues the issue notifications instead of sending them during the request', function (string $notification): void {
    expect(class_implements($notification))->toContain(ShouldQueue::class);
})->with([IssueOpened::class, IssueDigest::class]);

it('drops pending entries whose issue no longer exists', function (): void {
    config()->set('monitoring.max_immediate_notifications_per_minute', 1);

    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 99]),
    ]);

    Issue::query()->delete();

    Notification::fake();

    $this->travel(2)->minutes();

    $this->artisan('monitor:flush-issue-notifications');

    Notification::assertNothingSent();

    expect($project->refresh()->pending_issue_notifications)->toBeNull();
});

it('sends one digest across every channel, not one per issue', function (): void {
    config()->set('services.telegram-bot-api.token', 'testing-token');
    config()->set('monitoring.telegram_chat_id', '4242');
    config()->set('monitoring.channels', ['mail', 'telegram']);

    $project = Project::factory()->create();

    Notification::fake();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 11]),
        notifiableOccurrence(['line' => 22]),
    ]);

    Notification::assertSentTimes(IssueDigest::class, 1);

    Notification::assertSentTo(
        new AdminNotifiable,
        IssueDigest::class,
        fn (IssueDigest $notification, array $channels): bool => $channels === ['mail', 'telegram'],
    );
});

it('does nothing when there is nothing pending', function (): void {
    Notification::fake();

    Project::factory()->create();

    $this->artisan('monitor:flush-issue-notifications');

    Notification::assertNothingSent();
});

it('does nothing when the pending list is empty', function (): void {
    Notification::fake();

    $project = Project::factory()->create(['pending_issue_notifications' => []]);

    resolve(FlushIssueNotifications::class)->execute($project);

    Notification::assertNothingSent();
});
