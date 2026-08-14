<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Concerns\BuildsNotificationContent;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Telegram\TelegramMessage;

final class HeartbeatMissing extends Notification
{
    use BuildsNotificationContent;
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
        $lastHeartbeat = $this->localTime($this->project->last_heartbeat_at);

        return (new MailMessage)
            ->subject("Heartbeat missing: {$this->project->name}")
            ->line("Project {$this->project->name} ({$this->project->environment}) stopped sending heartbeats.")
            ->line("Last heartbeat: {$lastHeartbeat}")
            ->action('Open dashboard', route('dashboard'));
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $name = $this->escapeMarkdown($this->project->name);
        $environment = $this->escapeMarkdown($this->project->environment);
        $lastHeartbeat = $this->escapeMarkdown(
            $this->localTime($this->project->last_heartbeat_at),
        );

        return TelegramMessage::create()
            ->content("🔴 *Heartbeat missing*\n{$name} ({$environment})\nLast heartbeat: {$lastHeartbeat}")
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
