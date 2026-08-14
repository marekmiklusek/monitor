<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Concerns\BuildsNotificationContent;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\Telegram\TelegramMessage;

final class QueueStalled extends Notification
{
    use BuildsNotificationContent;

    /**
     * @param  array<int, string>  $reasons
     */
    public function __construct(private readonly array $reasons)
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
        $mail = (new MailMessage)
            ->subject('Queue stalled: issue notifications are not going out')
            ->line('The queue worker looks stopped or failing, so issue notifications are not being delivered.');

        foreach ($this->reasons as $reason) {
            $mail->line("- {$reason}");
        }

        return $mail->action('Open dashboard', route('dashboard'));
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $lines = [
            '🚨 *Queue stalled*',
            $this->escapeMarkdown('Issue notifications are not going out.'),
        ];

        foreach ($this->reasons as $reason) {
            $lines[] = $this->escapeMarkdown($reason);
        }

        return TelegramMessage::create()
            ->content(implode("\n", $lines))
            ->button('Open dashboard', route('dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['reasons' => $this->reasons];
    }
}
