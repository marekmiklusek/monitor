<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    /** @var Illuminate\Routing\Route $route */
    $route = Route::inertia('dashboard', 'dashboard');

    $route->name('dashboard');
});

require __DIR__.'/settings.php';
