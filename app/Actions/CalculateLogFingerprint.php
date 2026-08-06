<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class CalculateLogFingerprint
{
    /**
     * @param  array<string, mixed>  $occurrence
     */
    public function execute(array $occurrence): string
    {
        $type = $occurrence['type'] ?? null;
        $channel = $occurrence['channel'] ?? null;
        $message = $occurrence['message'] ?? null;

        $parts = [
            is_string($type) ? $type : '',
            is_string($channel) ? $channel : '',
            is_string($message) ? $this->normalize($message) : '',
        ];

        return hash('sha1', implode('|', $parts));
    }

    private function normalize(string $message): string
    {
        $patterns = [
            '/[\w.+-]+@[\w-]+\.[\w.-]+/i' => '{email}',
            '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i' => '{uuid}',
            '/\d+/' => '{n}',
        ];

        return (string) preg_replace(array_keys($patterns), array_values($patterns), $message);
    }
}
