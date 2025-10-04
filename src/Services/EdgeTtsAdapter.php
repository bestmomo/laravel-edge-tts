<?php

namespace Bestmomo\LaravelEdgeTts\Services;

use Afaya\EdgeTTS\Service\EdgeTTS;
use Bestmomo\LaravelEdgeTts\Contracts\TtsSynthesizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EdgeTtsAdapter implements TtsSynthesizer
{
    protected EdgeTTS $edgeTts;
    protected string $audioData = '';
    protected bool $logCalls = false;

    public function __construct(EdgeTTS $edgeTts, bool $logCalls = false)
    {
        $this->edgeTts = $edgeTts;
        $this->logCalls = $logCalls;
    }

    /**
     * @inheritDoc
     */
    public function synthesizeStream(string $text, string $voice, array $options, callable $callback): void
    {
        $this->edgeTts->synthesizeStream($text, $voice, $options, $callback);
    }

    /**
     * @inheritDoc
     */
    public function synthesize(string $text, string $voice, array $options = []): string
    {
        $startTime = microtime(true);

        if ($this->logCalls) {
            Log::info('Edge TTS Call Started', [
                'method' => 'synthesize',
                'text_length' => strlen($text),
                'voice' => $voice,
                'options' => $options
            ]);
        }

        $cacheKey = md5(serialize([$text, $voice, $options]));

        $result = Cache::remember($cacheKey, now()->addDay(), function() use ($text, $voice, $options) {
            $this->audioData = '';
            $this->edgeTts->synthesizeStream($text, $voice, $options, function($chunk) {
                $this->audioData .= $chunk;
            });
            return $this->audioData;
        });

        if ($this->logCalls) {
            $duration = microtime(true) - $startTime;
            Log::info('Edge TTS Call Completed', [
                'method' => 'synthesize',
                'voice' => $voice,
                'text_length' => strlen($text),
                'audio_size' => strlen($result),
                'duration_ms' => round($duration * 1000, 2),
                'cached' => true
            ]);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getVoices(): array
    {
        return $this->edgeTts->getVoices();
    }

    /**
     * @inheritDoc
     */
    public function toBase64(string $text, string $voice, array $options = []): string
    {
        $audioData = $this->synthesize($text, $voice, $options);
        return base64_encode($audioData);
    }

    /**
     * @inheritDoc
     */
    public function toFile(string $text, string $voice, string $fileName, array $options = []): string
    {
        $audioData = $this->synthesize($text, $voice, $options);

        $dir = dirname($fileName);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($fileName, $audioData);
        return $fileName;
    }

    /**
     * @inheritDoc
     */
    public function toRaw(string $text, string $voice, array $options = []): string
    {
        return $this->synthesize($text, $voice, $options);
    }
}
