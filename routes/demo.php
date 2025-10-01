<?php

use Illuminate\Support\Facades\Route;
use Bestmomo\LaravelEdgeTts\Http\Controllers\DemoController;

// Demo route
Route::get('edge-tts/demo', DemoController::class)->name('edge-tts.demo');