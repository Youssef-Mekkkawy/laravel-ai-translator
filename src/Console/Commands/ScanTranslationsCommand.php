<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;

class ScanTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lang:scan 
                            {--missing-only : Show only missing translations}
                            {--path=* : Additional paths to scan}';

    /**
     * The console command description.
     */
    protected $description = 'Scan Blade views for translation keys';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Scanning Blade views for translation keys...');
        $this->newLine();

        // Get scan paths from config and options
        $paths = config('ai-translator.scan_paths', [resource_path('views')]);

        if ($additionalPaths = $this->option('path')) {
            $paths = array_merge($paths, $additionalPaths);
        }

        // Initialize scanner
        $scanner = new ViewScanner($paths);
        $extractor = new KeyExtractor();

        // Show paths being scanned
        $this->components->info('📂 Scanning: ' . implode(', ', $paths));
        $this->newLine();

        // Scan all files
        $keys = $scanner->scanAll();

        if (empty($keys)) {
            $this->components->warn('⚠️  No translation keys found');
            return self::SUCCESS;
        }

        // Fixed: Use concatenation instead of interpolation
        $keyCount = count($keys);
        $this->components->info("✅ Found {$keyCount} unique translation keys");
        $this->newLine();

        // Get missing keys if requested
        $missingOnly = $this->option('missing-only');
        $defaultLang = config('ai-translator.default_language', 'en');

        // Organize keys by file
        $organized = $extractor->organizeKeysByFile($keys);

        // Display results
        $this->displayResults($organized, $extractor, $defaultLang, $missingOnly);

        // Show summary
        $this->displaySummary($keys, $extractor, $defaultLang);

        return self::SUCCESS;
    }

    /**
     * Display the translation keys in a table
     */
    protected function displayResults(
        array $organized,
        KeyExtractor $extractor,
        string $language,
        bool $missingOnly
    ): void {
        $this->components->info('📋 Translation Keys:');
        $this->newLine();

        $rows = [];

        foreach ($organized as $file => $fileKeys) {
            foreach ($fileKeys as $keyData) {
                $fullKey = $keyData['full_key'];
                $exists = $extractor->keyExists($fullKey, $language);

                // Skip if only showing missing and this exists
                if ($missingOnly && $exists) {
                    continue;
                }

                $value = $exists
                    ? $extractor->getKeyValue($fullKey, $language)
                    : $keyData['default_value'];

                $rows[] = [
                    $fullKey,
                    $file . '.php',
                    $exists ? '✅' : '❌',
                    $exists ? $value : '(missing)',
                ];
            }
        }

        if (empty($rows)) {
            if ($missingOnly) {
                $this->components->info('✨ All translation keys exist!');
            }
            return;
        }

        $this->table(
            ['Key', 'File', 'Exists', 'Value'],
            $rows
        );
    }

    /**
     * Display summary statistics
     */
    protected function displaySummary(
        array $keys,
        KeyExtractor $extractor,
        string $language
    ): void {
        $this->newLine();
        $this->components->info('📊 Summary:');

        $missing = $extractor->getMissingKeys($keys, $language);
        $existing = count($keys) - count($missing);
        $totalKeys = count($keys);
        $missingCount = count($missing);

        $this->line("   Total Keys: <fg=cyan>{$totalKeys}</>");
        $this->line("   Existing: <fg=green>{$existing}</>");
        $this->line("   Missing: <fg=yellow>{$missingCount}</>");

        if ($missingCount > 0) {
            $this->newLine();
            $this->components->info('💡 Tip: Missing keys will be auto-generated when you run lang:sync');
        }
    }
}
