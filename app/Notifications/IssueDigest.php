<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use App\Enums\IssueNotificationKind;
use Illuminate\Notifications\Notification;
use App\Concerns\BuildsNotificationContent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Telegram\TelegramMessage;

final class IssueDigest extends Notification implements ShouldQueue
{
    use BuildsNotificationContent;
    use Queueable;

    /**
     * @param  array<int, array{issue: Issue, kind: IssueNotificationKind}>  $entries
     */
    public function __construct(
        private readonly Project $project,
        private readonly array $entries,
    ) {
        // ...
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return AdminNotifiable::channels();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = count($this->entries);

        $mail = (new MailMessage)
            ->subject("{$count} new issues in {$this->project->name}")
            ->line("Project {$this->project->name} ({$this->project->environment})");

        foreach ($this->entries as $entry) {
            $marker = $entry['kind'] === IssueNotificationKind::Regression ? '[regression] ' : '';

            $mail->line("- {$marker}{$entry['issue']->title}");
        }

        return $mail->action(
            'View issues',
            route('issues.index', ['project' => $this->project->id, 'status' => 'open']),
        );
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $count = count($this->entries);

        $lines = [
            "🆕 *{$count} new issues*",
            $this->escapeMarkdown("{$this->project->name} ({$this->project->environment})"),
        ];

        foreach ($this->entries as $entry) {
            $marker = $entry['kind'] === IssueNotificationKind::Regression ? '⚠️ ' : '';

            $lines[] = $marker.$this->escapeMarkdown($entry['issue']->title);
        }

        return TelegramMessage::create()
            ->content(implode("\n", $lines))
            ->button(
                'View issues',
                route('issues.index', ['project' => $this->project->id, 'status' => 'open']),
            );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'issue_ids' => array_map(
                fn (array $entry): string => $entry['issue']->id,
                $this->entries,
            ),
        ];
    }
}
