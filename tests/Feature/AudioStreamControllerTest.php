<?php

namespace Bestmomo\LaravelEdgeTts\Tests\Feature;

use Bestmomo\LaravelEdgeTts\Contracts\TtsSynthesizer;
use Bestmomo\LaravelEdgeTts\Http\Controllers\AudioStreamController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Bestmomo\LaravelEdgeTts\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Cache;

class AudioStreamControllerTest extends TestCase
{
    private AudioStreamController $controller;
    private TtsSynthesizer $tts;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('edge_tts_available_voices'); 
        
        // Mock for TtsSynthesizer
        $this->tts = $this->createMock(TtsSynthesizer::class);

        $this->tts->method('getVoices')
        ->willReturn([
            ['ShortName' => 'en-US-AriaNeural'],
            ['ShortName' => 'fr-FR-DeniseNeural']
        ]);

        $this->app->instance(TtsSynthesizer::class, $this->tts);

        $this->controller = new AudioStreamController();
    }

    #[Test]
    public function it_validates_required_text_parameter()
    {
        $request = Request::create('/tts', 'GET', [
            // Missing 'text'
            'voice' => 'en-US-AriaNeural'
        ]);

        $this->expectException(ValidationException::class);
        $this->controller->__invoke($request, $this->tts);
    }

    #[Test]
    public function it_uses_default_voice_when_none_provided()
    {
        config(['edge-tts.default_voice' => 'fr-FR-DeniseNeural']);
        
        $request = Request::create('/tts', 'GET', [
            'text' => 'Bonjour le monde'
        ]);

        $this->tts->method('getVoices')
            ->willReturn([['ShortName' => 'fr-FR-DeniseNeural']]);

        $response = $this->controller->__invoke($request, $this->tts);
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('audio/mpeg', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function it_rejects_invalid_voice()
    {
        $request = Request::create('/tts', 'GET', [
            'text' => 'Hello',
            'voice' => 'invalid-voice'
        ]);

        $this->tts->method('getVoices')
            ->willReturn([['ShortName' => 'en-US-AriaNeural']]);

        $this->expectException(ValidationException::class);
        $this->controller->__invoke($request, $this->tts);
    }
}