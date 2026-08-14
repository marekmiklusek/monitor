<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Concerns\BuildsNotificationContent;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Telegram\TelegramMessage;

final class QueueRecovered extends Notification
{
    use BuildsNotificationContent;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return AdminNotifiable::channels();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Queue recovered: issue notifications are going out again')
            ->line('The queue worker is processing issue notifications again.')
            ->action('Open dashboard', route('dashboard'));
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        return TelegramMessage::create()
            ->content("✅ *Queue recovered*\n".$this->escapeMarkdown('Issue notifications are going out again.'))
            ->button('Open dashboard', route('dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
