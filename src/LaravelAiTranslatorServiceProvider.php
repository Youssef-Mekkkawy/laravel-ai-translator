<?php

namespace YoussefMekkkawy\LaravelAiTranslator;

use Illuminate\Support\ServiceProvider;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ScanTranslationsCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\TranslateCommand;

class LaravelAiTranslatorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__.'/config/laravel-ai-translator.php',
            'laravel-ai-translator'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__.'/config/laravel-ai-translator.php' => config_path('laravel-ai-translator.php'),
        ], 'laravel-ai-translator-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ScanTranslationsCommand::class,
                TranslateCommand::class,
            ]);
        }
    }
}