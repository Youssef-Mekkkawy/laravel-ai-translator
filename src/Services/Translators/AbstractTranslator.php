<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Translators;

abstract class AbstractTranslator implements TranslatorInterface
{
    /**
     * Configuration for this translator
     */
    protected array $config;

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Translate a single text
     */
    abstract public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string;

    /**
     * Translate multiple texts (default implementation - can be overridden)
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        $translations = [];

        foreach ($texts as $text) {
            $translations[] = $this->translate($text, $targetLang, $sourceLang);
        }

        return $translations;
    }

    /**
     * Check if translator is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->config['api_key'] ?? null);
    }

    /**
     * Get translator name
     */
    abstract public function getName(): string;

    /**
     * Estimate cost (default implementation)
     */
    public function estimateCost(array $texts, array $targetLangs): array
    {
        $totalChars = 0;
        
        foreach ($texts as $text) {
            $totalChars += mb_strlen($text);
        }

        $totalChars *= count($targetLangs);

        return [
            'characters' => $totalChars,
            'cost' => 0.0,
            'currency' => 'USD',
            'note' => 'Cost estimation not available for this provider',
        ];
    }

    /**
     * Preserve Laravel placeholders in text
     * Protects: :name, :count, {0}, {1}, etc.
     */
    protected function preservePlaceholders(string $text): array
    {
        $placeholders = [];
        
        // Find all Laravel placeholders
        // Pattern 1: :name, :count, :attribute
        preg_match_all('/(:\w+)/', $text, $matches1);
        
        // Pattern 2: {0}, {1}, {2}
        preg_match_all('/(\{\d+\})/', $text, $matches2);
        
        // Combine all matches
        $allMatches = array_merge($matches1[1] ?? [], $matches2[1] ?? []);
        
        // Replace with unique markers
        foreach (array_unique($allMatches) as $index => $placeholder) {
            $marker = "___PLACEHOLDER_{$index}___";
            $placeholders[$marker] = $placeholder;
            $text = str_replace($placeholder, $marker, $text);
        }

        return [$text, $placeholders];
    }

    /**
     * Restore placeholders after translation
     */
    protected function restorePlaceholders(string $text, array $placeholders): string
    {
        foreach ($placeholders as $marker => $original) {
            $text = str_replace($marker, $original, $text);
        }

        return $text;
    }

    /**
     * Preserve HTML tags in text
     */
    protected function preserveHtmlTags(string $text): array
    {
        $tags = [];
        
        // Find all HTML tags
        preg_match_all('/(<[^>]+>)/', $text, $matches);
        
        // Replace with unique markers
        foreach ($matches[1] ?? [] as $index => $tag) {
            $marker = "___TAG_{$index}___";
            $tags[$marker] = $tag;
            $text = str_replace($tag, $marker, $text);
        }

        return [$text, $tags];
    }

    /**
     * Restore HTML tags after translation
     */
    protected function restoreHtmlTags(string $text, array $tags): string
    {
        foreach ($tags as $marker => $original) {
            $text = str_replace($marker, $original, $text);
        }

        return $text;
    }

    /**
     * Prepare text for translation
     * Preserves placeholders and HTML tags
     */
    protected function prepareText(string $text): array
    {
        // First preserve placeholders
        [$text, $placeholders] = $this->preservePlaceholders($text);
        
        // Then preserve HTML tags
        [$text, $tags] = $this->preserveHtmlTags($text);

        return [$text, $placeholders, $tags];
    }

    /**
     * Restore text after translation
     */
    protected function restoreText(string $text, array $placeholders, array $tags): string
    {
        // Restore in reverse order
        $text = $this->restoreHtmlTags($text, $tags);
        $text = $this->restorePlaceholders($text, $placeholders);

        return $text;
    }

    /**
     * Chunk array into smaller batches
     */
    protected function chunkArray(array $items, int $chunkSize): array
    {
        return array_chunk($items, $chunkSize);
    }

    /**
     * Get configuration value
     */
    protected function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
}