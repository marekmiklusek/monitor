<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use App\Notifications\IssueDigest;
use App\Notifications\IssueOpened;
use App\Notifications\QueueStalled;
use Illuminate\Support\Facades\Log;
use App\Enums\IssueNotificationKind;
use Illuminate\Support\Facades\Date;
use App\Notifications\QueueRecovered;
use Illuminate\Support\Facades\Event;
use App\Notifications\AdminNotifiable;
use App\Notifications\HeartbeatMissing;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Log\Events\MessageLogged;
use App\Notifications\HeartbeatRecovered;
use App\Notifications\SafeTelegramChannel;

beforeEach(function (): void {
    config()->set('services.telegram-bot-api.token', 'testing-token');
    config()->set('monitoring.telegram_chat_id', '4242');
});

it('resolves the channels from the configuration', function (string $configured, array $expected): void {
    config()->set('monitoring.channels', explode(',', $configured));

    expect(AdminNotifiable::channels())->toBe($expected);
})->with([
    'mail only' => ['mail', ['mail']],
    'telegram only' => ['telegram', ['telegram']],
    'both' => ['mail,telegram', ['mail', 'telegram']],
]);

it('routes to the configured mail address and chat', function (): void {
    config()->set('monitoring.admin_email', 'ops@example.com');

    $notifiable = new AdminNotifiable;

    expect($notifiable->routeNotificationForMail())->toBe('ops@example.com')
        ->and($notifiable->routeNotificationForTelegram())->toBe('4242');
});

it('has no telegram route when the chat id is missing', function (): void {
    config()->set('monitoring.telegram_chat_id', '');

    expect((new AdminNotifiable)->routeNotificationForTelegram())->toBeNull();
});

it('skips telegram and warns when it is enabled but not configured', function (string $missing): void {
    config()->set('monitoring.channels', ['mail', 'telegram']);
    config()->set($missing, '');

    $warnings = 0;

    Log::listen(function (MessageLogged $message) use (&$warnings): void {
        if ($message->level === 'warning') {
            $warnings++;
        }
    });

    expect(AdminNotifiable::channels())->toBe(['mail'])
        ->and($warnings)->toBe(1);
})->with([
    'without token' => ['services.telegram-bot-api.token'],
    'without chat id' => ['monitoring.telegram_chat_id'],
]);

it('builds a telegram message for a missing heartbeat', function (): void {
    $project = Project::factory()->create([
        'name' => 'Checkout API',
        'environment' => 'production',
        'last_heartbeat_at' => now()->subMinutes(20),
    ]);

    $message = new HeartbeatMissing($project)->toTelegram(new AdminNotifiable);

    expect($message->toArray()['text'])->toContain('🔴 *Heartbeat missing*')
        ->toContain('Checkout API (production)');
});

it('builds a telegram message for a recovered heartbeat', function (): void {
    $project = Project::factory()->create(['name' => 'Checkout API']);

    $text = new HeartbeatRecovered($project)->toTelegram(new AdminNotifiable)->toArray()['text'];

    expect($text)->toContain('🟢 *Heartbeat recovered*');
});

it('builds a telegram message for a new issue', function (): void {
    $project = Project::factory()->create(['name' => 'Checkout API']);

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'title' => 'RuntimeException',
        'message' => 'Order failed',
        'file' => '/app/Orders.php',
        'line' => 42,
    ]);

    $text = new IssueOpened($project, $issue, IssueNotificationKind::NewIssue)
        ->toTelegram(new AdminNotifiable)
        ->toArray()['text'];

    expect($text)->toContain('🆕 *New issue*')
        ->toContain('RuntimeException')
        ->toContain('Order failed')
        ->toContain('app/Orders.php:42');
});

it('shortens a long hosting path in the telegram message', function (): void {
    $project = Project::factory()->create();

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'file' => '/data/7/7/77941b09-b3c1-4024-a9cb-91e72006b433/wellmall.webotvurcidev.cz/www/vendor/psy/psysh/src/ExecutionClosure.php',
        'line' => 41,
    ]);

    $text = new IssueOpened($project, $issue, IssueNotificationKind::NewIssue)
        ->toTelegram(new AdminNotifiable)
        ->toArray()['text'];

    expect($text)->toContain('psy/psysh/src/ExecutionClosure.php:41')
        ->not->toContain('77941b09');
});

