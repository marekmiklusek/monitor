<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\OccurrenceType;

final readonly class CalculateOccurrenceFingerprint
{
    public function __construct(
        private CalculateExceptionFingerprint $calculateExceptionFingerprint,
        private CalculateLogFingerprint $calculateLogFingerprint,
    ) {
        // ...
    }

    /**
     * @param  array<string, mixed>  $occurrence
     */
    public function execute(OccurrenceType $type, array $occurrence): string
    {
        return $this->strategy($type)->execute($occurrence);
    }

    private function strategy(OccurrenceType $type): CalculateExceptionFingerprint|CalculateLogFingerprint
    {
        if ($type === OccurrenceType::Log) {
            return $this->calculateLogFingerprint;
        }

        return $this->calculateExceptionFingerprint;
    }
}
