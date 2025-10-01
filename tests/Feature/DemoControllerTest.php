<?php

namespace Bestmomo\LaravelEdgeTts\Tests\Feature;

use Bestmomo\LaravelEdgeTts\Http\Controllers\DemoController;
use Bestmomo\LaravelEdgeTts\Contracts\TtsSynthesizer;
use Bestmomo\LaravelEdgeTts\Tests\TestCase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class DemoControllerTest extends TestCase
{
    private DemoController $controller;
    private TtsSynthesizer $tts;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('edge_tts_available_voices'); 
        
        // Mock for TtsSynthesizer
        $this->tts = $this->createMock(TtsSynthesizer::class);
        $this->app->instance(TtsSynthesizer::class, $this->tts);
        $this->controller = new DemoController();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_view_with_voices()
    {
        // Arrange
        $mockVoices = [
            ['ShortName' => 'en-US-AriaNeural', 'Gender' => 'Female', 'LocalName' => 'Aria'],
            ['ShortName' => 'fr-FR-DeniseNeural', 'Gender' => 'Female', 'LocalName' => 'Denise']
        ];
        
        $this->tts->method('getVoices')
            ->willReturn($mockVoices);

        Http::fake();

        // Act
        $response = $this->controller->__invoke($this->tts);

        // Assert
        $this->assertEquals('laravel-edge-tts::index', $response->name());
        $this->assertEquals($mockVoices, $response->getData()['voices']);
    }

    #[Test]
    public function it_handles_error_when_fetching_voices()
    {
        // Arrange
        $this->tts->method('getVoices')
            ->will($this->throwException(new \Exception('Connection error')));

        // Mock the logger
        Log::shouldReceive('error')
            ->once()
            ->with('Error loading Edge TTS voices: Connection error');

        // Act
        $response = $this->controller->__invoke($this->tts);

        // Assert
        $this->assertEquals('laravel-edge-tts::index', $response->name());
        $this->assertEmpty($response->getData()['voices']);
    }

    #[Test]
    public function it_returns_empty_voices_array_on_error()
    {
        // Arrange
        $this->tts->method('getVoices')
            ->willThrowException(new \Exception('Test exception'));

        // Act
        $response = $this->controller->__invoke($this->tts);

        // Assert
        $this->assertIsArray($response->getData()['voices']);
        $this->assertEmpty($response->getData()['voices']);
    }

    #[Test]
    public function it_passes_voices_to_view_correctly()
    {
        // Arrange
        $mockVoices = [['ShortName' => 'en-US-AriaNeural', 'LocalName' => 'Aria']];
        $this->tts->method('getVoices')->willReturn($mockVoices);

        // Act
        $response = $this->controller->__invoke($this->tts);
        $viewData = $response->getData();

        // Assert
        $this->assertArrayHasKey('voices', $viewData);
        $this->assertEquals($mockVoices, $viewData['voices']);
    }
}