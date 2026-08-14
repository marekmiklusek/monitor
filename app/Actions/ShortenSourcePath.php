<?php

declare(strict_types=1);

namespace App\Actions;

final readonly class ShortenSourcePath
{
    public function execute(string $file, ?int $line = null): string
    {
        $parts = array_values(array_filter(
            preg_split('#[\\\\/]#', $file) ?: [],
            fn (string $part): bool => $part !== '',
        ));

        $name = array_pop($parts) ?? $file;

        $vendorAt = $this->vendorPosition($parts);

        $kept = $vendorAt === null
            ? array_slice($parts, -2)
            : array_slice($parts, $vendorAt + 1);

        $shortened = $kept === [] ? $name : implode('/', [...$kept, $name]);

        return $line === null ? $shortened : "{$shortened}:{$line}";
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function vendorPosition(array $parts): ?int
    {
        foreach ($parts as $index => $part) {
            if ($part === 'vendor' || $part === 'node_modules') {
                return $index;
            }
        }

        return null;
    }
}
