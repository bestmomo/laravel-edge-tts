<?php

namespace Bestmomo\LaravelEdgeTts\Tests\Feature;

use Afaya\EdgeTTS\Service\EdgeTTS;
use Bestmomo\LaravelEdgeTts\Contracts\TtsSynthesizer;
use Bestmomo\LaravelEdgeTts\EdgeTtsLaravelServiceProvider;
use Bestmomo\LaravelEdgeTts\Services\EdgeTtsAdapter;
use Bestmomo\LaravelEdgeTts\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EdgeTtsLaravelServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [EdgeTtsLaravelServiceProvider::class];
    }

    #[Test]
    public function it_registers_edge_tts_service()
    {
        $this->assertTrue($this->app->bound(EdgeTTS::class));
        $this->assertInstanceOf(EdgeTTS::class, $this->app->make(EdgeTTS::class));
    }

    #[Test]
    public function it_registers_tts_synthesizer_contract()
    {
        $this->assertTrue($this->app->bound(TtsSynthesizer::class));
        $this->assertInstanceOf(
            EdgeTtsAdapter::class,
            $this->app->make(TtsSynthesizer::class)
        );
    }

    #[Test]
    public function it_aliases_tts_synthesizer()
    {
        $this->assertTrue($this->app->bound('laravel-edge-tts'));
        $this->assertInstanceOf(
            TtsSynthesizer::class,
            $this->app->make('laravel-edge-tts')
        );
    }
}