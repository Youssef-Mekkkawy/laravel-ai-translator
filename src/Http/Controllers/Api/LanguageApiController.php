<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class LanguageApiController extends DashboardController
{
    /**
     * Toggle a language on/off in SUPPORTED_LANGUAGES.
     */
    public function toggle(Request $request)
    {
        $locale  = preg_replace('/[^a-z\-]/', '', strtolower($request->input('locale', '')));
        $enabled = $request->boolean('enabled');

        if (!$locale) {
            return $this->error('Invalid locale code.');
        }

        $source  = config('ai-translator.default_language', 'en');
        $current = config('ai-translator.languages', []);

        if ($locale === $source) {
            return $this->error("Cannot disable the source language '{$source}'.");
        }

        if ($enabled && !in_array($locale, $current)) {
            $current[] = $locale;
        } elseif (!$enabled) {
            $current = array_values(array_filter($current, fn ($l) => $l !== $locale));
        }

        $newLangList = implode(',', $current);
        $envPath     = base_path('.env');

        if (!File::exists($envPath)) {
            return $this->error('.env file not found.');
        }

        $env = File::get($envPath);
        if (preg_match('/^SUPPORTED_LANGUAGES=/m', $env)) {
            $env = preg_replace('/^SUPPORTED_LANGUAGES=.*/m', "SUPPORTED_LANGUAGES={$newLangList}", $env);
        } else {
            $env .= "\nSUPPORTED_LANGUAGES={$newLangList}";
        }

        File::put($envPath, $env);
        Artisan::call('config:clear');

        return $this->success([
            'locale'    => $locale,
            'enabled'   => $enabled,
            'languages' => $current,
        ], "Language '{$locale}' " . ($enabled ? 'enabled' : 'disabled') . '.');
    }

    /**
     * Add a new language — updates .env only.
     * Translation is triggered separately to avoid HTTP timeout.
     */
    public function add(Request $request)
    {
        $locale = preg_replace('/[^a-z\-]/', '', strtolower($request->input('locale', '')));

        if (!$locale) {
            return $this->error('Invalid locale code.');
        }

        // Get current languages
        $current = config('ai-translator.languages', []);

        if (in_array($locale, $current)) {
            return $this->error("Language '{$locale}' is already configured.");
        }

        // Add to list
        $current[]   = $locale;
        $newLangList = implode(',', $current);

        // Update .env
        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return $this->error('.env file not found.');
        }

        $env = File::get($envPath);
        if (preg_match('/^SUPPORTED_LANGUAGES=/m', $env)) {
            $env = preg_replace('/^SUPPORTED_LANGUAGES=.*/m', "SUPPORTED_LANGUAGES={$newLangList}", $env);
        } else {
            $env .= "\nSUPPORTED_LANGUAGES={$newLangList}";
        }
        File::put($envPath, $env);

        // Clear config cache
        Artisan::call('config:clear');

        return $this->success([
            'locale'    => $locale,
            'languages' => $current,
            'translate' => $request->boolean('translate', true),
        ], "Language '{$locale}' added. Run translate to generate the files.");
    }

    /**
     * Return current dashboard stats for live refresh.
     */
    public function stats()
    {
        try {
            $config     = config('ai-translator', []);
            $sourceLang = $config['default_language'] ?? 'en';
            $languages  = array_filter($config['languages'] ?? [], fn ($l) => $l !== $sourceLang);
            $scanPaths  = array_filter($config['scan_paths'] ?? [resource_path('views')], fn ($p) => is_dir($p));

            $scanner   = new ViewScanner(array_values($scanPaths));
            $allKeys   = $scanner->scanAll();
            $totalKeys = count($allKeys);

            $translatedCount = 0;
            $missingCount    = 0;
            $coverage        = [];
            $langCount       = count($languages);

            foreach ($languages as $lang) {
                $langPath   = lang_path($lang);
                $langKeys   = $this->loadLangKeys($langPath);
                $missing    = count(array_diff($allKeys, array_keys($langKeys)));
                $translated = $totalKeys - $missing;
                $pct        = $totalKeys > 0 ? round(($translated / $totalKeys) * 100) : 0;

                $coverage[]       = compact('lang', 'translated', 'missing', 'pct');
                $missingCount    += $missing;
            }

            $translatedCount = $langCount > 0
                ? (int) round(array_sum(array_column($coverage, 'translated')) / max($langCount, 1))
                : 0;

            $storage     = new LockStorage();
            $lockManager = new LockManager($storage);
            $lockedCount = 0;
            foreach ($lockManager->getAll() as $langLocks) {
                if (is_array($langLocks)) $lockedCount += count($langLocks);
            }

            return $this->success([
                'totalKeys'       => $totalKeys,
                'translatedCount' => $translatedCount,
                'missingCount'    => $missingCount,
                'lockedCount'     => $lockedCount,
                'coverage'        => $coverage,
            ]);

        } catch (\Throwable $e) {
            return $this->error('Stats failed: ' . $e->getMessage());
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function loadLangKeys(string $langPath): array
    {
        if (!is_dir($langPath)) return [];
        $keys  = [];
        $files = glob($langPath . DIRECTORY_SEPARATOR . '*.php') ?: [];
        foreach ($files as $file) {
            $group = pathinfo($file, PATHINFO_FILENAME);
            $data  = @include $file;
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
            if (is_array($v)) $result = array_merge($result, $this->flatten($v, $fk));
            else $result[$fk] = $v;
        }
        return $result;
    }
}
