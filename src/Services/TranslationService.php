<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services;

use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\ChangeTracker;
use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\JsonLanguageWriter;
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
    protected JsonLanguageWriter $jsonWriter;
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
        $this->scanner           = $scanner;
        $this->extractor         = $extractor;
        $this->translatorManager = $translatorManager;
        $this->writer            = $writer;
        $this->changeTracker     = $changeTracker;
        $this->lockManager       = $lockManager;
        $this->jsonWriter        = new JsonLanguageWriter();
        $this->config            = $config;
    }

    /**
     * Translate all keys to specified languages.
     */
    public function translateAll(array $targetLanguages, bool $force = false, bool $dryRun = false): array
    {
        $startTime  = microtime(true);
        $sourceLang = $this->config['default_language'] ?? 'en';

        // Step 1: Scan views for translation keys
        $allKeys = $this->scanner->scanAll();

        // Step 2: Extract and organise keys
        $organized        = $this->extractor->extractMultiple($allKeys);
        $sourceTranslations = $this->loadSourceTranslations($sourceLang);

        // Step 3: Build the source-value map (PHP files)
        $keysToTranslate = [];
        foreach ($organized as $data) {
            $fullKey = $data['full_key'];
            $keysToTranslate[$fullKey] = $sourceTranslations[$fullKey]
                ?? $this->extractor->generateDefaultValue($fullKey);
        }

        // Step 3b: Also load JSON source keys if lang/en.json exists
        $jsonSourceKeys = $this->jsonWriter->getSourceJsonKeys($sourceLang);

        // Step 4: Get keys whose source has changed (for hash-based skipping)
        $changedKeys = $force ? $keysToTranslate
            : $this->changeTracker->filterChanged($keysToTranslate, $sourceLang, false);

        // Step 5: Translate to each target language
        $results = [
            'total_keys'          => count($keysToTranslate),
            'languages_processed' => 0,
            'files_written'       => [],
            'errors'              => [],
            'skipped_unchanged'   => 0,
            'locked_keys'         => 0,
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
                    $dryRun,
                    $changedKeys,
                    $jsonSourceKeys
                );

                $results['languages_processed']++;
                $results['files_written']    = array_merge($results['files_written'], $langResult['files']);
                $results['skipped_unchanged'] += $langResult['skipped'] ?? 0;
                $results['total_keys']        = count($keysToTranslate);
                $results['locked_keys']      += $langResult['locked'] ?? 0;

            } catch (\Exception $e) {
                $results['errors'][$targetLang] = $e->getMessage();
            }
        }

        // Step 6: Save hashes after successful translation so next run skips unchanged keys
        if (!$dryRun && !empty($changedKeys)) {
            $this->changeTracker->updateHashes($keysToTranslate, $sourceLang);
        }

        $results['duration'] = round(microtime(true) - $startTime, 2);

        return $results;
    }

    /**
     * Translate keys to a specific language.
     */
    protected function translateToLanguage(
        array $allSourceKeys,
        string $targetLang,
        string $sourceLang,
        bool $force = false,
        bool $dryRun = false,
        array $changedKeys = [],
        array $jsonSourceKeys = []
    ): array {
        $translated = [];
        $skipped    = 0;
        $locked     = 0;

        foreach ($allSourceKeys as $fullKey => $value) {
            // Skip locked keys
            if ($this->lockManager->isLocked($targetLang, $fullKey)) {
                $locked++;
                continue;
            }

            if ($force) {
                $translated[$fullKey] = $value;
                continue;
            }

            // Check if target already has this key
            $isJsonKey = !(!str_contains($fullKey, ' ') && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)+$/', $fullKey));

            if ($isJsonKey) {
                // Check JSON file for this key
                $existingJson = $this->jsonWriter->readJson($targetLang);
                $targetHasKey = isset($existingJson[$fullKey]);
            } else {
                $parts        = explode('.', $fullKey, 2);
                $file         = $parts[0];
                $existingPath = lang_path("{$targetLang}/{$file}.php");
                $targetHasKey = false;

                if (file_exists($existingPath)) {
                    $existing     = @include $existingPath;
                    $key          = $parts[1] ?? $fullKey;
                    $targetHasKey = is_array($existing) && isset($existing[$key]);
                }
            }

            if (!$targetHasKey) {
                // Target missing this key → always translate
                $translated[$fullKey] = $value;
            } elseif (isset($changedKeys[$fullKey])) {
                // Source changed → re-translate even if target has it
                $translated[$fullKey] = $value;
            } else {
                // Target has it and source unchanged → skip
                $skipped++;
            }
        }

        // Translate remaining texts via AI
        $translatedValues = [];
        if (!empty($translated)) {
            $translator       = $this->translatorManager->translator();
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

        // Separate PHP keys (dot-notation) from JSON keys (spaces or no dot)
        $phpKeys  = [];
        $jsonKeys = [];

        foreach ($finalTranslations as $key => $value) {
            if (!(!str_contains($key, ' ') && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)+$/', $key))) {
                // JSON key — full English string like "Forgot your password?"
                $jsonKeys[$key] = $value;
            } else {
                // PHP key — dot-notation like "auth.login"
                $phpKeys[$key] = $value;
            }
        }

        // Write PHP files
        $organizedByFile = $this->writer->organizeByFile($phpKeys);
        $writtenFiles    = [];

        if (!$dryRun) {
            foreach ($organizedByFile as $file => $translations) {
                $writtenFiles[] = $this->writer->write($targetLang, $file, $translations, true);
            }

            // Write JSON file for JSON-style keys
            if (!empty($jsonKeys)) {
                $writtenFiles[] = $this->jsonWriter->writeJson($targetLang, $jsonKeys);
            }
        }

        // Handle JSON source file (lang/en.json) — only keys NOT already in scanned keys
        if (!empty($jsonSourceKeys) && !$dryRun) {
            $jsonToTranslate = [];
            $existingJson    = $this->jsonWriter->readJson($targetLang);

            foreach ($jsonSourceKeys as $key => $value) {
                // Skip if already handled as a scanned key
                if (array_key_exists($key, $allSourceKeys)) {
                    continue;
                }
                if ($this->lockManager->isLocked($targetLang, $key)) {
                    continue;
                }
                if (isset($existingJson[$key]) && !isset($changedKeys[$key]) && !$force) {
                    $skipped++;
                    continue;
                }
                $jsonToTranslate[$key] = $value;
            }

            if (!empty($jsonToTranslate)) {
                $translator       = $this->translatorManager->translator();
                $translatedValues = $translator->translateBatch(
                    array_values($jsonToTranslate),
                    $targetLang,
                    $sourceLang
                );

                $translatedJson = [];
                $index = 0;
                foreach ($jsonToTranslate as $key => $original) {
                    $translatedJson[$key] = $translatedValues[$index] ?? $original;
                    $index++;
                }

                $writtenFiles[] = $this->jsonWriter->writeJson($targetLang, $translatedJson);
            }
        }

        return [
            'translated' => count($finalTranslations),
            'skipped'    => $skipped,
            'locked'     => $locked,
            'files'      => $writtenFiles,
        ];
    }

    /**
     * Load source translations from language files (flattened with dot notation).
     * Also reads JSON files if they exist.
     */
    protected function loadSourceTranslations(string $sourceLang): array
    {
        $langPath = base_path('lang') . DIRECTORY_SEPARATOR . $sourceLang;

        $translations = [];

        // Load PHP files
        if (File::exists($langPath)) {
            foreach (File::files($langPath) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $fileName         = $file->getBasename('.php');
                $fileTranslations = include $file->getPathname();

                if (!is_array($fileTranslations)) {
                    continue;
                }

                $translations = array_merge(
                    $translations,
                    $this->flattenArray($fileTranslations, $fileName)
                );
            }
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
     * Estimate translation cost without actually translating.
     */
    public function estimateCost(array $targetLanguages, bool $force = false): array
    {
        $allKeys   = $this->scanner->scanAll();
        $organized = $this->extractor->extractMultiple($allKeys);
        $texts     = array_column($organized, 'key');
        $translator = $this->translatorManager->translator();

        return $translator->estimateCost($texts, $targetLanguages);
    }
}


