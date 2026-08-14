<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use App\Enums\IssueNotificationKind;
use App\Concerns\BuildsTelegramMessages;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Telegram\TelegramMessage;

final class IssueOpened extends Notification implements ShouldQueue
{
    use BuildsTelegramMessages;
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly Issue $issue,
        private readonly IssueNotificationKind $kind,
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
        $prefix = $this->kind === IssueNotificationKind::Regression ? 'Regression' : 'New issue';

        $mail = (new MailMessage)
            ->subject("{$prefix}: {$this->issue->title}")
            ->line("Project {$this->project->name} ({$this->project->environment})")
            ->line($this->issue->title);

        if ($this->issue->message !== null) {
            $mail->line($this->issue->message);
        }

        if ($this->issue->file !== null) {
            $mail->line($this->sourcePath($this->issue->file, $this->issue->line));
        }

        return $mail->action('View issue', route('issues.show', $this->issue));
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $regression = $this->kind === IssueNotificationKind::Regression;

        $heading = $regression ? '⚠️ *Regression*' : '🆕 *New issue*';

        $lines = [
            $heading,
            $this->escapeMarkdown("{$this->project->name} ({$this->project->environment})"),
            $this->escapeMarkdown($this->issue->title),
        ];

        if ($this->issue->message !== null) {
            $lines[] = $this->escapeMarkdown($this->issue->message);
        }

        if ($this->issue->file !== null) {
            $lines[] = $this->escapeMarkdown($this->sourcePath($this->issue->file, $this->issue->line));
        }

        return TelegramMessage::create()
            ->content(implode("\n", $lines))
            ->button('View issue', route('issues.show', $this->issue));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'issue_id' => $this->issue->id,
            'kind' => $this->kind->value,
        ];
    }
}
