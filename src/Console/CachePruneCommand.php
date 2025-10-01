<?php

namespace Bestmomo\LaravelEdgeTts\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CachePruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     * @var string
     */
    protected $signature = 'edge-tts:cache-prune 
                            {--days=90 : The number of days after which cache files should be deleted.}';

    /**
     * The console command description.
     * @var string
     */
    protected $description = 'Prune old Edge TTS cache files from storage.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $diskName = config('edge-tts.cache.disk', 'local');
        $storage = Storage::disk($diskName);
        $days = (int) $this->option('days');
        
        // Limit of days to delete files
        $cutoffDate = Carbon::now()->subDays($days);
        $deletedCount = 0;

        $this->info("Pruning Edge TTS cache on disk '{$diskName}'...");
        $this->comment("Deleting files older than {$days} days (cutoff date: {$cutoffDate->format('Y-m-d')}).");

        // Get all files in the tts/ directory
        $files = $storage->allFiles('tts');
        
        if (empty($files)) {
            $this->info("No cache files found in 'tts/'.");
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $filePath) {
            // Only .mp3 files are concerned
            if (!str_ends_with($filePath, '.mp3')) {
                $bar->advance();
                continue;
            }
            
            try {
                // Get the last modified date of the file
                $lastModified = Carbon::createFromTimestamp($storage->lastModified($filePath));
                
                if ($lastModified->lt($cutoffDate)) {
                    $storage->delete($filePath);
                    $deletedCount++;
                }
            } catch (\Exception $e) {
                $this->warn("Could not process file '{$filePath}': " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Cache pruning complete. Total files deleted: {$deletedCount}.");
        
        return Command::SUCCESS;
    }
}