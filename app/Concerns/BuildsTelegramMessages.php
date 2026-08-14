<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Actions\ShortenSourcePath;

trait BuildsTelegramMessages
{
    private function escapeMarkdown(string $text): string
    {
        return str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $text);
    }

    private function sourcePath(string $file, ?int $line): string
    {
        return resolve(ShortenSourcePath::class)->execute($file, $line);
    }
}
