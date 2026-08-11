<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IngestController;
use App\Http\Middleware\LimitIngestPayloadSize;

Route::middleware([LimitIngestPayloadSize::class, 'project.token', 'throttle:ingest'])->group(function (): void {
    Route::post('ingest', IngestController::class)->name('api.ingest');
});
