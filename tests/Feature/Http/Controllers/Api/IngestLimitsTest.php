<?php

declare(strict_types=1);

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Enums\OccurrenceType;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ProcessIngestedOccurrences;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Middleware\LimitIngestPayloadSize;

beforeEach(function (): void {
    RateLimiter::clear('ingest');
});

/**
 * @param  array<int, array<string, mixed>>  $occurrences
 * @return array<string, mixed>
 */
function limitedPayload(array $occurrences = []): array
{
    return [
        'schema_version' => 1,
        'sent_at' => now()->toIso8601String(),
        'environment' => 'production',
        'occurrences' => $occurrences === [] ? [[
            'type' => OccurrenceType::Exception->value,
            'occurred_at' => now()->toIso8601String(),
            'exception_class' => 'RuntimeException',
        ]] : $occurrences,
    ];
}

it('rejects a project that exceeds the rate limit', function (): void {
    Bus::fake();

    config()->set('monitoring.ingest_rate_limit', 2);

    $token = Project::generateToken();
    Project::factory()->withToken($token['plain'])->create();

    foreach (range(1, 2) as $ignored) {
        $this->withToken($token['plain'])
            ->postJson(route('api.ingest'), limitedPayload())
            ->assertNoContent(202);
    }

    $this->withToken($token['plain'])
        ->postJson(route('api.ingest'), limitedPayload())
        ->assertStatus(429);

    Bus::assertDispatchedTimes(ProcessIngestedOccurrences::class, 2);
});

it('limits each project separately', function (): void {
    Bus::fake();

    config()->set('monitoring.ingest_rate_limit', 1);

    $first = Project::generateToken();
    $second = Project::generateToken();

    Project::factory()->withToken($first['plain'])->create();
    Project::factory()->withToken($second['plain'])->create();

    $this->withToken($first['plain'])
        ->postJson(route('api.ingest'), limitedPayload())
        ->assertNoContent(202);

    $this->withToken($first['plain'])
        ->postJson(route('api.ingest'), limitedPayload())
        ->assertStatus(429);

    $this->withToken($second['plain'])
        ->postJson(route('api.ingest'), limitedPayload())
        ->assertNoContent(202);
});

it('rejects a payload above the size limit without touching the database', function (): void {
    Bus::fake();

    config()->set('monitoring.max_payload_kilobytes', 1);

    $token = Project::generateToken();
    Project::factory()->withToken($token['plain'])->create();

    $occurrences = array_map(fn (int $line): array => [
        'type' => OccurrenceType::Exception->value,
        'occurred_at' => now()->toIso8601String(),
        'exception_class' => 'RuntimeException',
        'message' => str_repeat('a', 200),
        'line' => $line,
    ], range(1, 20));

    $this->withToken($token['plain'])
        ->postJson(route('api.ingest'), limitedPayload($occurrences))
        ->assertStatus(413)
        ->assertJsonPath('message', 'Payload too large.');

    Bus::assertNothingDispatched();

    expect(Issue::query()->count())->toBe(0);
});

it('accepts a payload below the size limit', function (): void {
    Bus::fake();

    config()->set('monitoring.max_payload_kilobytes', 512);

    $token = Project::generateToken();
    Project::factory()->withToken($token['plain'])->create();

    $this->withToken($token['plain'])
        ->postJson(route('api.ingest'), limitedPayload())
        ->assertNoContent(202);
});

it('rejects an oversized payload before authenticating the project', function (): void {
    Bus::fake();

    config()->set('monitoring.max_payload_kilobytes', 1);

    $occurrences = array_map(fn (int $line): array => [
        'type' => OccurrenceType::Exception->value,
        'occurred_at' => now()->toIso8601String(),
        'message' => str_repeat('a', 200),
        'line' => $line,
    ], range(1, 20));

    $this->postJson(route('api.ingest'), limitedPayload($occurrences))
        ->assertStatus(413);
});

it('falls back to the body length when there is no content length header', function (): void {
    config()->set('monitoring.max_payload_kilobytes', 1);

    $middleware = resolve(LimitIngestPayloadSize::class);

    $request = Request::create('/api/ingest', 'POST', [], [], [], [], str_repeat('a', 2048));
    $request->headers->remove('Content-Length');

    $response = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($response->getStatusCode())->toBe(413);
});

it('passes a small body through when there is no content length header', function (): void {
    config()->set('monitoring.max_payload_kilobytes', 1);

    $middleware = resolve(LimitIngestPayloadSize::class);

    $request = Request::create('/api/ingest', 'POST', [], [], [], [], 'tiny');
    $request->headers->remove('Content-Length');

    $response = $middleware->handle($request, fn (): Response => new Response('ok'));

    expect($response->getStatusCode())->toBe(200);
});
