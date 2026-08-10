<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

final class HeartbeatMissing extends Notification
{
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lastHeartbeat = $this->project->last_heartbeat_at?->toDateTimeString() ?? 'never';

        return (new MailMessage)
            ->subject("Heartbeat missing: {$this->project->name}")
            ->line("Project {$this->project->name} ({$this->project->environment}) stopped sending heartbeats.")
            ->line("Last heartbeat: {$lastHeartbeat}")
            ->action('Open dashboard', route('dashboard'));
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
