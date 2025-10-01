<?php

namespace Bestmomo\LaravelEdgeTts\Http\Controllers;

use Illuminate\Routing\Controller;
use Bestmomo\LaravelEdgeTts\Contracts\TtsSynthesizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DemoController extends Controller
{
    /**
     * Loads and displays the demo page with the list of voices.
     */
    public function __invoke(TtsSynthesizer $tts)
    {
        try {
            $voices = Cache::remember('edge_tts_available_voices', 3600, function () {
                return app(TtsSynthesizer::class)->getVoices();     
            });
        } catch (\Exception $e) {
            Log::error('Error loading Edge TTS voices: ' . $e->getMessage());
            $voices = [];
        }

        // Passes the list of voices (or an empty array) to the view
        return view('laravel-edge-tts::index', compact('voices'));
    }
}