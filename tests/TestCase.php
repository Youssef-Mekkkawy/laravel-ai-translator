<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use YoussefMekkkawy\LaravelAiTranslator\LaravelAiTranslatorServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelAiTranslatorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Setup package config for testing
        $app['config']->set('ai-translator.driver', 'deepl');
        $app['config']->set('ai-translator.languages', ['en', 'ar', 'fr']);
        $app['config']->set('ai-translator.default_language', 'en');
    }

    protected function getTempDirectory(): string
    {
        return __DIR__.DIRECTORY_SEPARATOR.'temp';
    }

    protected function createTestLanguageFile(string $lang, string $file, array $content): void
    {
        $path = $this->getTempDirectory().DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$lang;

        if (! file_exists($path)) {
            mkdir($path, 0755, true);
        }

        file_put_contents(
            $path.DIRECTORY_SEPARATOR.$file.'.php',
            '<?php return '.var_export($content, true).';'
        );
    }

    protected function tearDown(): void
    {
        $tempDir = $this->getTempDirectory();

        if (file_exists($tempDir)) {
            $this->deleteDirectory($tempDir);
        }

        parent::tearDown();
    }

    protected function deleteDirectory(string $dir): void
    {
        if (! file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