it('shortens a long hosting path in the mail message', function (): void {
    $project = Project::factory()->create();

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'file' => '/data/7/7/77941b09-b3c1-4024-a9cb-91e72006b433/wellmall.webotvurcidev.cz/www/vendor/psy/psysh/src/ExecutionClosure.php',
        'line' => 41,
    ]);

    $mail = new IssueOpened($project, $issue, IssueNotificationKind::NewIssue)
        ->toMail(new AdminNotifiable);

    expect($mail->introLines)->toContain('psy/psysh/src/ExecutionClosure.php:41');
});

it('marks a regression in the telegram message', function (): void {
    $project = Project::factory()->create();
    $issue = Issue::factory()->create(['project_id' => $project->id]);

    $text = new IssueOpened($project, $issue, IssueNotificationKind::Regression)
        ->toTelegram(new AdminNotifiable)
        ->toArray()['text'];

    expect($text)->toContain('⚠️ *Regression*');
});

it('escapes markdown characters coming from the exception', function (): void {
    $project = Project::factory()->create(['name' => 'Checkout API']);

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'title' => 'App\Jobs\Send_Invoice',
        'message' => 'Undefined array key [user_id] in *config*',
    ]);

    $text = new IssueOpened($project, $issue, IssueNotificationKind::NewIssue)
        ->toTelegram(new AdminNotifiable)
        ->toArray()['text'];

    expect($text)->toContain('Send\_Invoice')
        ->toContain('\[user\_id]')
        ->toContain('\*config\*');
});

it('leaves a message without optional fields intact', function (): void {
    $project = Project::factory()->create(['name' => 'Checkout API']);

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'title' => 'RuntimeException',
        'message' => null,
        'file' => null,
        'line' => null,
    ]);

    $text = new IssueOpened($project, $issue, IssueNotificationKind::NewIssue)
        ->toTelegram(new AdminNotifiable)
        ->toArray()['text'];

    expect($text)->toContain('RuntimeException')
        ->not->toContain('null');
});

it('sends one telegram message for a digest of several issues', function (): void {
    $project = Project::factory()->create(['name' => 'Checkout API']);

    $entries = Issue::factory()->count(3)->create(['project_id' => $project->id])
        ->map(fn (Issue $issue): array => [
            'issue' => $issue,
            'kind' => IssueNotificationKind::NewIssue,
        ])->all();

    $message = new IssueDigest($project, $entries)->toTelegram(new AdminNotifiable);

    expect($message->toArray()['text'])->toContain('🆕 *3 new issues*');
});

it('exposes the issue in the array representation', function (): void {
    $project = Project::factory()->create();
    $issue = Issue::factory()->create(['project_id' => $project->id]);

    $payload = new IssueOpened($project, $issue, IssueNotificationKind::Regression)
        ->toArray(new AdminNotifiable);

    expect($payload)->toBe([
        'project_id' => $project->id,
        'issue_id' => $issue->id,
        'kind' => 'regression',
    ]);
});

it('exposes every issue in the digest array representation', function (): void {
    $project = Project::factory()->create();

    $issues = Issue::factory()->count(2)->create(['project_id' => $project->id]);

    $entries = $issues->map(fn (Issue $issue): array => [
        'issue' => $issue,
        'kind' => IssueNotificationKind::NewIssue,
    ])->all();

    $payload = new IssueDigest($project, $entries)->toArray(new AdminNotifiable);

    expect($payload)->toBe([
        'project_id' => $project->id,
        'issue_ids' => $issues->pluck('id')->all(),
    ]);
});

it('builds a mail message for a new issue', function (): void {
    $project = Project::factory()->create(['name' => 'Checkout API']);

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'title' => 'RuntimeException',
        'message' => 'Order failed',
        'file' => '/app/Orders.php',
        'line' => 42,
    ]);

    $mail = new IssueOpened($project, $issue, IssueNotificationKind::NewIssue)
        ->toMail(new AdminNotifiable);

    expect($mail->subject)->toBe('New issue: RuntimeException')
        ->and($mail->introLines)->toContain('Order failed')
        ->and($mail->introLines)->toContain('app/Orders.php:42');
});

it('titles a regression mail differently', function (): void {
    $project = Project::factory()->create();

    $issue = Issue::factory()->create([
        'project_id' => $project->id,
        'title' => 'RuntimeException',
        'message' => null,
        'file' => null,
    ]);

    $mail = new IssueOpened($project, $issue, IssueNotificationKind::Regression)
        ->toMail(new AdminNotifiable);

    expect($mail->subject)->toBe('Regression: RuntimeException');
});

