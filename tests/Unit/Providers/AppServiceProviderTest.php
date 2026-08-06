<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Validation\Rules\Password;

it('applies strict password defaults in production', function (): void {
    app()->detectEnvironment(fn (): string => 'production');

    new AppServiceProvider(app())->boot();

    $default = Password::default();

    expect($default)->toEqual(
        Password::min(12)
            ->mixedCase()
            ->letters()
            ->numbers()
            ->symbols()
            ->uncompromised(),
    );
});

it('applies no password defaults outside production', function (): void {
    new AppServiceProvider(app())->boot();

    expect(Password::default())->toEqual(Password::min(8));
});
