<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Translators;

use Illuminate\Support\Facades\Http;
use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;

class OllamaTranslator extends AbstractTranslator
{
    /**
     * Get the translator name.
     */
    public function getName(): string
    {
        return 'ollama';
    }

    /**
     * Check if Ollama is running locally.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get($this->baseUrl().'/api/tags');

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Translate a single text.
     */
    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        // Protect placeholders and HTML tags before sending to AI
        [$prepared, $placeholders, $tags] = $this->prepareText($text);

        $translated = $this->sendChatRequest(
            $this->buildSinglePrompt($prepared, $targetLang, $sourceLang)
        );

        return $this->restoreText($translated, $placeholders, $tags);
    }

    /**
     * Translate multiple texts efficiently using a single JSON batch request.
     * Falls back to individual calls if the model returns malformed JSON.
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        if (empty($texts)) {
            return [];
        }

        // Prepare all texts (protect placeholders + HTML)
        $prepared = [];
        $restoreMap = [];

        foreach ($texts as $i => $text) {
            if (empty(trim((string) $text))) {
                $prepared[$i] = '';
                $restoreMap[$i] = [[], []];

                continue;
            }

            [$preparedText, $placeholders, $tags] = $this->prepareText((string) $text);
            $prepared[$i] = $preparedText;
            $restoreMap[$i] = [$placeholders, $tags];
        }

        // Split into chunks so we don't exceed context windows
        $chunkSize = (int) $this->getConfig('chunk_size', 20);
        $chunks = array_chunk($prepared, $chunkSize, true);
        $translations = [];

        foreach ($chunks as $chunk) {
            $translated = $this->translateChunk($chunk, $targetLang, $sourceLang);
            $translations = $translations + $translated;
        }

        // Restore placeholders and HTML on every result
        $result = [];
        foreach ($texts as $i => $text) {
            [$placeholders, $tags] = $restoreMap[$i];
            $raw = $translations[$i] ?? (string) $text;
            $result[] = $this->restoreText($raw, $placeholders, $tags);
        }

        return $result;
    }

    /**
     * Ollama is free and local — no cost.
     */
    public function estimateCost(array $texts, array $targetLangs): array
    {
        $totalChars = 0;
        foreach ($texts as $text) {
            $totalChars += mb_strlen((string) $text);
        }

        return [
            'characters' => $totalChars * count($targetLangs),
            'total_characters' => $totalChars * count($targetLangs),
            'cost' => 0.0,
            'estimated_cost' => 0.0,
            'currency' => 'USD',
            'note' => 'Ollama runs locally — zero API cost',
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Translate a chunk of texts via a single JSON batch prompt.
     * Falls back to individual calls on JSON parse failure.
     */
    private function translateChunk(array $chunk, string $targetLang, string $sourceLang): array
    {
        // Filter out empty strings — translate non-empty ones as a JSON object
        $toTranslate = array_filter($chunk, fn ($t) => trim($t) !== '');

        if (empty($toTranslate)) {
            return $chunk; // all empty, return as-is
        }

        $prompt = $this->buildBatchPrompt($toTranslate, $targetLang, $sourceLang);

        try {
            $raw = $this->sendChatRequest($prompt);
            $json = $this->extractJson($raw);

            if (is_array($json)) {
                // Map translated values back to original positions.
                // Works whether model returns {"0":"val"} or ["val1","val2"].
                $jsonValues = array_values($json);
                $result = [];
                $pos = 0;

                foreach ($chunk as $i => $text) {
                    $result[$i] = trim((string) $text) === ''
                        ? $text
                        : (string) ($json[$i] ?? $jsonValues[$pos] ?? $text);
                    $pos++;
                }

                return $result;
            }
        } catch (\Throwable $e) {
            // Fall through to individual translation
        }

        // Fallback: translate one by one
        $result = [];
        foreach ($chunk as $i => $text) {
            $result[$i] = trim((string) $text) === ''
                ? $text
                : $this->sendChatRequest($this->buildSinglePrompt($text, $targetLang, $sourceLang));
        }

        return $result;
    }

    /**
     * Send a chat/completions request to Ollama and return the assistant reply.
     */
    private function sendChatRequest(string $userMessage): string
    {
        $timeout = (int) $this->getConfig('timeout', 120);

        // Build system message — include context prompt if set
        $runtime   = new RuntimeConfig();
        $context   = $runtime->get('context', '')
            ?: config('ai-translator.options.context', '');
        $sysMsg    = 'You are a professional translator. Follow all instructions exactly.';
        if (!empty(trim((string) $context))) {
            $sysMsg .= '\n\nContext about this application: ' . trim($context);
        }

        $response = Http::timeout($timeout)
            ->post($this->baseUrl().'/v1/chat/completions', [
                'model' => $this->model(),
                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => $sysMsg,
                    ],
                    [
                        'role'    => 'user',
                        'content' => $userMessage,
                    ],
                ],
                'stream'      => false,
                'temperature' => 0.1,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "Ollama API error [{$response->status()}]: ".$response->body()
            );
        }

        return trim(
            $response->json('choices.0.message.content', '')
        );
    }

    /**
     * Build a single-text translation prompt.
     */
    private function buildSinglePrompt(string $text, string $targetLang, string $sourceLang): string
    {
        $targetName = $this->languageName($targetLang);

        return <<<PROMPT
Translate the following text from {$sourceLang} to {$targetName}.

Rules:
- Return ONLY the translated text, no explanations, no quotes.
- Preserve any markers like ___PLACEHOLDER_0___ or ___TAG_0___ exactly as they appear.

Text:
{$text}
PROMPT;
    }

    /**
     * Build a batch translation prompt that returns a JSON object.
     * Keys are the original array positions; values are the texts to translate.
     */
    private function buildBatchPrompt(array $texts, string $targetLang, string $sourceLang): string
    {
        $targetName = $this->languageName($targetLang);
        $input = json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Translate the following JSON object values from {$sourceLang} to {$targetName}.

Rules:
- Return ONLY valid JSON, no explanations, no markdown code blocks.
- Keep the same numeric keys.
- Preserve any markers like ___PLACEHOLDER_0___ or ___TAG_0___ exactly as they appear.

Input:
{$input}
PROMPT;
    }

    /**
     * Extract a JSON object or array from a raw AI response.
     * Handles cases where the model wraps JSON in markdown code blocks.
     */
    private function extractJson(string $raw): ?array
    {
        // Strip markdown code fences if present
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $clean = preg_replace('/```\s*$/m', '', $clean ?? $raw);
        $clean = trim($clean ?? $raw);

        // Try to parse the whole cleaned string as JSON first (fastest path)
        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // Try to find a JSON object { ... }
        if (preg_match('/\{.+\}/s', $clean, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Try to find a JSON array [ ... ]
        if (preg_match('/\[.+\]/s', $clean, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Get the Ollama base URL (without trailing slash).
     */
    private function baseUrl(): string
    {
        $url = $this->getConfig('api_url', 'http://localhost:11434');

        // Strip any /v1 suffix — we add it ourselves per-endpoint
        return rtrim(str_replace('/v1', '', $url), '/');
    }

    /**
     * Get the configured model name.
     */
    private function model(): string
    {
        return $this->getConfig('model', 'llama3');
    }

    /**
     * Map an ISO language code to a human-readable name for better prompts.
     */
    private function languageName(string $code): string
    {
        $map = [
            'ar' => 'Arabic',
            'fr' => 'French',
            'es' => 'Spanish',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'zh' => 'Chinese (Simplified)',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'tr' => 'Turkish',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'hi' => 'Hindi',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'fi' => 'Finnish',
            'no' => 'Norwegian',
            'cs' => 'Czech',
            'el' => 'Greek',
            'he' => 'Hebrew',
            'ro' => 'Romanian',
            'hu' => 'Hungarian',
            'uk' => 'Ukrainian',
            'vi' => 'Vietnamese',
            'th' => 'Thai',
            'id' => 'Indonesian',
            'ms' => 'Malay',
        ];

        return $map[$code] ?? strtoupper($code);
    }
}
