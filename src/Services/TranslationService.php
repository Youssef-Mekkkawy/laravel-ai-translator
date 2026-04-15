<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services;

use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\LanguageFileWriter;
use Illuminate\Support\Facades\File;

class TranslationService
{
    protected ViewScanner $scanner;
    protected KeyExtractor $extractor;
    protected TranslatorManager $translatorManager;
    protected LanguageFileWriter $writer;
    protected array $config;

    public function __construct(
        ViewScanner $scanner,
        KeyExtractor $extractor,
        TranslatorManager $translatorManager,
        LanguageFileWriter $writer,
        array $config = []
    ) {
        $this->scanner = $scanner;
        $this->extractor = $extractor;
        $this->translatorManager = $translatorManager;
        $this->writer = $writer;
        $this->config = $config;
    }

    /**
     * Translate all keys to all configured languages
     */
    public function translateAll(array $options = []): array
    {
        $startTime = microtime(true);
        
        $sourceLang = $options['source_lang'] ?? $this->config['default_language'] ?? 'en';
        $targetLangs = $options['target_langs'] ?? $this->getTargetLanguages();
        $force = $options['force'] ?? false;
        
        // Step 1: Scan views (returns flat array of keys)
        $allKeys = $this->scanner->scanAll();
        
        // Step 2: Load source translations
        $sourceTranslations = $this->loadSourceTranslations($sourceLang);
        
        // Step 3: Prepare translations
        $keysToTranslate = [];
        
        foreach ($allKeys as $key) {
            // Parse the key
            $parsed = $this->extractor->parseKey($key);
            $fullKey = $parsed['full_key'];
            
            // Get source value (from file or generate default)
            $sourceValue = $sourceTranslations[$fullKey] 
                ?? $this->extractor->generateDefaultValue($key);
            
            $keysToTranslate[$fullKey] = $sourceValue;
        }
        
        // Step 4: Translate to each target language
        $results = [
            'total_keys' => count($keysToTranslate),
            'languages' => [],
            'files_written' => [],
            'duration' => 0,
            'errors' => [],
        ];
        
        foreach ($targetLangs as $targetLang) {
            if ($targetLang === $sourceLang) {
                continue;
            }
            
            try {
                $langResult = $this->translateToLanguage(
                    $keysToTranslate,
                    $targetLang,
                    $sourceLang,
                    $force
                );
                
                $results['languages'][$targetLang] = $langResult;
                $results['files_written'] = array_merge(
                    $results['files_written'],
                    $langResult['files']
                );
                
            } catch (\Exception $e) {
                $results['errors'][$targetLang] = $e->getMessage();
            }
        }
        
        $results['duration'] = round(microtime(true) - $startTime, 2);
        
        return $results;
    }

    /**
     * Translate keys to a specific language
     */
    protected function translateToLanguage(
        array $keysToTranslate,
        string $targetLang,
        string $sourceLang,
        bool $force = false
    ): array {
        $translated = [];
        $skipped = 0;
        
        // Check which keys already exist (unless force mode)
        if (!$force) {
            $existingFiles = $this->writer->getFiles($targetLang);
            
            foreach ($keysToTranslate as $fullKey => $value) {
                $parts = explode('.', $fullKey, 2);
                $file = $parts[0];
                
                if (in_array($file, $existingFiles)) {
                    $existing = $this->writer->read($targetLang, $file);
                    $key = $parts[1] ?? $fullKey;
                    
                    if ($this->hasValue($existing, $key)) {
                        $skipped++;
                        continue;
                    }
                }
                
                $translated[$fullKey] = $value;
            }
        } else {
            $translated = $keysToTranslate;
        }
        
        // Translate the texts
        $translatedValues = [];
        
        if (!empty($translated)) {
            $translator = $this->translatorManager->translator();
            $translatedValues = $translator->translateBatch(
                array_values($translated),
                $targetLang,
                $sourceLang
            );
        }
        
        // Combine keys with translated values
        $finalTranslations = [];
        $index = 0;
        
        foreach ($translated as $fullKey => $originalValue) {
            $finalTranslations[$fullKey] = $translatedValues[$index] ?? $originalValue;
            $index++;
        }
        
        // Organize by file
        $organizedByFile = $this->writer->organizeByFile($finalTranslations);
        
        // Write to files
        $writtenFiles = [];
        
        foreach ($organizedByFile as $file => $translations) {
            $filePath = $this->writer->write($targetLang, $file, $translations, true);
            $writtenFiles[] = $filePath;
        }
        
        return [
            'translated' => count($finalTranslations),
            'skipped' => $skipped,
            'files' => $writtenFiles,
        ];
    }

    /**
     * Load source translations from language files
     */
    protected function loadSourceTranslations(string $sourceLang): array
    {
        $langPath = base_path('lang') . DIRECTORY_SEPARATOR . $sourceLang;
        
        if (!File::exists($langPath)) {
            return [];
        }
        
        $translations = [];
        $files = File::files($langPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            
            $fileName = $file->getBasename('.php');
            $fileTranslations = include $file->getPathname();
            
            if (!is_array($fileTranslations)) {
                continue;
            }
            
            // Flatten with dot notation
            $flattened = $this->flattenArray($fileTranslations, $fileName);
            $translations = array_merge($translations, $flattened);
        }
        
        return $translations;
    }

    /**
     * Flatten array with dot notation
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
     * Check if array has nested value
     */
    protected function hasValue(array $array, string $key): bool
    {
        if (strpos($key, '.') === false) {
            return isset($array[$key]);
        }
        
        $keys = explode('.', $key);
        $current = $array;
        
        foreach ($keys as $k) {
            if (!is_array($current) || !isset($current[$k])) {
                return false;
            }
            $current = $current[$k];
        }
        
        return true;
    }

    /**
     * Get target languages from config
     */
    protected function getTargetLanguages(): array
    {
        $languages = $this->config['languages'] ?? ['en'];
        $sourceLang = $this->config['default_language'] ?? 'en';
        
        return array_filter($languages, function ($lang) use ($sourceLang) {
            return $lang !== $sourceLang;
        });
    }

    /**
     * Estimate translation cost
     */
    public function estimateCost(array $options = []): array
    {
        $sourceLang = $options['source_lang'] ?? $this->config['default_language'] ?? 'en';
        $targetLangs = $options['target_langs'] ?? $this->getTargetLanguages();
        
        // Scan views (returns flat array of keys)
        $allKeys = $this->scanner->scanAll();
        
        // Prepare texts for estimation
        $texts = [];
        foreach ($allKeys as $key) {
            $texts[] = $this->extractor->generateDefaultValue($key);
        }
        
        // Get translator and estimate
        $translator = $this->translatorManager->translator();
        $translatorEstimate = $translator->estimateCost($texts, $targetLangs);
        
        // Format for command display
        $totalChars = $translatorEstimate['characters'] ?? 0;
        $estimatedCost = $translatorEstimate['cost'] ?? 0.0;
        
        // Calculate estimated time (rough estimate: 100 chars per second)
        $estimatedSeconds = max(1, (int)($totalChars / 100));
        $estimatedTime = $estimatedSeconds < 60 
            ? "~{$estimatedSeconds} second" . ($estimatedSeconds > 1 ? 's' : '')
            : "~" . ceil($estimatedSeconds / 60) . " minute" . (ceil($estimatedSeconds / 60) > 1 ? 's' : '');
        
        return [
            'total_keys' => count($allKeys),
            'total_characters' => $totalChars,
            'languages' => $targetLangs,
            'estimated_cost' => $estimatedCost,
            'estimated_time' => $estimatedTime,
        ];
    }
}