it('builds a mail message for a digest', function (): void {
    $project = Project::factory()->create(['name' => 'Checkout API']);

    $entries = Issue::factory()->count(2)->create(['project_id' => $project->id])
        ->map(fn (Issue $issue): array => [
            'issue' => $issue,
            'kind' => IssueNotificationKind::Regression,
        ])->all();

    $mail = new IssueDigest($project, $entries)->toMail(new AdminNotifiable);

    expect($mail->subject)->toBe('2 new issues in Checkout API');
});

it('keeps the notification alive when the telegram api fails', function (): void {
    $warnings = 0;

    Log::listen(function (MessageLogged $message) use (&$warnings): void {
        if ($message->level === 'warning') {
            $warnings++;
        }
    });

    $project = Project::factory()->create();

    $result = resolve(SafeTelegramChannel::class)
        ->send(new AdminNotifiable, new HeartbeatMissing($project));

    expect($result)->toBeNull()
        ->and($warnings)->toBe(1);
});

it('still delivers the mail when telegram is down', function (): void {
    $warnings = 0;

    Log::listen(function (MessageLogged $message) use (&$warnings): void {
        if ($message->level === 'warning') {
            $warnings++;
        }
    });

    config()->set('monitoring.channels', ['mail', 'telegram']);

    $sentMails = 0;

    Event::listen(MessageSent::class, function () use (&$sentMails): void {
        $sentMails++;
    });

    $project = Project::factory()->create([
        'last_heartbeat_at' => now()->subMinutes(20),
        'heartbeat_alerted_at' => null,
    ]);

    $this->artisan('monitor:check-heartbeats');

    expect($sentMails)->toBe(1)
        ->and($warnings)->toBe(1)
        ->and($project->refresh()->heartbeat_alerted_at)->not->toBeNull();
});

it('builds a telegram message for a stalled queue', function (): void {
    $text = new QueueStalled(['Issue notifications stuck in the queue for Checkout API.'])
        ->toTelegram(new AdminNotifiable)
        ->toArray()['text'];

    expect($text)->toContain('🚨 *Queue stalled*')
        ->toContain('Checkout API');
});

it('builds a mail message for a stalled queue', function (): void {
    $mail = new QueueStalled(['2 job(s) failed in the last hour.'])->toMail(new AdminNotifiable);

    expect($mail->subject)->toBe('Queue stalled: issue notifications are not going out')
        ->and($mail->introLines)->toContain('- 2 job(s) failed in the last hour.');
});

it('exposes the reasons in the stalled array representation', function (): void {
    $payload = new QueueStalled(['Broken.'])->toArray(new AdminNotifiable);

    expect($payload)->toBe(['reasons' => ['Broken.']]);
});

it('builds the messages for a recovered queue', function (): void {
    $notification = new QueueRecovered;

    $text = $notification->toTelegram(new AdminNotifiable)->toArray()['text'];

    expect($text)->toContain('✅ *Queue recovered*')
        ->and($notification->toMail(new AdminNotifiable)->subject)
        ->toBe('Queue recovered: issue notifications are going out again')
        ->and($notification->toArray(new AdminNotifiable))->toBeEmpty();
});

it('sends the queue notifications on the configured channels', function (): void {
    config()->set('monitoring.channels', ['mail', 'telegram']);

    expect(new QueueStalled([])->via(new AdminNotifiable))->toBe(['mail', 'telegram'])
        ->and((new QueueRecovered)->via(new AdminNotifiable))->toBe(['mail', 'telegram']);
});

it('writes the heartbeat time in the notification timezone', function (): void {
    config()->set('monitoring.timezone', 'Europe/Bratislava');

    $project = Project::factory()->create([
        'last_heartbeat_at' => Date::parse('2026-08-14 16:34:26', 'UTC'),
    ]);

    $text = new HeartbeatMissing($project)->toTelegram(new AdminNotifiable)->toArray()['text'];

    expect($text)->toContain('14.08.2026 18:34:26');
});

it('says never when a project has no heartbeat yet', function (): void {
    $project = Project::factory()->create(['last_heartbeat_at' => null]);

    $text = new HeartbeatMissing($project)->toTelegram(new AdminNotifiable)->toArray()['text'];

    expect($text)->toContain('never');
});
