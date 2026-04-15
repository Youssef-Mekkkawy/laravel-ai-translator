<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Translators;

use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorInterface;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\DeepLTranslator;

class TranslatorManager
{
    /**
     * Active translator instance
     */
    protected ?TranslatorInterface $translator = null;

    /**
     * Configuration
     */
    protected array $config;

    /**
     * Available translators
     */
    protected array $translators = [
        'deepl' => DeepLTranslator::class,
        // 'openai' => OpenAITranslator::class,
        // 'claude' => ClaudeTranslator::class,
        // 'google' => GoogleTranslator::class,
        // 'gemini' => GeminiTranslator::class,
        // 'ollama' => OllamaTranslator::class,
    ];

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Get translator instance
     */
    public function translator(?string $driver = null): TranslatorInterface
    {
        $driver = $driver ?? $this->config['driver'] ?? 'deepl';

        if (!isset($this->translators[$driver])) {
            throw new \RuntimeException("Translation driver '{$driver}' is not supported");
        }

        $translatorClass = $this->translators[$driver];
        $translatorConfig = $this->config['providers'][$driver] ?? [];

        return new $translatorClass($translatorConfig);
    }

    /**
     * Translate single text
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'en', ?string $driver = null): string
    {
        return $this->translator($driver)->translate($text, $targetLang, $sourceLang);
    }

    /**
     * Translate multiple texts
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en', ?string $driver = null): array
    {
        return $this->translator($driver)->translateBatch($texts, $targetLang, $sourceLang);
    }

    /**
     * Check if driver is available
     */
    public function isAvailable(?string $driver = null): bool
    {
        try {
            return $this->translator($driver)->isAvailable();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Estimate translation cost
     */
    public function estimateCost(array $texts, array $targetLangs, ?string $driver = null): array
    {
        return $this->translator($driver)->estimateCost($texts, $targetLangs);
    }

    /**
     * Get list of available drivers
     */
    public function getAvailableDrivers(): array
    {
        $drivers = [];

        foreach (array_keys($this->translators) as $driver) {
            if ($this->isAvailable($driver)) {
                $drivers[] = $driver;
            }
        }

        return $drivers;
    }

    /**
     * Get all configured drivers (even if not available)
     */
    public function getAllDrivers(): array
    {
        return array_keys($this->translators);
    }
}