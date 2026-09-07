<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;

class DashboardLangController extends DashboardController
{
    /**
     * Set the dashboard language via cookie and redirect back.
     * For pre-translated languages: instant.
     * For new languages: generates via Ollama first.
     */
    public function setLang(string $locale)
    {
        // Sanitize locale
        $locale = preg_replace('/[^a-z\-]/', '', strtolower($locale));
        if (! $locale) {
            return redirect(url('ai-translator'));
        }

        $pkgPath = app('ai-translator.package_path');
        $langFile = $pkgPath.'/resources/lang/'.$locale.'/dashboard.php';
        $enFile = $pkgPath.'/resources/lang/en/dashboard.php';

        // Generate if not pre-translated
        if (! file_exists($langFile) && file_exists($enFile)) {
            $this->generateLang($locale, $langFile, $enFile);
        }

        return redirect(url('ai-translator'))
            ->withCookie(cookie('dashboard_lang', $locale, 60 * 24 * 365, '/'));
    }

    /**
     * Generate dashboard translations for a locale on demand via Ollama (AJAX).
     */
    public function generate(Request $request)
    {
        $locale = preg_replace('/[^a-z\-]/', '', strtolower($request->input('locale', '')));
        $pkgPath = app('ai-translator.package_path');
        $langFile = $pkgPath.'/resources/lang/'.$locale.'/dashboard.php';
        $enFile = $pkgPath.'/resources/lang/en/dashboard.php';

        if (! $locale) {
            return $this->error('Invalid locale.');
        }

        if (file_exists($langFile)) {
            return $this->success(['translations' => include $langFile, 'cached' => true]);
        }

        if (! file_exists($enFile)) {
            return $this->error('Base translations missing.');
        }

        $ok = $this->generateLang($locale, $langFile, $enFile);

        if (! $ok) {
            return $this->error('Generation failed. Make sure Ollama is running.');
        }

        return $this->success(['translations' => include $langFile, 'cached' => false]);
    }

    // ── Private ────────────────────────────────────────────────────────────

    private function generateLang(string $locale, string $langFile, string $enFile): bool
    {
        $enStrings = include $enFile;
        $ollamaUrl = config('ai-translator.providers.ollama.api_url', 'http://localhost:11434');
        $model = config('ai-translator.providers.ollama.model', 'llama3.2');
        $ollamaUrl = rtrim(str_replace('/v1', '', $ollamaUrl), '/');

        try {
            $prompt = "Translate the following JSON array of strings from English to the language with ISO code '{$locale}'.\n"
                    ."Return ONLY a valid JSON array of translated strings in the same order. No explanations, no markdown.\n\n"
                    .json_encode(array_values($enStrings), JSON_UNESCAPED_UNICODE);

            $response = Http::timeout(120)->post($ollamaUrl.'/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a professional translator. Return only a JSON array.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'stream' => false,
                'temperature' => 0.1,
            ]);

            $raw = $response->json('choices.0.message.content', '');
            $clean = trim(preg_replace(['/^```(?:json)?\s*/m', '/```\s*$/m'], '', $raw));
            $decoded = json_decode($clean, true);

            if (! is_array($decoded)) {
                return false;
            }

            $keys = array_keys($enStrings);
            $translations = [];
            foreach ($keys as $i => $key) {
                $translations[$key] = $decoded[$i] ?? $enStrings[$key];
            }

            $dir = dirname($langFile);
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($langFile, "<?php\n\nreturn ".var_export($translations, true).";\n");

            return true;

        } catch (\Throwable $e) {
            return false;
        }
    }
}
