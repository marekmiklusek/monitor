<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;
use App\Http\Requests\Settings\TwoFactorAuthenticationRequest;

final class TwoFactorAuthenticationController implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')
            ? [new Middleware('password.confirm', only: ['show'])]
            : [];
    }

    /**
     * Show the user's two-factor authentication settings page.
     */
    public function show(TwoFactorAuthenticationRequest $request): Response
    {
        $request->ensureStateIsValid();

        /** @var User $user */
        $user = $request->user();

        return Inertia::render('settings/two-factor', [
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'requiresConfirmation' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ]);
    }
}
