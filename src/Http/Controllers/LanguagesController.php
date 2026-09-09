<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers;

use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;

class LanguagesController extends DashboardController
{
    public function index()
    {
        $config = config('ai-translator', []);
        $sourceLang = $config['default_language'] ?? 'en';
        $runtime = new RuntimeConfig;
        $allLangs = $runtime->getSupportedLanguages();

        $scanPaths = $config['scan_paths'] ?? [resource_path('views')];
        $scanner = new ViewScanner(array_filter($scanPaths, fn ($p) => is_dir($p)));
        $allKeys = $scanner->scanAll();
        $totalKeys = count($allKeys);

        $languages = [];

        foreach ($allLangs as $lang) {
            if ($lang === $sourceLang) {
                continue;
            }

            $langPath = lang_path($lang);
            $exists = is_dir($langPath);
            $langKeys = $exists ? $this->loadLangKeys($langPath) : [];
            $missing = $totalKeys - count(array_intersect($allKeys, array_keys($langKeys)));
            $translated = $totalKeys - $missing;
            $pct = $totalKeys > 0 ? round(($translated / $totalKeys) * 100) : 0;

            // "enabled" = not in runtime disabled list
            $isEnabled = ! $runtime->isDisabled($lang);
            $languages[] = [
                'code' => $lang,
                'name' => $this->languageName($lang),
                'nativeName' => $this->nativeName($lang),
                'enabled' => $isEnabled,
                'exists' => $exists,
                'totalKeys' => $totalKeys,
                'translated' => $translated,
                'missing' => $missing,
                'pct' => $pct,
            ];
        }

        return $this->view('languages', compact('languages', 'totalKeys'));
    }

    private function loadLangKeys(string $langPath): array
    {
        $keys = [];
        $files = glob($langPath.DIRECTORY_SEPARATOR.'*.php') ?: [];
        foreach ($files as $file) {
            $group = pathinfo($file, PATHINFO_FILENAME);
            $data = @include $file;
            if (is_array($data)) {
                foreach (array_keys($this->flatten($data, $group)) as $key) {
                    $keys[$key] = true;
                }
            }
        }

        return $keys;
    }

    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $k => $v) {
            $fk = $prefix ? "{$prefix}.{$k}" : $k;
            if (is_array($v)) {
                $result = array_merge($result, $this->flatten($v, $fk));
            } else {
                $result[$fk] = $v;
            }
        }

        return $result;
    }

    private function languageName(string $code): string
    {
        $names = [
            'ar' => 'Arabic', 'fr' => 'French', 'es' => 'Spanish', 'de' => 'German',
            'it' => 'Italian', 'pt' => 'Portuguese', 'ru' => 'Russian', 'zh' => 'Chinese',
            'ja' => 'Japanese', 'ko' => 'Korean', 'tr' => 'Turkish', 'nl' => 'Dutch',
            'pl' => 'Polish', 'hi' => 'Hindi', 'sv' => 'Swedish', 'da' => 'Danish',
        ];

        return $names[$code] ?? strtoupper($code);
    }

    private function nativeName(string $code): string
    {
        $names = [
            'ar' => 'العربية', 'fr' => 'Français', 'es' => 'Español', 'de' => 'Deutsch',
            'it' => 'Italiano', 'pt' => 'Português', 'ru' => 'Русский', 'zh' => '中文',
            'ja' => '日本語', 'ko' => '한국어', 'tr' => 'Türkçe', 'nl' => 'Nederlands',
            'pl' => 'Polski', 'hi' => 'हिन्दी', 'sv' => 'Svenska', 'da' => 'Dansk',
        ];

        return $names[$code] ?? $code;
    }
}
