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

    expect($project->refresh()->issues_notified_at)->not->toBeNull()
        ->and($project->pending_issue_notifications)->toBeNull();
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

    $project->fill(['issues_notified_at' => null])->save();

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

it('holds back notifications inside the throttle window', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    Notification::fake();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 99]),
    ]);

    Notification::assertNothingSent();

    expect($project->refresh()->pending_issue_notifications)->toHaveCount(1);
});

it('merges the held back issues into a single digest', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 11]),
        notifiableOccurrence(['line' => 22]),
        notifiableOccurrence(['line' => 33]),
    ]);

    expect($project->refresh()->pending_issue_notifications)->toHaveCount(3);

    Notification::fake();

    $this->travel(20)->minutes();

    $this->artisan('monitor:flush-issue-notifications');

    Notification::assertSentTimes(IssueDigest::class, 1);
    Notification::assertNotSentTo(new AdminNotifiable, IssueOpened::class);

    expect($project->refresh()->pending_issue_notifications)->toBeNull();
});

it('sends a single notification when only one issue was held back', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 99]),
    ]);

    Notification::fake();

    $this->travel(20)->minutes();

    $this->artisan('monitor:flush-issue-notifications');

    Notification::assertSentTimes(IssueOpened::class, 1);
    Notification::assertNotSentTo(new AdminNotifiable, IssueDigest::class);
});

it('drops pending entries whose issue no longer exists', function (): void {
    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 99]),
    ]);

    Issue::query()->delete();

    Notification::fake();

    $this->travel(20)->minutes();

    $this->artisan('monitor:flush-issue-notifications');

    Notification::assertNothingSent();

    expect($project->refresh()->pending_issue_notifications)->toBeNull();
});

it('sends one digest across every channel, not one per issue', function (): void {
    config()->set('services.telegram-bot-api.token', 'testing-token');
    config()->set('monitoring.telegram_chat_id', '4242');
    config()->set('monitoring.channels', ['mail', 'telegram']);

    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [notifiableOccurrence()]);

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        notifiableOccurrence(['line' => 11]),
        notifiableOccurrence(['line' => 22]),
    ]);

    Notification::fake();

    $this->travel(20)->minutes();

    $this->artisan('monitor:flush-issue-notifications');

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
