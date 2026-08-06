<?php

declare(strict_types=1);

use App\Models\Project;
use App\Enums\OccurrenceType;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ProcessIngestedOccurrences;

/**
 * @param  array<int, array<string, mixed>>  $occurrences
 * @return array<string, mixed>
 */
function ingestPayload(array $occurrences = []): array
{
    return [
        'schema_version' => 1,
        'sent_at' => now()->toIso8601String(),
        'environment' => 'production',
        'occurrences' => $occurrences === [] ? [ingestOccurrence()] : $occurrences,
    ];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function ingestOccurrence(array $overrides = []): array
{
    return [
        'type' => OccurrenceType::Exception->value,
        'occurred_at' => now()->toIso8601String(),
        'exception_class' => 'RuntimeException',
        'message' => 'Order 1234 failed',
        'file' => '/app/Http/Controllers/OrderController.php',
        'line' => 42,
        'stack' => ['#0 /app/Http/Controllers/OrderController.php(42)'],
        'context' => ['user_id' => 7],
        ...$overrides,
    ];
}

it('accepts a valid payload and dispatches it for processing', function (): void {
    Bus::fake();

    $token = Project::generateToken();
    $project = Project::factory()->withToken($token['plain'])->create();

    $response = $this
        ->withToken($token['plain'])
        ->postJson(route('api.ingest'), ingestPayload());

    $response->assertNoContent(202);

    Bus::assertDispatched(
        ProcessIngestedOccurrences::class,
        fn (ProcessIngestedOccurrences $job): bool => $job->projectId === $project->id
            && count($job->occurrences) === 1
            && $job->occurrences[0]['exception_class'] === 'RuntimeException',
    );
});

it('rejects a request without a token', function (): void {
    Bus::fake();

    Project::factory()->create();

    $response = $this->postJson(route('api.ingest'), ingestPayload());

    $response->assertUnauthorized();

    Bus::assertNothingDispatched();
});

it('rejects a request with an unknown token', function (): void {
    Bus::fake();

    Project::factory()->create();

    $response = $this
        ->withToken('this-token-does-not-exist')
        ->postJson(route('api.ingest'), ingestPayload());

    $response->assertUnauthorized();

    Bus::assertNothingDispatched();
});

it('rejects a payload with more than one hundred occurrences', function (): void {
    Bus::fake();

    $token = Project::generateToken();
    Project::factory()->withToken($token['plain'])->create();

    $occurrences = array_map(fn (int $line): array => ingestOccurrence(['line' => $line]), range(1, 101));

    $response = $this
        ->withToken($token['plain'])
        ->postJson(route('api.ingest'), ingestPayload($occurrences));

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors('occurrences');

    Bus::assertNothingDispatched();
});

it('rejects a payload with an unknown schema version', function (): void {
    Bus::fake();

    $token = Project::generateToken();
    Project::factory()->withToken($token['plain'])->create();

    $payload = [...ingestPayload(), 'schema_version' => 2];

    $response = $this
        ->withToken($token['plain'])
        ->postJson(route('api.ingest'), $payload);

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors('schema_version');

    Bus::assertNothingDispatched();
});

it('rejects a payload with an unknown occurrence type', function (): void {
    Bus::fake();

    $token = Project::generateToken();
    Project::factory()->withToken($token['plain'])->create();

    $response = $this
        ->withToken($token['plain'])
        ->postJson(route('api.ingest'), ingestPayload([ingestOccurrence(['type' => 'meteor_strike'])]));

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors('occurrences.0.type');

    Bus::assertNothingDispatched();
});

it('rejects an occurrence with an empty context', function (): void {
    Bus::fake();

    $token = Project::generateToken();
    Project::factory()->withToken($token['plain'])->create();

    $response = $this
        ->withToken($token['plain'])
        ->postJson(route('api.ingest'), ingestPayload([ingestOccurrence(['context' => []])]));

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors('occurrences.0.context');

    Bus::assertNothingDispatched();
});

it('accepts an occurrence without a context', function (): void {
    Bus::fake();

    $token = Project::generateToken();
    Project::factory()->withToken($token['plain'])->create();

    $occurrence = ingestOccurrence();
    unset($occurrence['context']);

    $response = $this
        ->withToken($token['plain'])
        ->postJson(route('api.ingest'), ingestPayload([$occurrence]));

    $response->assertNoContent(202);

    Bus::assertDispatched(ProcessIngestedOccurrences::class);
});

it('rejects a log occurrence with malformed breadcrumbs', function (): void {
    Bus::fake();

    $token = Project::generateToken();
    Project::factory()->withToken($token['plain'])->create();

    $occurrence = ingestOccurrence([
        'type' => OccurrenceType::Log->value,
        'channel' => 'stack',
        'breadcrumbs' => [['message' => 'Checkout started']],
    ]);

    $response = $this
        ->withToken($token['plain'])
        ->postJson(route('api.ingest'), ingestPayload([$occurrence]));

    $response
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'occurrences.0.breadcrumbs.0.level',
            'occurrences.0.breadcrumbs.0.logged_at',
        ]);

    Bus::assertNothingDispatched();
});
