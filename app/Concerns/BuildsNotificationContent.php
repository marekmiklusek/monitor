<?php

declare(strict_types=1);

namespace App\Concerns;

use Carbon\CarbonInterface;
use App\Actions\ShortenSourcePath;

trait BuildsNotificationContent
{
    private function escapeMarkdown(string $text): string
    {
        return str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $text);
    }

    private function sourcePath(string $file, ?int $line): string
    {
        return resolve(ShortenSourcePath::class)->execute($file, $line);
    }

    private function localTime(?CarbonInterface $moment): string
    {
        return $moment instanceof CarbonInterface
            ? $moment->copy()->setTimezone(config()->string('monitoring.timezone'))->format('d.m.Y H:i:s')
            : 'never';
    }
}
