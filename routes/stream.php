<?php

use Illuminate\Support\Facades\Route;
use Bestmomo\LaravelEdgeTts\Http\Controllers\AudioStreamController;

// Retrieves the list of middleware defined in the ‘edge-tts.middleware’ configuration file.
// If the config key does not exist, an empty array is used (no middleware).
$middlewares = config('edge-tts.middleware', []);

// The middleware is applied using the middleware() function with the retrieved array.
Route::middleware($middlewares)->group(function () {
    
    // Audio streaming route (protected by configuration)
    Route::get('edge-tts/stream', AudioStreamController::class)->name('edge-tts.stream');
});
