<?php

namespace Bestmomo\LaravelEdgeTts;

use Illuminate\Support\ServiceProvider;
use Afaya\EdgeTTS\Service\EdgeTTS;
use Illuminate\Support\Facades\Blade;
use Bestmomo\LaravelEdgeTts\Services\EdgeTtsAdapter;
use Bestmomo\LaravelEdgeTts\Contracts\TtsSynthesizer;
use Bestmomo\LaravelEdgeTts\Console\CachePruneCommand;

class EdgeTtsLaravelServiceProvider extends ServiceProvider
{
    /**
     * Register the services of the package in the IOC container.
     */
    public function register(): void
    {
        // 1. Register the concrete library (the internal dependency of the adapter)
        $this->app->singleton(EdgeTTS::class, function ($app) {
            return new EdgeTTS();
        });

        // 2. Register the adapter: Bind the Contract to the Implementation
        $this->app->singleton(TtsSynthesizer::class, function ($app) {
            $edgeTts = $app->make(EdgeTTS::class);
            $logCalls = config('edge-tts.enable_call_logging', false);

            return new EdgeTtsAdapter($edgeTts, $logCalls);
        });

        // 3. Update the alias for the Facade
        // The alias must now point to the Contract
        $this->app->alias(TtsSynthesizer::class, 'laravel-edge-tts');
    }

    /**
     * Start the services after all providers have been registered.
     */
    public function boot(): void
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__.'/../config/edge-tts.php' => config_path('edge-tts.php'),
        ], 'edge-tts-config');

        // Load the configuration file (in case it is not published)
        $this->mergeConfigFrom(
            __DIR__.'/../config/edge-tts.php', 'edge-tts'
        );

        // Add view registration
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-edge-tts');

        // Load the ESSENTIAL routes (STREAMING) ALWAYS
        // The 'api' routes are loaded with a prefix and optional middlewares (according to Laravel convention)
        // Here, we load them simply
        $this->loadRoutesFrom(__DIR__.'/../routes/stream.php');


        // Load the DEMO routes ONLY in development/staging environments
        if ($this->app->environment('local', 'staging', 'testing')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/demo.php');
        }

        // Register the @edge_tts directive
        Blade::directive('edge_tts', function ($expression) {
            // Clean the parentheses of the Blade expression (e.g., @edge_tts('text', 'voice'))
            // Ensure the expression is ready to be passed to route()
            return "<?php echo '<audio controls autoplay><source src=\"' . route('edge-tts.stream', {$expression}) . '\" type=\"audio/mpeg\"></audio>'; ?>";
        });

        // Register the console command
        if ($this->app->runningInConsole()) {
            $this->commands([
                CachePruneCommand::class,
            ]);
        }
    }
}
