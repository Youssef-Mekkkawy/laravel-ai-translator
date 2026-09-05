<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\Backup\BackupService;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\ChangeTracker;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\HashGenerator;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\MetadataManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\TranslationService;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\LanguageFileWriter;

class TestTranslationResultsCommand extends Command
{
    protected $signature = 'lang:test-results';

    protected $description = 'Test what translateAll() returns';

    public function handle()
    {
        $this->info("🧪 Testing Translation Results Structure\n");

        // Initialize service
        $config = config('laravel-ai-translator');

        $scanner = new ViewScanner($config);
        $extractor = new KeyExtractor;
        $translatorManager = new TranslatorManager($config);

        $backupService = new BackupService($config['backup'] ?? []);
        $writer = new LanguageFileWriter($backupService, $config);

        $hashGenerator = new HashGenerator;
        $metadataPath = $config['storage']['metadata_file'] ?? lang_path('.translations-meta.json');
        $metadataManager = new MetadataManager($metadataPath);
        $changeTracker = new ChangeTracker($hashGenerator, $metadataManager);

        $lockStorage = new LockStorage;
        $lockManager = new LockManager($lockStorage);

        $translationService = new TranslationService(
            $scanner,
            $extractor,
            $translatorManager,
            $writer,
            $changeTracker,
            $lockManager,
            $config
        );

        // Run translation
        $this->info('Running translation...');
        $results = $translationService->translateAll(['ar'], true, false);

        // Display results structure
        $this->info("\n📊 Results Structure:");
        $this->line(json_encode($results, JSON_PRETTY_PRINT));

        // Check files
        $this->info("\n📁 Checking files:");
        $arPath = lang_path('ar/auth.php');
        $this->line("Path: $arPath");
        $this->line('Exists: '.(file_exists($arPath) ? '✅ YES' : '❌ NO'));

        if (file_exists($arPath)) {
            $content = file_get_contents($arPath);
            $this->line("\nFile content:");
            $this->line($content);
        }

        // List all ar files
        $arDir = lang_path('ar');
        if (is_dir($arDir)) {
            $files = scandir($arDir);
            $this->info("\nAll AR files:");
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $this->line("  - $file");
                }
            }
        }
    }
}
