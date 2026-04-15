<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services;

use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\LanguageFileWriter;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\ChangeTracker;

class TranslationService
{
    protected ViewScanner $scanner;
    protected KeyExtractor $extractor;
    protected TranslatorManager $translator;
    protected LanguageFileWriter $writer;
    protected ChangeTracker $changeTracker;
    protected array $config;

    public function __construct(
        ViewScanner $scanner,
        KeyExtractor $extractor,
        TranslatorManager $translator,
        LanguageFileWriter $writer,
        ChangeTracker $changeTracker,
        array $config
    ) {
        $this->scanner = $scanner;
        $this->extractor = $extractor;
        $this->translator = $translator;
        $this->writer = $writer;
        $this->changeTracker = $changeTracker;
        $this->config = $config;
    }

    /**
     * Translate all discovered keys to target languages
     *
     * @param array $targetLanguages Target language codes
     * @param bool $force Force re-translation of all keys
     * @param bool $dryRun Don't actually write files
     * @return array Results
     */
    public function translateAll(array $targetLanguages, bool $force = false, bool $dryRun = false): array
    {
        $results = [
            'total_keys' => 0,
            'languages_processed' => 0,
            'files_written' => [],
            'errors' => [],
            'skipped_unchanged' => 0,
            'duration' => 0,
        ];

        $startTime = microtime(true);

        // Step 1: Scan views for translation keys
        $scanPaths = $this->config['scan_paths'] ?? [resource_path('views')];
        $discoveredKeys = [];
        
        foreach ($scanPaths as $path) {
            $keys = $this->scanner->scan($path);
            $discoveredKeys = array_merge($discoveredKeys, $keys);
        }
        
        $discoveredKeys = array_unique($discoveredKeys);
        
        // Step 2: Load source translations (en/)
        $sourceTranslations = $this->loadSourceTranslations();
        
        // Step 3: Merge discovered keys with source
        $allTranslations = $this->mergeTranslations($discoveredKeys, $sourceTranslations);
        
        $results['total_keys'] = count($allTranslations);

        // Step 4: Use change tracking to filter (unless forced)
        $translationsToProcess = $allTranslations;
        
        if (!$force && ($this->config['change_tracking']['enabled'] ?? true)) {
            // Get only changed/new keys
            $sourceLanguage = $this->config['source_language'] ?? 'en';
            $changedKeys = $this->changeTracker->getKeysToTranslate(
                $allTranslations,
                $sourceLanguage,
                $force
            );
            
            // Filter to only changed keys
            $translationsToProcess = [];
            foreach ($changedKeys as $key) {
                if (isset($allTranslations[$key])) {
                    $translationsToProcess[$key] = $allTranslations[$key];
                }
            }
            
            $results['skipped_unchanged'] = count($allTranslations) - count($translationsToProcess);
        }

        // Step 5: Translate to each target language
        foreach ($targetLanguages as $language) {
            try {
                $languageResult = $this->translateToLanguage(
                    $language,
                    $translationsToProcess,
                    $dryRun
                );
                
                if ($languageResult['success']) {
                    $results['languages_processed']++;
                    $results['files_written'] = array_merge(
                        $results['files_written'],
                        $languageResult['files_written']
                    );
                } else {
                    $results['errors'][$language] = $languageResult['error'];
                }
            } catch (\Exception $e) {
                $results['errors'][$language] = $e->getMessage();
            }
        }

        // Step 6: Update tracking metadata (if not dry run and tracking enabled)
        if (!$dryRun && ($this->config['change_tracking']['enabled'] ?? true)) {
            try {
                $sourceLanguage = $this->config['source_language'] ?? 'en';
                $this->changeTracker->updateHashes($allTranslations, $sourceLanguage);
                
                // Cleanup deleted keys
                $this->changeTracker->cleanupDeletedKeys($allTranslations, $sourceLanguage);
            } catch (\Exception $e) {
                // Log error but don't fail the whole process
                $results['errors']['tracking'] = 'Failed to update tracking: ' . $e->getMessage();
            }
        }

        $results['duration'] = round(microtime(true) - $startTime, 2);

        return $results;
    }

