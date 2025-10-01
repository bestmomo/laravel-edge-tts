<?php

namespace Bestmomo\LaravelEdgeTts\Contracts;

interface TtsSynthesizer
{
    /**
     * Synthesize text to speech and execute a callback function for streaming the audio chunks.
     *
     * @param string $text The text to synthesize.
     * @param string $voice The voice name (e.g., 'fr-FR-DeniseNeural').
     * @param array $options Modulation options (rate, volume, pitch).
     * @param callable $callback Function to be called with each audio chunk.
     * @return void
     */
    public function synthesizeStream(string $text, string $voice, array $options, callable $callback): void;

    /**
     * Synthesize text to speech and return the complete audio data (MP3) as a string.
     * This is useful for saving files or caching.
     *
     * @param string $text The text to synthesize.
     * @param string $voice The voice name.
     * @param array $options Modulation options (rate, volume, pitch).
     * @return string The raw MP3 audio data.
     */
    public function synthesize(string $text, string $voice, array $options = []): string;

    /**
     * Retrieves the list of available voices from the service.
     *
     * @return array
     */
    public function getVoices(): array;

      /**
     * Get the synthesized audio data as a Base64 encoded string.
     *
     * @param string $text The text to synthesize.
     * @param string $voice The voice name.
     * @param array $options Modulation options.
     * @return string The audio data encoded in Base64.
     */
    public function toBase64(string $text, string $voice, array $options = []): string;

    /**
     * Save the synthesized audio to a file.
     *
     * @param string $text The text to synthesize.
     * @param string $voice The voice name.
     * @param string $fileName The path/name of the file to save (without extension).
     * @param array $options Modulation options.
     * @return string The full path to the saved file.
     */
    public function toFile(string $text, string $voice, string $fileName, array $options = []): string;

    /**
     * Get the raw synthesized audio data (MP3 stream) as a string.
     * Alias for the synthesize() method in this context.
     *
     * @param string $text The text to synthesize.
     * @param string $voice The voice name.
     * @param array $options Modulation options.
     * @return string The raw MP3 audio data.
     */
    public function toRaw(string $text, string $voice, array $options = []): string;

}