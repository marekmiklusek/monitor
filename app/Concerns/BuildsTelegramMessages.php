<?php

declare(strict_types=1);

namespace App\Concerns;

trait BuildsTelegramMessages
{
    private function escapeMarkdown(string $text): string
    {
        return str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $text);
    }
}
