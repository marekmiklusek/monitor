<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Issue;
use App\Models\Occurrence;
use App\Enums\OccurrenceType;

it('reveals the breadcrumb context only for breadcrumbs that carry one', function (): void {
    $issue = Issue::factory()->create();

    Occurrence::factory()->for($issue)->create([
        'payload' => [
            'type' => OccurrenceType::Exception->value,
            'exception_class' => 'RuntimeException',
            'breadcrumbs' => [
                [
                    'level' => 'info',
                    'message' => 'Checkout started',
                    'context' => ['order_id' => 4711],
                    'logged_at' => now()->toIso8601String(),
                ],
                [
                    'level' => 'error',
                    'message' => 'Payment declined',
                    'logged_at' => now()->toIso8601String(),
                ],
            ],
        ],
    ]);

    $this->actingAs(User::factory()->create());

    $page = visit(route('issues.show', $issue));

    $page->assertSee('Checkout started')
        ->assertSee('Payment declined')
        ->assertDontSee('order_id')
        ->click('Checkout started')
        ->assertSee('order_id')
        ->assertNoJavaScriptErrors();
});

it('hides the stack trace section when the occurrence carries no frames', function (): void {
    $issue = Issue::factory()->create([
        'type' => OccurrenceType::Log,
        'title' => 'payments',
        'message' => 'Gateway timeout',
        'file' => null,
        'line' => null,
    ]);

    Occurrence::factory()->for($issue)->create([
        'payload' => [
            'type' => OccurrenceType::Log->value,
            'channel' => 'payments',
            'message' => 'Gateway timeout',
        ],
    ]);

    $this->actingAs(User::factory()->create());

    $page = visit(route('issues.show', $issue));

    $page->assertSee('Gateway timeout')
        ->assertDontSee(__('Stack trace'))
        ->assertDontSee(__('Location'))
        ->assertNoJavaScriptErrors();
});

it('shows the location for an issue that carries a file', function (): void {
    $issue = Issue::factory()->create(['file' => '/app/Http/Controllers/OrderController.php', 'line' => 42]);

    Occurrence::factory()->for($issue)->create();

    $this->actingAs(User::factory()->create());

    $page = visit(route('issues.show', $issue));

    $page->assertSee(__('Location'))
        ->assertSee('OrderController.php')
        ->assertNoJavaScriptErrors();
});

it('labels the occurrence type on the issue inbox', function (): void {
    Issue::factory()->create(['type' => OccurrenceType::FailedJob, 'title' => 'SendInvoice']);

    $this->actingAs(User::factory()->create());

    $page = visit(route('issues.index'));

    $page->assertSee('Failed job')
        ->assertDontSee('failed_job')
        ->assertNoJavaScriptErrors();
});
