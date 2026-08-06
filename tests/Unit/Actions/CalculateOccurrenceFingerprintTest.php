<?php

declare(strict_types=1);

use App\Enums\OccurrenceType;
use App\Actions\CalculateOccurrenceFingerprint;

it('uses the exception strategy for non log types', function (OccurrenceType $type): void {
    $occurrence = [
        'type' => $type->value,
        'exception_class' => 'RuntimeException',
        'file' => '/app/Jobs/SendInvoice.php',
        'line' => 17,
    ];

    $expected = hash('sha1', implode('|', [$type->value, 'RuntimeException', '/app/Jobs/SendInvoice.php', '17']));

    expect(resolve(CalculateOccurrenceFingerprint::class)->execute($type, $occurrence))->toBe($expected);
})->with([
    OccurrenceType::Exception,
    OccurrenceType::FailedJob,
    OccurrenceType::SlowQuery,
    OccurrenceType::Heartbeat,
]);

it('uses the log strategy for log types', function (): void {
    $occurrence = [
        'type' => OccurrenceType::Log->value,
        'channel' => 'payments',
        'message' => 'Charge 42 declined',
    ];

    $expected = hash('sha1', implode('|', [OccurrenceType::Log->value, 'payments', 'Charge {n} declined']));

    expect(resolve(CalculateOccurrenceFingerprint::class)->execute(OccurrenceType::Log, $occurrence))->toBe($expected);
});

it('falls back to empty parts when the occurrence has no identifying fields', function (): void {
    $expected = hash('sha1', implode('|', ['', '', '', '']));

    expect(resolve(CalculateOccurrenceFingerprint::class)->execute(OccurrenceType::Exception, []))->toBe($expected);
});

it('ignores non string and non integer values', function (): void {
    $occurrence = [
        'type' => OccurrenceType::Exception->value,
        'exception_class' => ['not', 'a', 'string'],
        'file' => 42,
        'line' => '17',
    ];

    $expected = hash('sha1', implode('|', [OccurrenceType::Exception->value, '', '', '']));

    expect(resolve(CalculateOccurrenceFingerprint::class)->execute(OccurrenceType::Exception, $occurrence))->toBe($expected);
});

it('ignores non string values when building a log fingerprint', function (): void {
    $occurrence = [
        'type' => OccurrenceType::Log->value,
        'channel' => 99,
        'message' => null,
    ];

    $expected = hash('sha1', implode('|', [OccurrenceType::Log->value, '', '']));

    expect(resolve(CalculateOccurrenceFingerprint::class)->execute(OccurrenceType::Log, $occurrence))->toBe($expected);
});
