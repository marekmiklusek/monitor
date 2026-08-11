<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use App\Concerns\BuildsTelegramMessages;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Telegram\TelegramMessage;

final class HeartbeatRecovered extends Notification
{
    use BuildsTelegramMessages;
    use Queueable;

    public function __construct(private readonly Project $project)
    {
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
        $lastHeartbeat = $this->project->last_heartbeat_at?->toDateTimeString() ?? 'never';

        return (new MailMessage)
            ->subject("Heartbeat recovered: {$this->project->name}")
            ->line("Project {$this->project->name} ({$this->project->environment}) is sending heartbeats again.")
            ->line("Last heartbeat: {$lastHeartbeat}")
            ->action('Open dashboard', route('dashboard'));
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $name = $this->escapeMarkdown($this->project->name);
        $environment = $this->escapeMarkdown($this->project->environment);

        return TelegramMessage::create()
            ->content("🟢 *Heartbeat recovered*\n{$name} ({$environment})")
            ->button('Open dashboard', route('dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'last_heartbeat_at' => $this->project->last_heartbeat_at?->toIso8601String(),
        ];
    }
}
