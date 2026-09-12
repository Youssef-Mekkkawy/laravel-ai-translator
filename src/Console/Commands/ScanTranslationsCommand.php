<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;

class ScanTranslationsCommand extends Command
{
    protected $signature = 'lang:scan
                            {--missing-only : Show only missing translations}
                            {--path=* : Additional paths to scan}';

    protected $description = 'Scan Blade views for translation keys';

    public function handle(): int
    {
        $this->info('🔍 Scanning Blade views for translation keys...');
        $this->newLine();

        $paths = config('ai-translator.scan_paths', [resource_path('views')]);

        if ($additionalPaths = $this->option('path')) {
            $paths = array_merge($paths, $additionalPaths);
        }

        $paths = array_values(
            array_filter($paths, fn($path) => File::isDirectory($path))
        );

        if (empty($paths)) {
            $this->components->warn(
                '⚠️  No valid scan paths found. Check your config or use --path.'
            );

            return self::FAILURE;
        }

        $scanner = new ViewScanner($paths);
        $extractor = new KeyExtractor;

        $this->components->info('📂 Scanning: ' . implode(', ', $paths));
        $this->newLine();

        $keys = $scanner->scanAll();

        if (empty($keys)) {
            $this->components->warn('⚠️  No translation keys found');

            return self::SUCCESS;
        }

        $keyCount = count($keys);

        $this->components->info(
            "✅ Found {$keyCount} unique translation keys."
        );

        $this->newLine();

        $defaultLang = config('ai-translator.default_language', 'en');
        $langBasePath = base_path(
            'lang' . DIRECTORY_SEPARATOR . $defaultLang
        );

        $missingOnly = $this->option('missing-only');

        $rows = [];
        $existingCount = 0;
        $missingCount = 0;

        foreach ($keys as $key) {
            $parsed = $extractor->extractFromKey($key);

            $defaultValue = $parsed['default_value'];

            $exists = false;
            $value = "(missing) → {$defaultValue}";

            /*
             * KeyExtractor returns:
             *
             * Plain string:
             * __('Dashboard')
             *     file = auto
             *
             * PHP translation key:
             * __('auth.login')
             *     file = auth
             *
             * We use that information instead of checking for "."
             * ourselves because "auto" is the package's indicator
             * for JSON-style translations.
             */
            $isPhpKey = ($parsed['file'] !== 'auto');

            if ($isPhpKey) {
                /*
                 * Example:
                 * __('auth.login')
                 *
                 * -> lang/en/auth.php
                 * -> ['login' => 'Login']
                 */
                $file = $parsed['file'];
                $subKey = $parsed['key'];

                $langFile = $langBasePath
                    . DIRECTORY_SEPARATOR
                    . $file
                    . '.php';

                $displayFile = $file . '.php';

                if (File::exists($langFile)) {
                    $translations = require $langFile;

                    if (
                        is_array($translations)
                        && array_key_exists($subKey, $translations)
                    ) {
                        $exists = true;
                        $value = $translations[$subKey];
                        $existingCount++;
                    } else {
                        $missingCount++;
                    }
                } else {
                    $missingCount++;
                }
            } else {
                /*
                 * Example:
                 * __('Dashboard')
                 *
                 * -> lang/en.json
                 */
                $displayFile = $defaultLang . '.json';

                $jsonFile = lang_path($defaultLang . '.json');

                if (File::exists($jsonFile)) {
                    $translations = json_decode(
                        File::get($jsonFile),
                        true
                    );

                    if (
                        is_array($translations)
                        && array_key_exists($key, $translations)
                    ) {
                        $exists = true;
                        $value = $translations[$key];
                        $existingCount++;
                    } else {
                        $missingCount++;
                    }
                } else {
                    $missingCount++;
                }
            }

            if ($missingOnly && $exists) {
                continue;
            }

            $rows[] = [
                $key,
                $displayFile,
                $exists ? '✅' : '❌',
                $value,
            ];
        }

        if (! empty($rows)) {
            $this->components->info('📋 Translation Keys:');
            $this->newLine();

            $this->table(
                ['Key', 'File', 'Exists', 'Value'],
                $rows
            );
        } elseif ($missingOnly) {
            $this->components->info('✨ All translation keys exist!');
        }

        $this->newLine();

        $this->components->info('📊 Summary:');

        $this->line(
            "   Total Keys: <fg=cyan>{$keyCount}</>"
        );

        $this->line(
            "   Existing: <fg=green>{$existingCount}</>"
        );

        $this->line(
            "   Missing: <fg=yellow>{$missingCount}</>"
        );

        if ($missingCount > 0) {
            $this->newLine();

            $this->components->info(
                '💡 Tip: Missing keys will be auto-generated when you run lang:sync'
            );
        }

        return self::SUCCESS;
    }
}
