<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Support\Facades\Log;
use Illuminate\Notifications\Notifiable;

final class AdminNotifiable
{
    use Notifiable;

    /**
     * @return array<int, string>
     */
    public static function channels(): array
    {
        $channels = config()->array('monitoring.channels');

        return array_values(array_filter(
            $channels,
            fn (mixed $channel): bool => is_string($channel) && self::isUsable($channel),
        ));
    }

    public function getKey(): string
    {
        return 'monitoring-admin';
    }

    public function routeNotificationForMail(): string
    {
        return config()->string('monitoring.admin_email');
    }

    public function routeNotificationForTelegram(): ?string
    {
        $chatId = config()->string('monitoring.telegram_chat_id', '');

        return $chatId === '' ? null : $chatId;
    }

    private static function isUsable(string $channel): bool
    {
        if ($channel !== 'telegram') {
            return true;
        }

        $token = config()->string('services.telegram-bot-api.token', '');
        $chatId = config()->string('monitoring.telegram_chat_id', '');

        if ($token !== '' && $chatId !== '') {
            return true;
        }

        Log::warning('Telegram notifications are enabled but not configured; skipping the channel.', [
            'has_token' => $token !== '',
            'has_chat_id' => $chatId !== '',
        ]);

        return false;
    }
}
