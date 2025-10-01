<?php

namespace Bestmomo\LaravelEdgeTts\Tests\Feature;

use Bestmomo\LaravelEdgeTts\Contracts\TtsSynthesizer;
use Bestmomo\LaravelEdgeTts\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Mockery;

class EdgeTtsFacadeTest extends TestCase
{
    private $ttsMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock for TtsSynthesizer
        $this->ttsMock = Mockery::mock(TtsSynthesizer::class);
        
        // Register the mock in the container
        $this->app->instance(TtsSynthesizer::class, $this->ttsMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_delegates_synthesize_to_service()
    {
        $text = 'Hello world';
        $voice = 'en-US-AriaNeural';
        $options = ['rate' => '10%'];
        $expected = 'audio_data';

        $this->ttsMock->shouldReceive('synthesize')
            ->once()
            ->with($text, $voice, $options)
            ->andReturn($expected);

        $result = $this->ttsMock->synthesize($text, $voice, $options);
        
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_delegates_synthesize_stream_to_service()
    {
        $text = 'Hello world';
        $voice = 'en-US-AriaNeural';
        $options = ['rate' => '10%'];
        $callback = function() {};
        $called = false;
    
        $this->ttsMock->shouldReceive('synthesizeStream')
            ->once()
            ->with($text, $voice, $options, Mockery::on(function($arg) use (&$called) {
                // Verify that the callback is a function
                $called = is_callable($arg);
                return true;
            }));
    
        $this->ttsMock->synthesizeStream($text, $voice, $options, $callback);
        
        // Verify that the callback was properly passed to the service
        $this->assertTrue($called, 'The callback was not properly passed to the service');
    }

    #[Test]
    public function it_delegates_get_voices_to_service()
    {
        $expected = [['ShortName' => 'en-US-AriaNeural']];
        
        $this->ttsMock->shouldReceive('getVoices')
            ->once()
            ->andReturn($expected);

        $result = $this->ttsMock->getVoices();
        
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_delegates_to_base64_to_service()
    {
        $text = 'Hello';
        $voice = 'en-US-AriaNeural';
        $options = [];
        $expected = 'base64_encoded_audio';

        $this->ttsMock->shouldReceive('toBase64')
            ->once()
            ->with($text, $voice, $options)
            ->andReturn($expected);

        $result = $this->ttsMock->toBase64($text, $voice, $options);
        
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_delegates_to_file_to_service()
    {
        $text = 'Hello';
        $voice = 'en-US-AriaNeural';
        $path = 'test.mp3';
        $options = [];
        $expected = '/path/to/file.mp3';

        $this->ttsMock->shouldReceive('toFile')
            ->once()
            ->with($text, $voice, $path, $options)
            ->andReturn($expected);

        $result = $this->ttsMock->toFile($text, $voice, $path, $options);
        
        $this->assertEquals($expected, $result);
    }

    #[Test]
    public function it_delegates_to_raw_to_service()
    {
        $text = 'Hello';
        $voice = 'en-US-AriaNeural';
        $options = [];
        $expected = 'raw_audio_data';

        $this->ttsMock->shouldReceive('toRaw')
            ->once()
            ->with($text, $voice, $options)
            ->andReturn($expected);

        $result = $this->ttsMock->toRaw($text, $voice, $options);
        
        $this->assertEquals($expected, $result);
    }
}