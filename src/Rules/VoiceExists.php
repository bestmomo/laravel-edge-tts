<?php

namespace Bestmomo\LaravelEdgeTts\Rules;

use Bestmomo\LaravelEdgeTts\Contracts\TtsSynthesizer;
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Cache;

class VoiceExists implements ValidationRule
{
    /**
     * Check if the voice exists in the available voices.
     */
    protected function voiceExists(string $voice): bool
    {
        $availableVoices = Cache::remember('edge_tts_available_voices', 3600, function () {
            return app(TtsSynthesizer::class)->getVoices();
        });

        try {
            return is_array($availableVoices) && in_array($voice, array_column($availableVoices, 'ShortName'), true);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Validate the attribute.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !$this->voiceExists($value)) {
            $fail('The selected voice is not available.');
        }
    }
}
