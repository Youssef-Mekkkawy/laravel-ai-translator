<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Translators;

interface TranslatorInterface
{
    /**
     * Translate a single key from source to target language
     *
     * @param  string  $text  Text to translate
     * @param  string  $targetLang  Target language code (e.g., 'ar', 'fr')
     * @param  string  $sourceLang  Source language code (default: 'en')
     * @return string Translated text
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string;

    /**
     * Translate multiple texts at once (batch processing)
     *
     * @param  array  $texts  Array of texts to translate
     * @param  string  $targetLang  Target language code
     * @param  string  $sourceLang  Source language code
     * @return array Array of translations (same order as input)
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array;

    /**
     * Check if the translator is available and configured
     */
    public function isAvailable(): bool;

    /**
     * Get the name of this translator
     */
    public function getName(): string;

    /**
     * Estimate the cost of translating given texts
     *
     * @param  array  $texts  Texts to estimate
     * @param  array  $targetLangs  Target languages
     * @return array ['cost' => float, 'currency' => string, 'characters' => int]
     */
    public function estimateCost(array $texts, array $targetLangs): array;
}
