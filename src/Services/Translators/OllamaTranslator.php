<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Translators;

use Illuminate\Support\Facades\Http;
use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;

class OllamaTranslator extends AbstractTranslator
{
    public function getName(): string
    {
        return 'ollama';
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get($this->baseUrl() . '/api/tags');
            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function translate(string $text, string $targetLang, string $sourceLang = 'en'): string
    {
        if (empty(trim($text))) {
            return $text;
        }

        [$prepared, $placeholders, $tags] = $this->prepareText($text);

        $translated = $this->sendChatRequest(
            $this->buildSinglePrompt($prepared, $targetLang, $sourceLang)
        );

        return $this->restoreText($translated, $placeholders, $tags);
    }

    /**
     * Translate multiple texts in batches.
     *
     * Speed improvements vs original:
     * - chunk_size default raised from 20 → 50  (fewer API calls)
     * - long-string threshold raised from 150 → 500 chars (more strings batch together)
     */
    public function translateBatch(array $texts, string $targetLang, string $sourceLang = 'en'): array
    {
        if (empty($texts)) {
            return [];
        }

        $prepared   = [];
        $restoreMap = [];

        foreach ($texts as $i => $text) {
            if (empty(trim((string) $text))) {
                $prepared[$i]   = '';
                $restoreMap[$i] = [[], []];
                continue;
            }

            [$preparedText, $placeholders, $tags] = $this->prepareText((string) $text);
            $prepared[$i]   = $preparedText;
            $restoreMap[$i] = [$placeholders, $tags];
        }

        // SPEED FIX: default chunk_size raised to 50 (was 20)
        $chunkSize    = (int) $this->getConfig('chunk_size', 50);
        $chunks       = array_chunk($prepared, $chunkSize, true);
        $translations = [];

        foreach ($chunks as $chunk) {
            $translated   = $this->translateChunk($chunk, $targetLang, $sourceLang);
            $translations = $translations + $translated;
        }

        $result = [];
        foreach ($texts as $i => $text) {
            [$placeholders, $tags] = $restoreMap[$i];
            $raw      = $translations[$i] ?? (string) $text;
            $result[] = $this->restoreText($raw, $placeholders, $tags);
        }

        return $result;
    }

    public function estimateCost(array $texts, array $targetLangs): array
    {
        $totalChars = 0;
        foreach ($texts as $text) {
            $totalChars += mb_strlen((string) $text);
        }

        return [
            'characters'       => $totalChars * count($targetLangs),
            'total_characters' => $totalChars * count($targetLangs),
            'cost'             => 0.0,
            'estimated_cost'   => 0.0,
            'currency'         => 'USD',
            'note'             => 'Ollama runs locally — zero API cost',
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Translate a chunk of texts.
     *
     * SPEED FIX: long-string threshold raised from 150 → 500 chars.
     * Most UI strings are well under 500 chars, so nearly everything
     * goes through the fast batch path instead of individual calls.
     */
    private function translateChunk(array $chunk, string $targetLang, string $sourceLang): array
    {
        $result     = [];
        $shortChunk = [];

        foreach ($chunk as $i => $text) {
            if (trim((string) $text) === '') {
                $result[$i] = $text;
            } elseif (mb_strlen((string) $text) > 500) {
                // Only truly long strings (500+ chars) go individual
                $result[$i] = $this->sendChatRequest(
                    $this->buildSinglePrompt((string) $text, $targetLang, $sourceLang)
                );
            } else {
                $shortChunk[$i] = $text;
            }
        }

        if (empty($shortChunk)) {
            return $result;
        }

        $prompt = $this->buildBatchPrompt($shortChunk, $targetLang, $sourceLang);

        try {
            $raw  = $this->sendChatRequest($prompt);
            $json = $this->extractJson($raw);

            if (is_array($json)) {
                $jsonValues = array_values($json);
                $pos        = 0;

                foreach ($shortChunk as $i => $text) {
                    $result[$i] = (string) ($json[$i] ?? $jsonValues[$pos] ?? $text);
                    $pos++;
                }

                return $result;
            }
        } catch (\Throwable $e) {
            // Fall through to individual translation
        }

        // Fallback: translate individually
        foreach ($shortChunk as $i => $text) {
            $result[$i] = $this->sendChatRequest(
                $this->buildSinglePrompt((string) $text, $targetLang, $sourceLang)
            );
        }

        return $result;
    }

    private function sendChatRequest(string $userMessage): string
    {
        $timeout = (int) $this->getConfig('timeout', 120);

        $runtime = new RuntimeConfig();
        $context = $runtime->get('context', '')
            ?: config('ai-translator.options.context', '');

        $sysMsg = 'You are a professional translator. Follow all instructions exactly.';
        if (!empty(trim((string) $context))) {
            $sysMsg .= '\n\nContext about this application: ' . trim($context);
        }

        $response = Http::timeout($timeout)
            ->post($this->baseUrl() . '/v1/chat/completions', [
                'model'    => $this->model(),
                'messages' => [
                    ['role' => 'system', 'content' => $sysMsg],
                    ['role' => 'user',   'content' => $userMessage],
                ],
                'stream'      => false,
                'temperature' => 0.1,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException(
                "Ollama API error [{$response->status()}]: " . $response->body()
            );
        }

        return trim($response->json('choices.0.message.content', ''));
    }

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
     * Build a batch translation prompt.
     *
     * The "Output JSON:" ending anchors the model to start its response
     * with { directly — reduces preamble and markdown wrapping,
     * meaning extractJson() succeeds more often and the individual fallback
     * is hit less frequently.
     */
    private function buildBatchPrompt(array $texts, string $targetLang, string $sourceLang): string
    {
        $targetName = $this->languageName($targetLang);
        $input      = json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
You are a professional translator. Translate the JSON values from {$sourceLang} to {$targetName}.

STRICT RULES:
1. Return ONLY a valid JSON object — no markdown, no code blocks, no explanations.
2. Keep the same numeric keys.
3. Preserve markers like ___PLACEHOLDER_0___ or ___TAG_0___ exactly as they appear.
4. Do NOT add extra fields or change the structure.

Input JSON:
{$input}

Output JSON:
PROMPT;
    }

    private function extractJson(string $raw): ?array
    {
        // Strip markdown code fences if present
        $clean = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $clean = preg_replace('/```\s*$/m', '', $clean ?? $raw);
        $clean = trim($clean ?? $raw);

        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.+\}/s', $clean, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/\[.+\]/s', $clean, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function baseUrl(): string
    {
        $url = $this->getConfig('api_url', 'http://localhost:11434');
        return rtrim(str_replace('/v1', '', $url), '/');
    }

    private function model(): string
    {
        return $this->getConfig('model', 'llama3');
    }

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
