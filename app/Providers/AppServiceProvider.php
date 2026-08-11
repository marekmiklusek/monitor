<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Validation\Rules\Password;
use App\Notifications\SafeTelegramChannel;
use Illuminate\Support\Facades\RateLimiter;
use App\Http\Middleware\AuthenticateProject;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Notification::resolved(function (ChannelManager $manager): void {
            $manager->extend('telegram', fn (): SafeTelegramChannel => resolve(SafeTelegramChannel::class));
        });

        RateLimiter::for('ingest', function (Request $request): Limit {
            $project = $request->attributes->get(AuthenticateProject::ATTRIBUTE);

            return Limit::perMinute(config()->integer('monitoring.ingest_rate_limit'))
                ->by($project instanceof Project ? $project->id : (string) $request->ip());
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    private function configureDefaults(): void
    {
        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
