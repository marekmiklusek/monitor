<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Support\Str;
use App\Enums\OccurrenceType;
use App\Actions\CheckQueueHealth;
use Illuminate\Support\Facades\DB;
use App\Notifications\QueueStalled;
use App\Notifications\QueueRecovered;
use Illuminate\Support\Facades\Cache;
use App\Notifications\AdminNotifiable;
use Illuminate\Testing\PendingCommand;
use App\Actions\ProcessIngestedOccurrences;
use Illuminate\Support\Facades\Notification;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function stalledOccurrence(array $overrides = []): array
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

function pendingSince(Project $project, string $queuedAt): void
{
    $issue = Issue::factory()->create(['project_id' => $project->id]);

    $project->fill([
        'pending_issue_notifications' => [
            ['issue_id' => $issue->id, 'kind' => 'new', 'queued_at' => $queuedAt],
        ],
    ])->save();
}

it('stays quiet when nothing is pending and no job failed', function (): void {
    Notification::fake();

    Project::factory()->create();

    $result = resolve(CheckQueueHealth::class)->execute();

    Notification::assertNothingSent();

    expect($result['alerted'])->toBeFalse()
        ->and($result['recovered'])->toBeFalse();
});

it('alerts when a notification has been pending for too long', function (): void {
    Notification::fake();

    $project = Project::factory()->create(['name' => 'Checkout API']);

    pendingSince($project, now()->subMinutes(30)->toIso8601String());

    $result = resolve(CheckQueueHealth::class)->execute();

    Notification::assertSentTimes(QueueStalled::class, 1);

    expect($result['alerted'])->toBeTrue()
        ->and($result['reasons'][0])->toContain('Checkout API');
});

it('alerts when jobs failed in the last hour', function (): void {
    Notification::fake();

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'Telegram token rejected',
        'failed_at' => now()->subMinutes(10),
    ]);

    $result = resolve(CheckQueueHealth::class)->execute();

    Notification::assertSentTimes(QueueStalled::class, 1);

    expect($result['reasons'][0])->toContain('1 job(s) failed');
});

it('ignores jobs that failed more than an hour ago', function (): void {
    Notification::fake();

    DB::table('failed_jobs')->insert([
        'uuid' => (string) Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => '{}',
        'exception' => 'Telegram token rejected',
        'failed_at' => now()->subHours(2),
    ]);

    resolve(CheckQueueHealth::class)->execute();

    Notification::assertNothingSent();
});

it('does not repeat the alert while the problem lasts', function (): void {
    Notification::fake();

    $project = Project::factory()->create();

    pendingSince($project, now()->subMinutes(30)->toIso8601String());

    resolve(CheckQueueHealth::class)->execute();

    $result = resolve(CheckQueueHealth::class)->execute();

    Notification::assertSentTimes(QueueStalled::class, 1);

    expect($result['alerted'])->toBeFalse();
});

it('repeats the alert once the reminder window passes', function (): void {
    Notification::fake();

    $project = Project::factory()->create();

    pendingSince($project, now()->subMinutes(30)->toIso8601String());

    resolve(CheckQueueHealth::class)->execute();

    $this->travel(61)->minutes();

    resolve(CheckQueueHealth::class)->execute();

    Notification::assertSentTimes(QueueStalled::class, 2);
});

it('notifies about the recovery once the queue drains', function (): void {
    Notification::fake();

    $project = Project::factory()->create();

    pendingSince($project, now()->subMinutes(30)->toIso8601String());

    resolve(CheckQueueHealth::class)->execute();

    $project->fill(['pending_issue_notifications' => null])->save();

    $result = resolve(CheckQueueHealth::class)->execute();

    Notification::assertSentTimes(QueueRecovered::class, 1);

    expect($result['recovered'])->toBeTrue();
});

it('does not notify about a recovery that was never alerted on', function (): void {
    Notification::fake();

    Project::factory()->create();

    resolve(CheckQueueHealth::class)->execute();

    Notification::assertNotSentTo(new AdminNotifiable, QueueRecovered::class);
});

it('does not notify about a recovery when the cache was cleared', function (): void {
    Notification::fake();

    $project = Project::factory()->create();

    pendingSince($project, now()->subMinutes(30)->toIso8601String());

    resolve(CheckQueueHealth::class)->execute();

    Cache::flush();

    $project->fill(['pending_issue_notifications' => null])->save();

    resolve(CheckQueueHealth::class)->execute();

    Notification::assertNotSentTo(new AdminNotifiable, QueueRecovered::class);
});

it('stays quiet about a batch that is only waiting for the next flush', function (): void {
    config()->set('monitoring.max_immediate_notifications_per_minute', 1);

    $project = Project::factory()->create();

    resolve(ProcessIngestedOccurrences::class)->execute($project, [stalledOccurrence()]);

    resolve(ProcessIngestedOccurrences::class)->execute($project, [
        stalledOccurrence(['line' => 11]),
        stalledOccurrence(['line' => 22]),
    ]);

    expect($project->refresh()->pending_issue_notifications)->toHaveCount(2);

    Notification::fake();

    $this->travel(2)->minutes();

    resolve(CheckQueueHealth::class)->execute();

    Notification::assertNothingSent();
});

it('ignores pending entries that carry no timestamp', function (): void {
    Notification::fake();

    $project = Project::factory()->create();

    $issue = Issue::factory()->create(['project_id' => $project->id]);

    $project->fill([
        'pending_issue_notifications' => [['issue_id' => $issue->id, 'kind' => 'new']],
    ])->save();

    resolve(CheckQueueHealth::class)->execute();

    Notification::assertNothingSent();
});

function expectQueueHealthOutput(PendingCommand|int $command, string $expected): void
{
    expect($command)->toBeInstanceOf(PendingCommand::class);

    if ($command instanceof PendingCommand) {
        $command->expectsOutputToContain($expected)->run();
    }
}

it('reports the queue health from the command', function (): void {
    Notification::fake();

    Project::factory()->create();

    expectQueueHealthOutput($this->artisan('monitor:check-queue-health'), 'The queue is healthy.');
});

it('reports a stalled queue from the command', function (): void {
    Notification::fake();

    $project = Project::factory()->create(['name' => 'Checkout API']);

    pendingSince($project, now()->subMinutes(30)->toIso8601String());

    expectQueueHealthOutput($this->artisan('monitor:check-queue-health'), 'Checkout API');
    expectQueueHealthOutput($this->artisan('monitor:check-queue-health'), 'The queue is still stalled.');
});

it('reports the recovery from the command', function (): void {
    Notification::fake();

    $project = Project::factory()->create();

    pendingSince($project, now()->subMinutes(30)->toIso8601String());

    resolve(CheckQueueHealth::class)->execute();

    $project->fill(['pending_issue_notifications' => null])->save();

    expectQueueHealthOutput(
        $this->artisan('monitor:check-queue-health'),
        'The queue is processing issue notifications again.',
    );
});
