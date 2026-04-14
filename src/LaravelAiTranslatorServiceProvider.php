<?php

namespace YoussefMekkkawy\LaravelAiTranslator;

use Illuminate\Support\ServiceProvider;

class LaravelAiTranslatorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__.'/config/ai-translator.php',
            'ai-translator'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/config/ai-translator.php' => config_path('ai-translator.php'),
        ], 'ai-translator-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Commands will be registered here as we build them
            ]);
        }
    }
}
