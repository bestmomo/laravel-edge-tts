<?php

namespace Bestmomo\LaravelEdgeTts\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage; 
use Bestmomo\LaravelEdgeTts\Contracts\TtsSynthesizer;
use Illuminate\Support\Facades\Log;
use Bestmomo\LaravelEdgeTts\Rules\VoiceExists;

class AudioStreamController extends Controller
{
    /**
     * Streams TTS audio directly to the browser.
     */
    public function __invoke(Request $request, TtsSynthesizer $tts): StreamedResponse
    {
        // INPUT VALIDATION
        $validatedData = $request->validate([
            'text' => 'required|string|max:5000',
            'voice' => ['nullable', 'string', 'max:100', new VoiceExists],
            'rate' => ['nullable', 'string', 'regex:/^[-+]?\d{1,3}%$/'],
            'volume' => ['nullable', 'string', 'regex:/^[-+]?\d{1,3}%$/'],
            'pitch' => ['nullable', 'string', 'regex:/^[-+]?\d{1,3}Hz$/'],
        ]);

        // CLEANED PARAMETERS
        $defaultVoice = config('edge-tts.default_voice');
        $voice = $validatedData['voice'] ?? $defaultVoice;
        $rate = $validatedData['rate'] ?? '0%';
        $volume = $validatedData['volume'] ?? '0%';
        $pitch = $validatedData['pitch'] ?? '0Hz';
        $text = trim($validatedData['text']);  

        $options = [];
    
        // SSML CHECK AND VALIDATION
        $isSsml = str_starts_with($text, '<speak');

        if ($isSsml) {
            // DIRECT CALL to the helper function
            if (!is_valid_ssml($text)) { 
                $errorMessage = "SSML syntax error: The XML content is not well-formed or the <speak> tag is missing/incorrect.";
                Log::error($errorMessage);
                
                return response()->stream(function() use ($errorMessage) { 
                    echo $errorMessage; 
                }, 400, [
                    'Content-Type' => 'text/plain',
                ]);
            }
        } else {
            $options = [
                'rate' => $rate, 
                'volume' => $volume, 
                'pitch' => $pitch,
            ];           
        }

        $diskName = config('edge-tts.cache.disk', 'local');
        $cacheEnabled = config('edge-tts.cache.enabled', false);
        $storage = Storage::disk($diskName);

        // UNIQUE HASH GENERATION
        $cacheKey = md5(json_encode([
            'text' => $text, 
            'voice' => $voice, 
            'options' => $options
        ]));
        $filePath = "tts/{$cacheKey}.mp3";
        

        // CACHE CHECK
        if ($cacheEnabled && $storage->exists($filePath)) {
            $stream = $storage->readStream($filePath);
            $fileSize = $storage->size($filePath);
            
            if ($stream === false) {
                $errorMessage = "Cache file access error: Failed to open stream for " . $filePath;
                Log::error($errorMessage);                

                return new StreamedResponse(function () use ($errorMessage) { 
                    echo $errorMessage; 
                }, 500, [
                    'Content-Type' => 'text/plain', 
                ]);
            }
            
            return new StreamedResponse(function() use ($stream) {
                fpassthru($stream);
                fclose($stream);
            }, 200, [
                'Content-Type' => 'audio/mpeg',
                'Content-Disposition' => 'inline; filename="tts_cached.mp3"',
                'Content-Length' => $fileSize, 
            ]);
        }

        // SYNTHESIS AND SAVING (If not found in cache)
        $buffer = '';
        $callback = function ($chunk) use (&$buffer) {
            $buffer .= $chunk; // Accumulate the stream in a buffer
            echo $chunk;      // Send the stream to the client
            flush(); 
        };

        try {
            // Synthesize and execute the callback
            $tts->synthesizeStream($text, $voice, $options, $callback);
            
            // After sending to the client, save the content in the cache if enabled
            if ($cacheEnabled) {
                $storage->put($filePath, $buffer);
                
                // If a lifetime is defined, it could be handled here (e.g., via an Artisan command)
                // The simple file system does not automatically expire, but it is recorded.
            }

        } catch (\Exception $e) {
            // Handle the error (e.g., failed connection)
            // An appropriate HTTP error response should be returned
            Log::error("Edge TTS Streaming Error: " . $e->getMessage());
            // It should return an appropriate HTTP error response
            return response()->stream(function() use ($e) { echo "Speech synthesis error: " . $e->getMessage(); }, 503);
        }

        $headers = [
            'Content-Type' => 'audio/mpeg',
            'Content-Disposition' => 'inline; filename="tts_live.mp3"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate', 
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        // We cannot easily add the content length in a StreamedResponse that has already sent data,
        // but the return is technically the TTS service response.
        return new StreamedResponse(function () {}, 200, $headers);
    }
}