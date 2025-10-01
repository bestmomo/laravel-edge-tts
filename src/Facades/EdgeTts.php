<?php

namespace Bestmomo\LaravelEdgeTts\Facades;

use Illuminate\Support\Facades\Facade;

class EdgeTts extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        // Must correspond to the alias defined in register() of the ServiceProvider
        return 'laravel-edge-tts';
    }
}