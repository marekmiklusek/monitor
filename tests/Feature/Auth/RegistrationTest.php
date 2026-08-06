<?php

declare(strict_types=1);

test('registration screen can be rendered', function (): void {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function (): void {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration screen returns 404 when registration is disabled', function (): void {
    config()->set('fortify.registration_enabled', false);

    $this->get(route('register'))->assertNotFound();
});

test('registration submission returns 404 when registration is disabled', function (): void {
    config()->set('fortify.registration_enabled', false);

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
});
