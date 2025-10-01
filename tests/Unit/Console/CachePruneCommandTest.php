<?php

namespace Bestmomo\LaravelEdgeTts\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\Test;
use Bestmomo\LaravelEdgeTts\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class CachePruneCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Configure the test disk
        config(['filesystems.disks.test-disk' => [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disk1'),
        ]]);
    }

    protected function tearDown(): void
    {
        // Clean up
        Storage::disk('test-disk')->deleteDirectory('tts');
        parent::tearDown();
    }

    #[Test]
    public function it_has_correct_signature_and_description()
    {
        $this->artisan('edge-tts:cache-prune --help')
            ->expectsOutputToContain('Prune old Edge TTS cache files')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_uses_custom_disk_from_config()
    {
        config(['edge-tts.cache.disk' => 'test-disk']);
        
        // Create test file
        Storage::disk('test-disk')->put('tts/test.txt', 'test');
        
        $this->artisan('edge-tts:cache-prune')
            ->expectsOutput("Pruning Edge TTS cache on disk 'test-disk'...")
            ->assertExitCode(0);
    }

    #[Test]
    public function it_deletes_old_files()
    {
        config(['edge-tts.cache.disk' => 'test-disk']);
        
        $disk = Storage::disk('test-disk');
        
        // Create old file
        $disk->put('tts/old.mp3', 'old');
        touch($disk->path('tts/old.mp3'), now()->subDays(100)->timestamp);
        
        // Create new file
        $disk->put('tts/new.mp3', 'new');
        touch($disk->path('tts/new.mp3'), now()->subDays(10)->timestamp);

        // Check file exists before delete
        $this->assertTrue($disk->exists('tts/old.mp3'));
        $this->assertTrue($disk->exists('tts/new.mp3'));

        $this->artisan('edge-tts:cache-prune', ['--days' => 30])
            ->assertExitCode(0);
            
        // Check only old file is deleted
        $this->assertFalse($disk->exists('tts/old.mp3'), 'Old file should be deleted');
        $this->assertTrue($disk->exists('tts/new.mp3'), 'New file should still exist');
    }

    #[Test]
    public function it_handles_empty_directory()
    {
        config(['edge-tts.cache.disk' => 'test-disk']);
        
        $this->artisan('edge-tts:cache-prune')
            ->expectsOutput("No cache files found in 'tts/'.")
            ->assertExitCode(0);
    }
}