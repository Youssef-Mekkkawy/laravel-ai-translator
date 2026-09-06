<?php

namespace YoussefMekkkawy\LaravelAiTranslator;

use Illuminate\Support\ServiceProvider;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ListBackupsCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ListLockedCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\LockTranslationCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\RestoreCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ScanTranslationsCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\TranslateCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\UnlockTranslationCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ValidateTranslationsCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\InstallCommand;
class LaravelAiTranslatorServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ai-translator.php',
            'ai-translator'
        );
        $this->app->instance('ai-translator.package_path', dirname(__DIR__));
    }

    /**
     * Bootstrap any application services.
     */
    // public function boot(): void
    // {
    //     // Publish configuration file
    //     $this->publishes([
    //         __DIR__.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'ai-translator.php' => config_path('ai-translator.php'),
    //     ], 'ai-translator-config');

    //     // Register all commands
    //     if ($this->app->runningInConsole()) {
    //         $this->commands([
    //             ScanTranslationsCommand::class,
    //             TranslateCommand::class,
    //             LockTranslationCommand::class,
    //             UnlockTranslationCommand::class,
    //             ListLockedCommand::class,
    //             ValidateTranslationsCommand::class,
    //             RestoreCommand::class,
    //             ListBackupsCommand::class,
    //         ]);
    //     }
    // }

    public function boot(): void
    {
        // lang 
        $this->loadTranslationsFrom(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang', 'ai-translator');
        // Publish configuration file
        $this->publishes([
            __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ai-translator.php' => config_path('ai-translator.php'),
        ], 'ai-translator-config');
    
        // ── ADD THESE 2 LINES ──
        $this->loadRoutesFrom(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php');
        $this->loadViewsFrom(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views', 'ai-translator');
        // ──────────────────────
        // Register all commands
        $this->commands([
            ScanTranslationsCommand::class,
            TranslateCommand::class,
            LockTranslationCommand::class,
            UnlockTranslationCommand::class,
            ListLockedCommand::class,
            ValidateTranslationsCommand::class,
            RestoreCommand::class,
            ListBackupsCommand::class,
            InstallCommand::class,
        ]);
    }
}
