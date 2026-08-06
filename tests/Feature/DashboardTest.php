<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

it('allows authenticated users to visit the dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});
