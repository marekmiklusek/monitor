<?php

declare(strict_types=1);

use App\Models\Project;
use App\Actions\CheckHeartbeats;
use Illuminate\Testing\PendingCommand;
use App\Notifications\HeartbeatMissing;
use App\Notifications\HeartbeatRecovered;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\AnonymousNotifiable;

it('alerts once when a project stops sending heartbeats', function (): void {
    Notification::fake();

    $project = Project::factory()->create([
        'last_heartbeat_at' => now()->subMinutes(20),
        'heartbeat_alerted_at' => null,
    ]);

    resolve(CheckHeartbeats::class)->execute();

    Notification::assertSentTimes(HeartbeatMissing::class, 1);

    expect($project->refresh()->heartbeat_alerted_at)->not->toBeNull();

    resolve(CheckHeartbeats::class)->execute();

    Notification::assertSentTimes(HeartbeatMissing::class, 1);
});

it('alerts for a project that never sent a heartbeat', function (): void {
    Notification::fake();

    Project::factory()->create([
        'last_heartbeat_at' => null,
        'heartbeat_alerted_at' => null,
    ]);

    resolve(CheckHeartbeats::class)->execute();

    Notification::assertSentTimes(HeartbeatMissing::class, 1);
});

it('does not alert while heartbeats keep arriving', function (): void {
    Notification::fake();

    Project::factory()->create([
        'last_heartbeat_at' => now()->subMinutes(2),
        'heartbeat_alerted_at' => null,
    ]);

    resolve(CheckHeartbeats::class)->execute();

    Notification::assertNothingSent();
});

it('sends a recovery notification and clears the alert', function (): void {
    Notification::fake();

    $project = Project::factory()->create([
        'last_heartbeat_at' => now()->subMinutes(2),
        'heartbeat_alerted_at' => now()->subMinutes(30),
    ]);

    resolve(CheckHeartbeats::class)->execute();

    Notification::assertSentTimes(HeartbeatRecovered::class, 1);

    expect($project->refresh()->heartbeat_alerted_at)->toBeNull();

    resolve(CheckHeartbeats::class)->execute();

    Notification::assertSentTimes(HeartbeatRecovered::class, 1);
});

it('sends notifications to the configured admin email', function (): void {
    Notification::fake();

    config()->set('monitoring.admin_email', 'ops@example.com');

    Project::factory()->create([
        'last_heartbeat_at' => now()->subMinutes(20),
        'heartbeat_alerted_at' => null,
    ]);

    resolve(CheckHeartbeats::class)->execute();

    Notification::assertSentTo(
        new AnonymousNotifiable,
        HeartbeatMissing::class,
        fn (HeartbeatMissing $notification, array $channels, AnonymousNotifiable $notifiable): bool => $notifiable->routes['mail'] === 'ops@example.com',
    );
});

it('runs the check through the artisan command', function (): void {
    Notification::fake();

    Project::factory()->create([
        'last_heartbeat_at' => now()->subMinutes(20),
        'heartbeat_alerted_at' => null,
    ]);

    $command = $this->artisan('monitor:check-heartbeats');

    expect($command)->toBeInstanceOf(PendingCommand::class);

    if ($command instanceof PendingCommand) {
        $command->assertSuccessful()->run();
    }

    Notification::assertSentTimes(HeartbeatMissing::class, 1);
});

it('builds the outage mail message', function (): void {
    $project = Project::factory()->create([
        'name' => 'Checkout API',
        'environment' => 'production',
        'last_heartbeat_at' => now()->subMinutes(20),
    ]);

    $mail = new HeartbeatMissing($project)->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toBe('Heartbeat missing: Checkout API')
        ->and(new HeartbeatMissing($project)->via(new AnonymousNotifiable))->toBe(['mail'])
        ->and(new HeartbeatMissing($project)->toArray(new AnonymousNotifiable))
        ->toHaveKey('project_id', $project->id);
});

it('builds the recovery mail message', function (): void {
    $project = Project::factory()->create([
        'name' => 'Checkout API',
        'environment' => 'production',
        'last_heartbeat_at' => now(),
    ]);

    $mail = new HeartbeatRecovered($project)->toMail(new AnonymousNotifiable);

    expect($mail->subject)->toBe('Heartbeat recovered: Checkout API')
        ->and(new HeartbeatRecovered($project)->via(new AnonymousNotifiable))->toBe(['mail'])
        ->and(new HeartbeatRecovered($project)->toArray(new AnonymousNotifiable))
        ->toHaveKey('project_id', $project->id);
});

it('reports never as the last heartbeat when none was received', function (): void {
    $project = Project::factory()->create([
        'name' => 'Checkout API',
        'last_heartbeat_at' => null,
    ]);

    expect(new HeartbeatMissing($project)->toArray(new AnonymousNotifiable))
        ->toHaveKey('last_heartbeat_at', null)
        ->and(new HeartbeatRecovered($project)->toArray(new AnonymousNotifiable))
        ->toHaveKey('last_heartbeat_at', null);
});
