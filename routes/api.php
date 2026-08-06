<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IngestController;

Route::middleware('project.token')->group(function (): void {
    Route::post('ingest', IngestController::class)->name('api.ingest');
});
