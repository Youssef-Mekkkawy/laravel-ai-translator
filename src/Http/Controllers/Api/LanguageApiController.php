<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use Illuminate\Http\Request;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;
use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;

class LanguageApiController extends DashboardController
{
    protected RuntimeConfig $runtime;

    public function __construct()
    {
        $this->runtime = new RuntimeConfig;
    }

    /**
     * Add a new language.
     */
    public function add(Request $request)
    {
        $locale = preg_replace('/[^a-z\-]/', '', strtolower($request->input('locale', '')));

        if (! $locale) {
            return $this->error('Invalid locale code.');
        }

        $current = $this->runtime->getSupportedLanguages();

        if (in_array($locale, $current)) {
            return $this->error("Language '{$locale}' is already configured.");
        }

        $this->runtime->addLanguage($locale);

        return $this->success([
            'locale' => $locale,
            'languages' => $this->runtime->getSupportedLanguages(),
        ], "Language '{$locale}' added. Run translate to generate the files.");
    }

    /**
     * Toggle a language on/off (enable/disable).
     */
    public function toggle(Request $request)
    {
        $locale = preg_replace('/[^a-z\-]/', '', strtolower($request->input('locale', '')));
        $enabled = $request->boolean('enabled');

        if (! $locale) {
            return $this->error('Invalid locale code.');
        }

        $source = config('ai-translator.default_language', 'en');
        if ($locale === $source) {
            return $this->error("Cannot disable the source language '{$source}'.");
        }

        $this->runtime->setDisabled($locale, ! $enabled);

        return $this->success([
            'locale' => $locale,
            'enabled' => $enabled,
        ], "Language '{$locale}' ".($enabled ? 'enabled' : 'disabled').'.');
    }

    /**
     * Remove a language completely.
     */
    public function remove(Request $request)
    {
        $locale = preg_replace('/[^a-z\-]/', '', strtolower($request->input('locale', '')));
        $source = config('ai-translator.default_language', 'en');

        if (! $locale) {
            return $this->error('Invalid locale code.');
        }

        if ($locale === $source) {
            return $this->error("Cannot remove the source language '{$source}'.");
        }

        $this->runtime->removeLanguage($locale);

        return $this->success([
            'locale' => $locale,
            'languages' => $this->runtime->getSupportedLanguages(),
        ], "Language '{$locale}' removed.");
    }

    /**
     * Return current dashboard stats for live refresh.
     */
    public function stats()
    {
        try {
            $config = config('ai-translator', []);
            $sourceLang = $config['default_language'] ?? 'en';
            $languages = array_filter(
                $this->runtime->getSupportedLanguages(),
                fn ($l) => $l !== $sourceLang
            );
            $scanPaths = array_filter(
                $config['scan_paths'] ?? [resource_path('views')],
                fn ($p) => is_dir($p)
            );

            $scanner = new ViewScanner(array_values($scanPaths));
            $allKeys = $scanner->scanAll();
            $totalKeys = count($allKeys);

            $translatedCount = 0;
            $missingCount = 0;
            $coverage = [];
            $langCount = count($languages);

            // Get locked keys
            $storage = new LockStorage;
            $lockManager = new LockManager($storage);
            $lockedCount = 0;
            $lockedByLang = [];
            foreach ($lockManager->getAll() as $lLang => $llocks) {
                if (is_array($llocks)) {
                    $lockedCount += count($llocks);
                    $lockedByLang[$lLang] = array_keys($llocks);
                }
            }

            foreach ($languages as $lang) {
                $langPath = lang_path($lang);
                $langKeys = $this->loadLangKeys($langPath);
                $lockedKeys = $lockedByLang[$lang] ?? [];
                $missing = count(array_filter(
                    array_diff($allKeys, array_keys($langKeys)),
                    fn ($k) => ! in_array($k, $lockedKeys)
                ));
                $translated = $totalKeys - $missing;
                $pct = $totalKeys > 0 ? round(($translated / $totalKeys) * 100) : 0;
                $coverage[] = compact('lang', 'translated', 'missing', 'pct');
                $missingCount += $missing;
            }

            $translatedCount = $langCount > 0
                ? (int) round(array_sum(array_column($coverage, 'translated')) / max($langCount, 1))
                : 0;

            return $this->success([
                'totalKeys' => $totalKeys,
                'translatedCount' => $translatedCount,
                'missingCount' => $missingCount,
                'lockedCount' => $lockedCount,
                'coverage' => $coverage,
            ]);

        } catch (\Throwable $e) {
            return $this->error('Stats failed: '.$e->getMessage());
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function loadLangKeys(string $langPath): array
    {
        if (! is_dir($langPath)) {
            return [];
        }
        $keys = [];
        $files = glob($langPath.DIRECTORY_SEPARATOR.'*.php') ?: [];
        foreach ($files as $file) {
            $group = pathinfo($file, PATHINFO_FILENAME);
            $data = @include $file;
            if (is_array($data)) {
                foreach ($this->flatten($data, $group) as $key => $_) {
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
}
