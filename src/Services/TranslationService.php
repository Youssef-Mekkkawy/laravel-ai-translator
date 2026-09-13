<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
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

    public function translateAll(array $targetLanguages, bool $force = false, bool $dryRun = false): array
    {
        $startTime  = microtime(true);
        $sourceLang = $this->config['default_language'] ?? 'en';

        // Step 1: Scan views
        $runtime      = new RuntimeConfig();
        $runtimePaths = $runtime->get('scan_paths', []);
        $runtimeExts  = $runtime->get('scan_extensions', ['blade.php']);
        $outputFormat = $runtime->get('output_format', env('AUTO_TRANSLATE_OUTPUT', 'auto'));

        if (!empty($runtimePaths)) {
            $scanner = new ViewScanner(
                array_filter((array) $runtimePaths, fn ($p) => is_dir($p)),
                null,
                $runtimeExts
            );
        }

        $activeScanner = !empty($runtimePaths) ? $scanner : $this->scanner;
        $allKeys       = $activeScanner->scanAll();

        // Step 2: Extract and organise keys
        $organized          = $this->extractor->extractMultiple($allKeys);
        $sourceTranslations = $this->loadSourceTranslations($sourceLang);
        $jsonSourceKeys     = $this->jsonWriter->getSourceJsonKeys($sourceLang);

        // Step 3: Auto-create missing source files
        // Creates lang/en.json for JSON-style keys and lang/en/{file}.php for PHP-style keys
        // when those source entries don't exist yet. Uses merge — never overwrites.
        $sourceCreated = $this->ensureSourceFilesExist(
            $organized,
            $sourceLang,
            $sourceTranslations,
            $jsonSourceKeys
        );

        // Reload after creation so source values are correct
        if ($sourceCreated) {
            $sourceTranslations = $this->loadSourceTranslations($sourceLang);
            $jsonSourceKeys     = $this->jsonWriter->getSourceJsonKeys($sourceLang);
        }

        // Step 4: Build source-value map
        $keysToTranslate = [];
        foreach ($organized as $data) {
            $fullKey                   = $data['full_key'];
            $keysToTranslate[$fullKey] = $sourceTranslations[$fullKey]
                ?? $this->extractor->generateDefaultValue($fullKey);
        }

        // Step 5: Hash-based change filtering
        $changedKeys = $force
            ? $keysToTranslate
            : $this->changeTracker->filterChanged($keysToTranslate, $sourceLang, false);

        // Step 6: Translate to each target language
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
                    $jsonSourceKeys,
                    $outputFormat
                );

                $results['languages_processed']++;
                $results['files_written']     = array_merge($results['files_written'], $langResult['files']);
                $results['skipped_unchanged'] += $langResult['skipped'] ?? 0;
                $results['total_keys']         = count($keysToTranslate);
                $results['locked_keys']       += $langResult['locked']  ?? 0;
            } catch (\Exception $e) {
                $results['errors'][$targetLang] = $e->getMessage();
            }
        }

        // Step 7: Save hashes
        if (!$dryRun && !empty($changedKeys)) {
            $this->changeTracker->updateHashes($keysToTranslate, $sourceLang);
        }

        $results['duration'] = round(microtime(true) - $startTime, 2);

        return $results;
    }

    /**
     * Auto-create missing source language files for keys found in views.
     *
     * PHP-style keys (auth.login)  → lang/{sourceLang}/{file}.php  (merge)
     * JSON-style keys ("Log in")   → lang/{sourceLang}.json         (merge)
     * 'auto' file keys             → lang/{sourceLang}.json         (merge)
     *
     * Returns true if any file was created or updated.
     */
    protected function ensureSourceFilesExist(
        array $organized,
        string $sourceLang,
        array $existingPhpTranslations,
        array $existingJsonKeys
    ): bool {
        $newJsonKeys  = [];
        $newPhpByFile = [];

        foreach ($organized as $data) {
            $fullKey = $data['full_key'];

            // JSON-style key — original_text is set for plain-text keys
            if (isset($data['original_text'])) {
                if (!array_key_exists($fullKey, $existingJsonKeys)) {
                    // Key IS the value in Laravel JSON translations
                    $newJsonKeys[$fullKey] = $fullKey;
                }
                continue;
            }

            // PHP-style key — check existence in flattened source
            if (!isset($existingPhpTranslations[$fullKey])) {
                $file = $data['file'] ?? 'auto';
                $key  = $data['key']  ?? $fullKey;

                // 'auto' keys have no dot-notation — safer as JSON source
                if ($file === 'auto') {
                    if (!array_key_exists($fullKey, $existingJsonKeys)) {
                        $newJsonKeys[$fullKey] = $fullKey;
                    }
                    continue;
                }

                if (!isset($newPhpByFile[$file])) {
                    $newPhpByFile[$file] = [];
                }
                $newPhpByFile[$file][$key] = $data['default_value']
                    ?? Str::headline($key);
            }
        }

        $created = false;

        if (!empty($newJsonKeys)) {
            $this->jsonWriter->writeJson($sourceLang, $newJsonKeys);
            $created = true;
        }

        foreach ($newPhpByFile as $file => $keys) {
            $this->writer->write($sourceLang, $file, $keys, true);
            $created = true;
        }

        return $created;
    }

    protected function translateToLanguage(
        array $allSourceKeys,
        string $targetLang,
        string $sourceLang,
        bool $force = false,
        bool $dryRun = false,
        array $changedKeys = [],
        array $jsonSourceKeys = [],
        string $outputFormat = 'auto'
    ): array {
        $translated = [];
        $skipped    = 0;
        $locked     = 0;

        foreach ($allSourceKeys as $fullKey => $value) {
            if ($this->lockManager->isLocked($targetLang, $fullKey)) {
                $locked++;
                continue;
            }

            if ($force) {
                $translated[$fullKey] = $value;
                continue;
            }

            $isPhpKey = str_contains($fullKey, '.')
                && !str_contains($fullKey, ' ')
                && preg_match(
                    '/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)+$/',
                    $fullKey
                );

            $targetHasKey = false;
            $isTranslated = false;

            if (!$isPhpKey) {
                $existingJson = $this->jsonWriter->readJson($targetLang);
                if (array_key_exists($fullKey, $existingJson)) {
                    $targetHasKey = true;
                    $targetValue  = trim((string) $existingJson[$fullKey]);
                    $sourceValue  = trim((string) $value);
                    $isTranslated = $targetValue !== '' && $targetValue !== $sourceValue;
                }
            } else {
                $parts        = explode('.', $fullKey, 2);
                $file         = $parts[0];
                $key          = $parts[1] ?? $fullKey;
                $existingPath = lang_path("{$targetLang}/{$file}.php");

                if (File::exists($existingPath)) {
                    $existing = @include $existingPath;
                    if (is_array($existing) && array_key_exists($key, $existing)) {
                        $targetHasKey = true;
                        $targetValue  = trim((string) $existing[$key]);
                        $sourceValue  = trim((string) $value);
                        $isTranslated = $targetValue !== '' && $targetValue !== $sourceValue;
                    }
                }
            }

            if (!$targetHasKey) {
                $translated[$fullKey] = $value;
            } elseif (!$isTranslated) {
                $translated[$fullKey] = $value;
            } elseif (isset($changedKeys[$fullKey])) {
                $translated[$fullKey] = $value;
            } else {
                $skipped++;
            }
        }

        $translatedValues = [];
        if (!empty($translated)) {
            $translator       = $this->translatorManager->translator();
            $translatedValues = $translator->translateBatch(
                array_values($translated),
                $targetLang,
                $sourceLang
            );
        }

        $finalTranslations = [];
        $index             = 0;
        foreach ($translated as $fullKey => $originalValue) {
            $finalTranslations[$fullKey] = $translatedValues[$index] ?? $originalValue;
            $index++;
        }

        $phpKeys  = [];
        $jsonKeys = [];

        foreach ($finalTranslations as $key => $value) {
            $isPhpKey = !str_contains($key, ' ')
                && (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)+$/', $key);

            if ($outputFormat === 'json') {
                $jsonKeys[$key] = $value;
            } elseif ($outputFormat === 'php') {
                $phpKeys[$key] = $value;
            } else {
                if ($isPhpKey) {
                    $phpKeys[$key] = $value;
                } else {
                    $jsonKeys[$key] = $value;
                }
            }
        }

        $organizedByFile = $this->writer->organizeByFile($phpKeys);
        $writtenFiles    = [];

        if (!$dryRun) {
            foreach ($organizedByFile as $file => $translations) {
                $writtenFiles[] = $this->writer->write($targetLang, $file, $translations, true);
            }

            if (!empty($jsonKeys)) {
                $writtenFiles[] = $this->jsonWriter->writeJson($targetLang, $jsonKeys);
            }
        }

        if (!empty($jsonSourceKeys) && !$dryRun) {
            $jsonToTranslate = [];
            $existingJson    = $this->jsonWriter->readJson($targetLang);

            foreach ($jsonSourceKeys as $key => $value) {
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
                $index          = 0;
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

    protected function loadSourceTranslations(string $sourceLang): array
    {
        $langPath     = base_path('lang') . DIRECTORY_SEPARATOR . $sourceLang;
        $translations = [];

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

    public function estimateCost(array $targetLanguages, bool $force = false): array
    {
        $allKeys    = $this->scanner->scanAll();
        $organized  = $this->extractor->extractMultiple($allKeys);
        $texts      = array_column($organized, 'key');
        $translator = $this->translatorManager->translator();
        return $translator->estimateCost($texts, $targetLanguages);
    }
}
