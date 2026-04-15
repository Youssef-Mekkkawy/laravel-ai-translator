<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Translators;

use DeepL\Translator as DeepLClient;
use DeepL\DeepLException;

class DeepLTranslator extends AbstractTranslator
{
    /**
     * DeepL client instance
     */
    protected ?DeepLClient $client = null;

    /**
     * Maximum texts per batch (DeepL limit)
     */
    protected int $batchSize = 50;

    /**
     * Get translator name
     */
    public function getName(): string
    {
        return 'DeepL';
    }

    /**
     * Get DeepL client instance
     */
    protected function getClient(): DeepLClient
    {
        if ($this->client === null) {
            $apiKey = $this->getConfig('api_key');
            
            if (empty($apiKey)) {
                throw new \RuntimeException('DeepL API key not configured');
            }

            $this->client = new DeepLClient($apiKey);
        }

        return $this->client;
    }

    /**
     * Translate a single text
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        try {
            // Prepare text (preserve placeholders and HTML)
            [$preparedText, $placeholders, $tags] = $this->prepareText($text);

            // Translate using DeepL
            $result = $this->getClient()->translateText(
                $preparedText,
                $sourceLang,
                $this->normalizeLanguageCode($targetLang)
            );

            $translatedText = $result->text;

            // Restore placeholders and HTML
            return $this->restoreText($translatedText, $placeholders, $tags);

        } catch (DeepLException $e) {
            throw new \RuntimeException("DeepL translation failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Translate multiple texts in batch (optimized for DeepL)
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        if (empty($texts)) {
            return [];
        }

        try {
            $translations = [];
            $preparedData = [];

            // Prepare all texts
            foreach ($texts as $index => $text) {
                [$preparedText, $placeholders, $tags] = $this->prepareText($text);
                $preparedData[$index] = [
                    'prepared' => $preparedText,
                    'placeholders' => $placeholders,
                    'tags' => $tags,
                ];
            }

            // Extract prepared texts
            $preparedTexts = array_column($preparedData, 'prepared');

            // Split into chunks (DeepL has batch limits)
            $chunks = $this->chunkArray($preparedTexts, $this->batchSize);
            $allResults = [];

            foreach ($chunks as $chunk) {
                // Translate batch
                $results = $this->getClient()->translateText(
                    $chunk,
                    $sourceLang,
                    $this->normalizeLanguageCode($targetLang)
                );

                // Extract translated texts
                foreach ($results as $result) {
                    $allResults[] = $result->text;
                }
            }

            // Restore placeholders and tags
            foreach ($allResults as $index => $translatedText) {
                $placeholders = $preparedData[$index]['placeholders'];
                $tags = $preparedData[$index]['tags'];
                $translations[] = $this->restoreText($translatedText, $placeholders, $tags);
            }

            return $translations;

        } catch (DeepLException $e) {
            throw new \RuntimeException("DeepL batch translation failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Normalize language code for DeepL
     * DeepL uses specific codes like 'EN-US', 'PT-BR', etc.
     */
    protected function normalizeLanguageCode(string $lang): string
    {
        // Map common codes to DeepL format
        $mapping = [
            'en' => 'EN-US',
            'pt' => 'PT-BR',
            'ar' => 'AR',
            'fr' => 'FR',
            'de' => 'DE',
            'es' => 'ES',
            'it' => 'IT',
            'ja' => 'JA',
            'ko' => 'KO',
            'nl' => 'NL',
            'pl' => 'PL',
            'ru' => 'RU',
            'zh' => 'ZH',
        ];

        return $mapping[strtolower($lang)] ?? strtoupper($lang);
    }

    /**
     * Estimate cost for DeepL
     */
    public function estimateCost(array $texts, array $targetLangs): array
    {
        $totalChars = 0;
        
        foreach ($texts as $text) {
            $totalChars += mb_strlen($text);
        }

        $totalChars *= count($targetLangs);

        // DeepL pricing:
        // Free tier: 500,000 chars/month
        // Pro: $5.49 per million characters
        $costPerMillion = 5.49;
        $cost = ($totalChars / 1_000_000) * $costPerMillion;

        return [
            'characters' => $totalChars,
            'cost' => round($cost, 4),
            'currency' => 'USD',
            'provider' => 'DeepL',
            'note' => 'Free tier: 500K chars/month. Pro: $5.49/million chars',
        ];
    }

    /**
     * Check usage statistics
     */
    public function getUsage(): array
    {
        try {
            $usage = $this->getClient()->getUsage();

            return [
                'character_count' => $usage->character->count ?? 0,
                'character_limit' => $usage->character->limit ?? 0,
                'remaining' => ($usage->character->limit ?? 0) - ($usage->character->count ?? 0),
                'percentage_used' => $usage->character->limit > 0 
                    ? round(($usage->character->count / $usage->character->limit) * 100, 2)
                    : 0,
            ];
        } catch (DeepLException $e) {
            throw new \RuntimeException("Failed to get DeepL usage: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get supported languages
     */
    public function getSupportedLanguages(): array
    {
        try {
            $languages = $this->getClient()->getTargetLanguages();
            
            $supported = [];
            foreach ($languages as $language) {
                $supported[] = [
                    'code' => $language->code,
                    'name' => $language->name,
                ];
            }

            return $supported;
        } catch (DeepLException $e) {
            throw new \RuntimeException("Failed to get supported languages: {$e->getMessage()}", 0, $e);
        }
    }
}