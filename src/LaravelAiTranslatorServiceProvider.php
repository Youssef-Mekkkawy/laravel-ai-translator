<?php

namespace YoussefMekkkawy\LaravelAiTranslator;

use Illuminate\Support\ServiceProvider;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\CleanCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\InstallCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ListBackupsCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ListLockedCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\LockTranslationCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\RestoreCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ScanTranslationsCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\TranslateCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\UnlockTranslationCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\ValidateTranslationsCommand;
use YoussefMekkkawy\LaravelAiTranslator\Console\Commands\WorkQueueCommand;
use Illuminate\Support\Facades\View;
use YoussefMekkkawy\LaravelAiTranslator\View\DashboardLayoutComposer;   // at the top, with the other `use` lines

class LaravelAiTranslatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ai-translator.php',
            'ai-translator'
        );

        $this->app->instance('ai-translator.package_path', dirname(__DIR__));
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lang',
            'ai-translator'
        );

        $this->publishes([
            __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'ai-translator.php' => config_path('ai-translator.php'),
        ], 'ai-translator-config');
        $this->publishes([
            __DIR__ . '/../resources/images' => public_path('vendor/ai-translator'),
        ], 'ai-translator-assets');
        $this->publishes([
            __DIR__ . '/../resources/js/layout.js' => public_path('vendor/ai-translator/layout.js'),
        ], 'your-existing-tag');
        $this->publishes([
            __DIR__ . '/../resources/css/layout.css' => public_path('vendor/ai-translator/layout.css'),
        ], 'your-existing-tag');
        $this->publishes([
            __DIR__ . '/../resources/css/overview.css' => public_path('vendor/ai-translator/overview.css'),
        ], 'your-existing-tag');

        $this->loadRoutesFrom(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php'
        );

        $this->loadViewsFrom(
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views',
            'ai-translator'
        );
        View::composer('ai-translator::layout', DashboardLayoutComposer::class);

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
            CleanCommand::class,
            WorkQueueCommand::class, // background queue worker — spawned automatically
        ]);
    }
}
