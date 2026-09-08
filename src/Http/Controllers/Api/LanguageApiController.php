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
     * Toggle enable/disable using DISABLED_LANGUAGES — does NOT remove from config.
     */
    public function toggle(Request $request)
    {
        $locale  = preg_replace('/[^a-z\-]/', '', strtolower($request->input('locale', '')));
        $enabled = $request->boolean('enabled');

        if (!$locale) {
            return $this->error('Invalid locale code.');
        }

        $source = config('ai-translator.default_language', 'en');
        if ($locale === $source) {
            return $this->error("Cannot disable the source language '{$source}'.");
        }

        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return $this->error('.env file not found.');
        }

        $env      = File::get($envPath);
        $disabled = array_filter(
            explode(',', env('DISABLED_LANGUAGES', '')),
            fn ($l) => !empty(trim($l))
        );

        if (!$enabled) {
            $disabled[] = $locale;
        } else {
            $disabled = array_filter($disabled, fn ($l) => $l !== $locale);
        }

        $disabled    = array_unique(array_values($disabled));
        $disabledStr = implode(',', $disabled);

        if (preg_match('/^DISABLED_LANGUAGES=/m', $env)) {
            $env = preg_replace('/^DISABLED_LANGUAGES=.*/m', "DISABLED_LANGUAGES={$disabledStr}", $env);
        } else {
            $env .= PHP_EOL . "DISABLED_LANGUAGES={$disabledStr}";
        }

        File::put($envPath, $env);
        Artisan::call('config:clear');

        return $this->success([
            'locale'  => $locale,
            'enabled' => $enabled,
        ], "Language '{$locale}' " . ($enabled ? 'enabled' : 'disabled') . '.');
    }

    /**
     * Remove a language completely from SUPPORTED_LANGUAGES.
     */
    public function remove(Request $request)
    {
        $locale = preg_replace('/[^a-z\-]/', '', strtolower($request->input('locale', '')));
        $source = config('ai-translator.default_language', 'en');

        if (!$locale) {
            return $this->error('Invalid locale code.');
        }

        if ($locale === $source) {
            return $this->error("Cannot remove the source language '{$source}'.");
        }

        $current = array_values(array_filter(
            config('ai-translator.languages', []),
            fn ($l) => $l !== $locale
        ));

        $envPath = base_path('.env');
        if (!File::exists($envPath)) {
            return $this->error('.env file not found.');
        }

        $env = File::get($envPath);

        // Remove from SUPPORTED_LANGUAGES
        if (preg_match('/^SUPPORTED_LANGUAGES=/m', $env)) {
            $env = preg_replace('/^SUPPORTED_LANGUAGES=.*/m', 'SUPPORTED_LANGUAGES=' . implode(',', $current), $env);
        }

        // Also remove from DISABLED_LANGUAGES if present
        $disabled = array_filter(
            explode(',', env('DISABLED_LANGUAGES', '')),
            fn ($l) => !empty(trim($l)) && $l !== $locale
        );
        if (preg_match('/^DISABLED_LANGUAGES=/m', $env)) {
            $env = preg_replace('/^DISABLED_LANGUAGES=.*/m', 'DISABLED_LANGUAGES=' . implode(',', $disabled), $env);
        }

        File::put($envPath, $env);
        Artisan::call('config:clear');

        return $this->success([
            'locale'    => $locale,
            'languages' => $current,
        ], "Language '{$locale}' removed.");
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

            // Get locked keys per language
            $lockedByLang = [];
            foreach ($lockManager->getAll() as $lLang => $llocks) {
                if (is_array($llocks)) $lockedByLang[$lLang] = array_keys($llocks);
            }

            foreach ($languages as $lang) {
                $langPath   = lang_path($lang);
                $langKeys   = $this->loadLangKeys($langPath);
                $lockedKeys = $lockedByLang[$lang] ?? [];
                $missing    = count(array_filter(
                    array_diff($allKeys, array_keys($langKeys)),
                    fn($k) => !in_array($k, $lockedKeys)
                ));
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
