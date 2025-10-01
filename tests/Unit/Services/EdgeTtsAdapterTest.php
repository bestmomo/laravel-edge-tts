<?php

namespace Tests\Unit\Services;

use Afaya\EdgeTTS\Service\EdgeTTS;
use Bestmomo\LaravelEdgeTts\Services\EdgeTtsAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use Bestmomo\LaravelEdgeTts\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EdgeTtsAdapterTest extends TestCase
{
    private EdgeTTS|MockObject $edgeTtsMock;
    private EdgeTtsAdapter $adapter;
    private string $audioData = 'simulated_audio_data';

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock for EdgeTTS
        $this->edgeTtsMock = $this->createMock(EdgeTTS::class);
        $this->adapter = new EdgeTtsAdapter($this->edgeTtsMock);
    }

    private function setupSynthesizeStreamMock(): void
    {
        $this->edgeTtsMock->expects($this->once())
            ->method('synthesizeStream')
            ->willReturnCallback(function ($text, $voice, $options, $callback) {
                // Simulate sending audio data in chunks
                $chunks = str_split($this->audioData, 5);
                foreach ($chunks as $chunk) {
                    $callback($chunk);
                }
            });
    }

    #[Test]
    public function it_can_synthesize_text_to_speech()
    {
        $text = 'Hello world';
        $voice = 'en-US-AriaNeural';
        $options = ['rate' => '1.0'];

        $this->setupSynthesizeStreamMock();

        $result = $this->adapter->synthesize($text, $voice, $options);
        $this->assertEquals($this->audioData, $result);
    }

    #[Test]
    public function it_can_convert_text_to_base64()
    {
        $text = 'Hello world';
        $voice = 'en-US-AriaNeural';
        $options = [];
        $expectedResult = base64_encode($this->audioData);

        $this->setupSynthesizeStreamMock();

        $result = $this->adapter->toBase64($text, $voice, $options);
        $this->assertEquals($expectedResult, $result);
    }

    #[Test]
    public function it_can_save_speech_to_file()
    {
        $text = 'Hello world';
        $voice = 'en-US-AriaNeural';
        $fileName = 'test.mp3';
        $options = [];

        $this->setupSynthesizeStreamMock();

        // Use a temporary file for the test
        $tempFile = sys_get_temp_dir() . '/' . uniqid('test_') . '.mp3';
        
        $result = $this->adapter->toFile($text, $voice, $tempFile, $options);
        
        $this->assertEquals($tempFile, $result);
        $this->assertFileExists($tempFile);
        $this->assertEquals($this->audioData, file_get_contents($tempFile));
        
        // Clean up
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
    }

    #[Test]
    public function it_can_convert_text_to_raw_audio()
    {
        $text = 'Hello world';
        $voice = 'en-US-AriaNeural';
        $options = [];

        $this->setupSynthesizeStreamMock();

        $result = $this->adapter->toRaw($text, $voice, $options);
        $this->assertEquals($this->audioData, $result);
    }

    #[Test]
    public function it_can_get_available_voices()
    {
        $expectedVoices = [
            ['Name' => 'en-US-AriaNeural', 'Gender' => 'Female'],
            ['Name' => 'fr-FR-DeniseNeural', 'Gender' => 'Female'],
        ];

        $this->edgeTtsMock->expects($this->once())
            ->method('getVoices')
            ->willReturn($expectedVoices);

        $voices = $this->adapter->getVoices();
        $this->assertEquals($expectedVoices, $voices);
    }

    #[Test]
    public function it_can_synthesize_stream()
    {
        $text = 'Hello world';
        $voice = 'en-US-AriaNeural';
        $options = [];
        $receivedChunks = [];
        
        $callback = function($chunk) use (&$receivedChunks) {
            $receivedChunks[] = $chunk;
        };
    
        $this->edgeTtsMock->expects($this->once())
            ->method('synthesizeStream')
            ->with(
                $text,
                $voice,
                $options,
                $this->callback(fn($arg) => is_callable($arg))
            )
            ->willReturnCallback(function ($text, $voice, $options, $callback) {
                $chunks = str_split($this->audioData, 5);
                foreach ($chunks as $chunk) {
                    $callback($chunk);
                }
            });
    
        $this->adapter->synthesizeStream($text, $voice, $options, $callback);
        
        // Vérifie que le callback a bien été appelé avec les données
        $this->assertNotEmpty($receivedChunks);
        $this->assertEquals($this->audioData, implode('', $receivedChunks));
    }
}
