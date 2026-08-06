<?php

declare(strict_types=1);

use App\Models\User;

it('redirects root to login page', function (): void {
    $page = visit('/');

    $page->assertPathIs('/login')
        ->assertSee(__('Log in to your account'))
        ->assertNoJavaScriptErrors();
});

it('logs the user in via the login page', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    $page = visit('/login');

    $page->assertSee(__('Log in to your account'))
        ->fill('email', $user->email)
        ->fill('password', 'password')
        ->click(__('Log in'))
        ->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($user);
});

it('registers a new user via the register page', function (): void {
    $page = visit('/register');

    $page->assertSee(__('Create an account'))
        ->fill('name', 'Test User')
        ->fill('email', 'newuser@example.com')
        ->fill('password', 'password1234')
        ->fill('password_confirmation', 'password1234')
        ->click('[data-test="register-user-button"]')
        ->assertPathIs('/email/verify')
        ->assertNoJavaScriptErrors();

    expect(User::query()->where('email', 'newuser@example.com')->exists())->toBeTrue();
});