    /**
     * Translate to a specific language
     *
     * @param string $language Target language code
     * @param array $translations Translations to process (flat array with dot notation keys)
     * @param bool $dryRun Don't write files
     * @return array Result
     */
    protected function translateToLanguage(string $language, array $translations, bool $dryRun = false): array
    {
        $result = [
            'success' => false,
            'files_written' => [],
            'error' => null,
        ];

        try {
            // Load existing translations for this language
            $existing = $this->loadLanguageTranslations($language);

            // Determine which keys need translation
            $keysToTranslate = [];
            foreach ($translations as $key => $text) {
                // Skip if already exists (unless forcing)
                if (isset($existing[$key])) {
                    continue;
                }
                $keysToTranslate[$key] = $text;
            }

            // If nothing to translate, we're done
            if (empty($keysToTranslate)) {
                $result['success'] = true;
                return $result;
            }

            // Translate the batch
            $translated = $this->translator->translateBatch(
                array_values($keysToTranslate),
                $language
            );

            // Map back to keys
            $keysList = array_keys($keysToTranslate);
            $translatedMap = [];
            foreach ($translated as $index => $translatedText) {
                $translatedMap[$keysList[$index]] = $translatedText;
            }

            // Merge with existing
            $final = array_merge($existing, $translatedMap);

            // Write files (unless dry run)
            if (!$dryRun) {
                // Group translations by file
                $fileTranslations = $this->groupByFile($final);
                
                // Write using writeMultiple
                $files = $this->writer->writeMultiple($language, $fileTranslations);
                $result['files_written'] = array_values($files);
            }

            $result['success'] = true;

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Group flat translations by file
     *
     * @param array $translations Flat array with dot notation keys
     * @return array Grouped by file
     */
    protected function groupByFile(array $translations): array
    {
        $grouped = [];
        
        foreach ($translations as $key => $value) {
            // Parse key to get file and actual key
            $parsed = $this->extractor->parseKey($key);
            $file = $parsed['file'];
            $actualKey = $parsed['key'];
            
            if (!isset($grouped[$file])) {
                $grouped[$file] = [];
            }
            
            $grouped[$file][$actualKey] = $value;
        }
        
        return $grouped;
    }

    /**
     * Load source language translations
     *
     * @return array Flat array of translations
     */
    protected function loadSourceTranslations(): array
    {
        $sourceLanguage = $this->config['source_language'] ?? 'en';
        return $this->loadLanguageTranslations($sourceLanguage);
    }

    /**
     * Load translations for a language
     *
     * @param string $language Language code
     * @return array Flat array of translations
     */
    protected function loadLanguageTranslations(string $language): array
    {
        $langPath = lang_path($language);
        
        if (!file_exists($langPath)) {
            return [];
        }

        $translations = [];
        $files = glob($langPath . '/*.php');

        foreach ($files as $file) {
            $namespace = basename($file, '.php');
            $data = include $file;
            
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $flatKey = $namespace . '.' . $key;
                    $translations[$flatKey] = $value;
                }
            }
        }

        return $translations;
    }

    /**
     * Merge discovered keys with source translations
     *
     * @param array $discovered Discovered keys from views
     * @param array $source Source language translations
     * @return array Merged translations
     */
    protected function mergeTranslations(array $discovered, array $source): array
    {
        $merged = [];

        // Add all discovered keys
        foreach ($discovered as $key) {
            // Parse the key to get namespace and actual key
            $parsed = $this->extractor->parseKey($key);
            
            // If exists in source, use source value
            if (isset($source[$key])) {
                $merged[$key] = $source[$key];
            } else {
                // Generate default value
                $merged[$key] = $this->extractor->generateDefaultValue($parsed['key']);
            }
        }

        return $merged;
    }

    /**
     * Estimate translation cost
     *
     * @param array $targetLanguages Target languages
     * @param bool $force Force re-translation
     * @return array Cost estimation
     */
    public function estimateCost(array $targetLanguages, bool $force = false): array
    {
        // Scan and load translations
        $scanPaths = $this->config['scan_paths'] ?? [resource_path('views')];
        $discoveredKeys = [];
        
        foreach ($scanPaths as $path) {
            $keys = $this->scanner->scan($path);
            $discoveredKeys = array_merge($discoveredKeys, $keys);
        }
        
        $discoveredKeys = array_unique($discoveredKeys);
        
        $sourceTranslations = $this->loadSourceTranslations();
        $allTranslations = $this->mergeTranslations($discoveredKeys, $sourceTranslations);
        
        // Filter by change tracking
        $translationsToProcess = $allTranslations;
        
        if (!$force && ($this->config['change_tracking']['enabled'] ?? true)) {
            $sourceLanguage = $this->config['source_language'] ?? 'en';
            $translationsToProcess = $this->changeTracker->filterChanged(
                $allTranslations,
                $sourceLanguage,
                $force
            );
        }
        
        // Get character count
        $totalCharacters = array_sum(array_map('mb_strlen', $translationsToProcess));
        
        // Get estimation from translator (pass array of languages, not count!)
        $texts = array_values($translationsToProcess);
        $estimation = $this->translator->estimateCost($texts, $targetLanguages);
        
        // Format for command display
        return [
            'total_keys' => count($translationsToProcess),
            'total_characters' => $totalCharacters,
            'languages' => $targetLanguages, // Return array for display
            'language_count' => count($targetLanguages), // Count for calculations
            'estimated_cost' => $estimation['estimated_cost'] ?? 0,
            'estimated_time' => $estimation['estimated_time'] ?? '~1 second',
            'skipped_keys' => count($allTranslations) - count($translationsToProcess),
        ];
    }

    /**
     * Get change tracking report
     *
     * @return array Report
     */
    public function getChangeReport(): array
    {
        $sourceLanguage = $this->config['source_language'] ?? 'en';
        $sourceTranslations = $this->loadSourceTranslations();
        
        return $this->changeTracker->getChangeReport($sourceTranslations, $sourceLanguage);
    }

    /**
     * Reset change tracking
     *
     * @return void
     */
    public function resetTracking(): void
    {
        $this->changeTracker->reset();
    }
}