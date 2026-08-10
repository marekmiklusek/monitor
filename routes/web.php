<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OccurrenceController;
use App\Http\Controllers\IssueStatusController;
use App\Http\Controllers\ProjectTokenController;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('issues', [IssueController::class, 'index'])->name('issues.index');
    Route::get('issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
    Route::post('issues/{issue}/status', IssueStatusController::class)->name('issues.status.store');

    Route::get('issues/{issue}/occurrences/{occurrence}', [OccurrenceController::class, 'show'])
        ->name('occurrences.show');

    Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::post('projects/{project}/token', ProjectTokenController::class)->name('projects.token.store');
});

require __DIR__.'/settings.php';
