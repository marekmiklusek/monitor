<?php

declare(strict_types=1);

namespace App\Notifications;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;

final class SafeTelegramChannel extends TelegramChannel
{
    /**
     * @return array<mixed>|null
     */
    public function send(mixed $notifiable, Notification $notification): ?array
    {
        try {
            return parent::send($notifiable, $notification);
        } catch (Throwable $throwable) {
            Log::warning('Telegram notification failed.', [
                'notification' => $notification::class,
                'exception' => $throwable->getMessage(),
            ]);

            return null;
        }
    }
}
