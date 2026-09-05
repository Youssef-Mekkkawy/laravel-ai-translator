<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services;

use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\ChangeTracker;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\LanguageFileWriter;

class TranslationService
{
    protected ViewScanner $scanner;

    protected KeyExtractor $extractor;

    protected TranslatorManager $translatorManager;

    protected LanguageFileWriter $writer;

    protected ChangeTracker $changeTracker;

    protected LockManager $lockManager;

    protected array $config;

    public function __construct(
        ViewScanner $scanner,
        KeyExtractor $extractor,
        TranslatorManager $translatorManager,
        LanguageFileWriter $writer,
        ChangeTracker $changeTracker,
        LockManager $lockManager,
        array $config = []
    ) {
        $this->scanner = $scanner;
        $this->extractor = $extractor;
        $this->translatorManager = $translatorManager;
        $this->writer = $writer;
        $this->changeTracker = $changeTracker;
        $this->lockManager = $lockManager;
        $this->config = $config;
    }

    /**
     * Translate all keys to specified languages.
     */
    public function translateAll(array $targetLanguages, bool $force = false, bool $dryRun = false): array
    {
        $startTime = microtime(true);
        $sourceLang = $this->config['default_language'] ?? 'en';

        // Step 1: Scan views for translation keys
        $allKeys = $this->scanner->scanAll();

        // Step 2: Extract and organise keys
        $organized = $this->extractor->extractMultiple($allKeys);
        $sourceTranslations = $this->loadSourceTranslations($sourceLang);

        // Step 3: Build the source-value map
        $keysToTranslate = [];

        foreach ($organized as $data) {
            $key = $data['key'];
            $fullKey = $data['full_key'];

            // FIX: was generateValue() — correct method is generateDefaultValue()
            $sourceValue = $sourceTranslations[$fullKey]
                ?? $this->extractor->generateDefaultValue($fullKey);

            $keysToTranslate[$fullKey] = $sourceValue;
        }

        // Step 4: Translate to each target language
        $results = [
            'total_keys' => count($keysToTranslate),
            'languages_processed' => 0,
            'files_written' => [],
            'errors' => [],
            'skipped_unchanged' => 0,
            'locked_keys' => 0,
        ];

        foreach ($targetLanguages as $targetLang) {
            if ($targetLang === $sourceLang) {
                continue;
            }

            try {
                $langResult = $this->translateToLanguage(
                    $keysToTranslate,
                    $targetLang,
                    $sourceLang,
                    $force,
                    $dryRun
                );

                $results['languages_processed']++;
                $results['files_written'] = array_merge($results['files_written'], $langResult['files']);
                $results['skipped_unchanged'] += $langResult['skipped'] ?? 0;
                $results['locked_keys'] += $langResult['locked'] ?? 0;

            } catch (\Exception $e) {
                $results['errors'][$targetLang] = $e->getMessage();
            }
        }

        $results['duration'] = round(microtime(true) - $startTime, 2);

        return $results;
    }

    /**
     * Translate keys to a specific language.
     */
    protected function translateToLanguage(
        array $keysToTranslate,
        string $targetLang,
        string $sourceLang,
        bool $force = false,
        bool $dryRun = false
    ): array {
        $translated = [];
        $skipped = 0;
        $locked = 0;

        // Filter out locked keys
        foreach ($keysToTranslate as $fullKey => $value) {
            if ($this->lockManager->isLocked($targetLang, $fullKey)) {
                $locked++;

                continue;
            }
            $translated[$fullKey] = $value;
        }

        // Skip keys that already have translations (unless --force)
        if (! $force) {
            $toTranslate = [];

            foreach ($translated as $fullKey => $value) {
                $parts = explode('.', $fullKey, 2);
                $file = $parts[0];
                $existingPath = lang_path("{$targetLang}/{$file}.php");

                if (file_exists($existingPath)) {
                    $existing = include $existingPath;
                    $key = $parts[1] ?? $fullKey;

                    if (isset($existing[$key])) {
                        $skipped++;

                        continue;
                    }
                }

                $toTranslate[$fullKey] = $value;
            }

            $translated = $toTranslate;
        }

        // Translate remaining texts
        $translatedValues = [];

        if (! empty($translated)) {
            $translator = $this->translatorManager->translator();
            $translatedValues = $translator->translateBatch(
                array_values($translated),
                $targetLang,
                $sourceLang
            );
        }

        // Map translated values back to keys
        $finalTranslations = [];
        $index = 0;

        foreach ($translated as $fullKey => $originalValue) {
            $finalTranslations[$fullKey] = $translatedValues[$index] ?? $originalValue;
            $index++;
        }

        // Organise by file and write
        $organizedByFile = $this->writer->organizeByFile($finalTranslations);
        $writtenFiles = [];

        if (! $dryRun) {
            foreach ($organizedByFile as $file => $translations) {
                $writtenFiles[] = $this->writer->write($targetLang, $file, $translations, true);
            }
        }

        return [
            'translated' => count($finalTranslations),
            'skipped' => $skipped,
            'locked' => $locked,
            'files' => $writtenFiles,
        ];
    }

    /**
     * Load source translations from language files (flattened with dot notation).
     */
    protected function loadSourceTranslations(string $sourceLang): array
    {
        $langPath = base_path('lang').DIRECTORY_SEPARATOR.$sourceLang;

        if (! File::exists($langPath)) {
            return [];
        }

        $translations = [];

        foreach (File::files($langPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $fileName = $file->getBasename('.php');
            $fileTranslations = include $file->getPathname();

            if (! is_array($fileTranslations)) {
                continue;
            }

            $translations = array_merge(
                $translations,
                $this->flattenArray($fileTranslations, $fileName)
            );
        }

        return $translations;
    }

    /**
     * Flatten a nested array using dot-notation keys.
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Get target languages (all configured languages minus the source language).
     */
    protected function getTargetLanguages(): array
    {
        $languages = $this->config['languages'] ?? ['en'];
        $sourceLang = $this->config['default_language'] ?? 'en';

        return array_values(array_filter($languages, fn ($lang) => $lang !== $sourceLang));
    }

    /**
     * Estimate translation cost without actually translating.
     */
    public function estimateCost(array $targetLanguages, bool $force = false): array
    {
        $allKeys = $this->scanner->scanAll();
        $organized = $this->extractor->extractMultiple($allKeys);

        $texts = array_column($organized, 'key');

        $translator = $this->translatorManager->translator();

        return $translator->estimateCost($texts, $targetLanguages);
    }
}
