<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class CalculateExceptionFingerprint
{
    /**
     * @param  array<string, mixed>  $occurrence
     */
    public function execute(array $occurrence): string
    {
        $type = $occurrence['type'] ?? null;
        $exceptionClass = $occurrence['exception_class'] ?? null;
        $file = $occurrence['file'] ?? null;
        $line = $occurrence['line'] ?? null;

        $parts = [
            is_string($type) ? $type : '',
            is_string($exceptionClass) ? $exceptionClass : '',
            is_string($file) ? $file : '',
            is_int($line) ? (string) $line : '',
        ];

        return hash('sha1', implode('|', $parts));
    }
}
