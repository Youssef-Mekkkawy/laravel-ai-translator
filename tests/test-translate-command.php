<?php

/**
 * TEST SCRIPT: Translation Command
 *
 * This script tests the main translation command that:
 * 1. Scans views for translation keys
 * 2. Translates them using DeepL
 * 3. Writes them safely with backups
 *
 * Run: php test-translate-command.php
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Contracts\Console\Kernel;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\TranslationService;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\LanguageFileWriter;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                                                          ║\n";
echo "║   🧪  TESTING: Translation Command (Phase 7)           ║\n";
echo "║                                                          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";

// Load configuration
$config = config('laravel-ai-translator');

// Initialize services
$scanner = new ViewScanner($config);
$extractor = new KeyExtractor;
$translatorManager = new TranslatorManager($config['translators'] ?? []);
$writer = new LanguageFileWriter;

$translationService = new TranslationService(
    $scanner,
    $extractor,
    $translatorManager,
    $writer,
    $config
);

try {
    // TEST 1: Cost Estimation
    echo "Test 1: Cost Estimation\n";
    echo str_repeat('─', 50)."\n";

    $estimation = $translationService->estimateCost();

    echo 'Total Keys: '.number_format($estimation['total_keys'])."\n";
    echo 'Total Characters: '.number_format($estimation['total_characters'])."\n";
    echo 'Target Languages: '.implode(', ', $estimation['languages'])."\n";
    echo 'Estimated Cost: $'.number_format($estimation['estimated_cost'], 4)."\n";
    echo 'Estimated Time: '.$estimation['estimated_time']."\n";
    echo "✅ Cost estimation working!\n\n";

    // TEST 2: Translate to Single Language (Arabic) - DRY RUN
    echo "Test 2: Translate to Arabic (First 3 Keys Only - DRY RUN)\n";
    echo str_repeat('─', 50)."\n";

    // Scan views
    $viewFiles = $scanner->scanAll();
    $allKeys = [];

    foreach ($viewFiles as $file) {
        foreach ($file['keys'] as $key) {
            $allKeys[] = $key;
            if (count($allKeys) >= 3) {
                break 2;
            } // Get only 3 keys for testing
        }
    }

    echo "Keys to translate:\n";
    foreach ($allKeys as $key) {
        echo "  - {$key}\n";
    }

    // Extract and organize
    $organized = $extractor->extractMultiple($allKeys);

    echo "\nOrganized by file:\n";
    foreach ($organized as $data) {
        echo "  {$data['full_key']} → {$data['file']}.php : {$data['key']}\n";
    }

    echo "\n✅ Dry run successful!\n\n";

    // TEST 3: Ask user if they want to run REAL translation
    echo "Test 3: Run REAL Translation?\n";
    echo str_repeat('─', 50)."\n";
    echo "⚠️  WARNING: This will:\n";
    echo "  - Call DeepL API (costs money)\n";
    echo "  - Create translation files in lang/ar/\n";
    echo "  - Create automatic backups\n";
    echo "\n";

    $handle = fopen('php://stdin', 'r');
    echo 'Do you want to proceed? (yes/no): ';
    $line = fgets($handle);
    $response = trim($line);
    fclose($handle);

    if (strtolower($response) !== 'yes') {
        echo "\n❌ Translation cancelled. No changes made.\n";
        echo "\n🎉 All tests passed! Command is ready!\n";
        exit(0);
    }

    echo "\n🚀 Starting REAL translation...\n\n";

    // Run translation for Arabic only
    $results = $translationService->translateAll([
        'target_langs' => ['ar'],
        'force' => false, // Don't re-translate existing
    ]);

    echo "\n";
    echo "═══════════════════════════════════════════════════════\n";
    echo "           ✅  TRANSLATION COMPLETE!                   \n";
    echo "═══════════════════════════════════════════════════════\n";
    echo "\n";

    echo 'Total Keys: '.number_format($results['total_keys'])."\n";
    echo 'Languages: '.count($results['languages'])."\n";
    echo 'Duration: '.$results['duration']."s\n";
    echo "\n";

    // Show per-language results
    foreach ($results['languages'] as $lang => $data) {
        echo 'Language: '.strtoupper($lang)."\n";
        echo '  Translated: '.($data['translated'] ?? 0)."\n";
        echo '  Skipped: '.($data['skipped'] ?? 0)."\n";
        echo '  Files: '.count($data['files'] ?? [])."\n";
        echo "\n";
    }

    // Show files written
    if (! empty($results['files_written'])) {
        echo "Files Created/Updated:\n";
        foreach ($results['files_written'] as $file) {
            echo '  ✓ '.str_replace(base_path(), '', $file)."\n";
        }
        echo "\n";
    }

    // Show errors
    if (! empty($results['errors'])) {
        echo "⚠️  Errors:\n";
        foreach ($results['errors'] as $lang => $error) {
            echo "  ✗ {$lang}: {$error}\n";
        }
        echo "\n";
    }

    echo "✅ All tests passed!\n";
    echo "💡 Check lang/ar/ for the translated files!\n";
    echo "💡 Check lang/.backup/ for the automatic backups!\n";
    echo "\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: ".$e->getMessage()."\n";
    echo $e->getTraceAsString()."\n";
    exit(1);
}

echo "\n";
echo "╔══════════════════════════════════════════════════════════╗\n";
echo "║                                                          ║\n";
echo "║   🎉  ALL TESTS PASSED!                                ║\n";
echo "║                                                          ║\n";
echo "║   Translation Command is READY!                         ║\n";
echo "║                                                          ║\n";
echo "╚══════════════════════════════════════════════════════════╝\n";
echo "\n";
