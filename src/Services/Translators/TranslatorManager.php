<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Translators;

class TranslatorManager
{
    protected ?TranslatorInterface $translator = null;

    protected array $config;

    /**
     * All registered translation drivers.
     */
    protected array $translators = [
        'deepl' => DeepLTranslator::class,
        'ollama' => OllamaTranslator::class,
        // 'openai'  => OpenAITranslator::class,
        // 'claude'  => ClaudeTranslator::class,
        // 'google'  => GoogleTranslator::class,
        // 'gemini'  => GeminiTranslator::class,
    ];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Resolve and return the active translator instance.
     */
    public function translator(?string $driver = null): TranslatorInterface
    {
        $driver = $driver ?? $this->config['driver'] ?? 'ollama';

        if (! isset($this->translators[$driver])) {
            throw new \RuntimeException("Translation driver '{$driver}' is not supported.");
        }

        $class = $this->translators[$driver];
        $config = $this->config['providers'][$driver] ?? [];

        return new $class($config);
    }

    public function translate(string $text, string $targetLang, string $sourceLang = 'en', ?string $driver = null): string
    {
        return $this->translator($driver)->translate($text, $targetLang, $sourceLang);
    }

    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en', ?string $driver = null): array
    {
        return $this->translator($driver)->translateBatch($texts, $targetLang, $sourceLang);
    }

    public function isAvailable(?string $driver = null): bool
    {
        try {
            return $this->translator($driver)->isAvailable();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function estimateCost(array $texts, array $targetLangs, ?string $driver = null): array
    {
        return $this->translator($driver)->estimateCost($texts, $targetLangs);
    }

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

    public function getAllDrivers(): array
    {
        return array_keys($this->translators);
    }
}
