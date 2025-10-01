<?php
return [
    // Define default voice
    'default_voice' => env('EDGE_TTS_DEFAULT_VOICE', 'fr-FR-DeniseNeural'),

    // Define middleware
    'middleware' => [
        // 'web' is often necessary for 'auth' or 'throttle' based on session to work.
        'web', 
        
        // Requires a user connection. Change to 'auth:sanctum' if it's for an API.
        'auth', 
        
        // Limit requests to 60 per minute to prevent abuse.
        'throttle:60,1', 
    ],

    // Define cache parameters
    'cache' => [
        // Enable/Disable cache.
        'enabled' => env('EDGE_TTS_CACHE_ENABLED', true),
        
        // Laravel disk to use for MP3 files. 
        // 'local' stores in storage/app. You can use 'public' if you want them to be accessible directly.
        'disk' => env('EDGE_TTS_CACHE_DISK', 'local'),
        
        // Cache duration in minutes (or null for unlimited duration).
        'lifetime' => env('EDGE_TTS_CACHE_LIFETIME', null), 
    ],

    // Enable Call Logging
    'enable_call_logging' => env('EDGE_TTS_LOG_CALLS', false),
];