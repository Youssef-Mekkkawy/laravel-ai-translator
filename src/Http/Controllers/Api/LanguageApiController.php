<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;
use Illuminate\Http\Request;

class LanguageApiController extends DashboardController
{
    protected RuntimeConfig $runtime;

    public function __construct()
    {
        $this->runtime = new RuntimeConfig();
    }

    public function add(Request $request)
    {
        $locale = preg_replace('/[^a-z\-]/', '', strtolower($request->input('locale', '')));

        if (!$locale) {
            return $this->error('Invalid locale code.');
        }

        $current = $this->runtime->getSupportedLanguages();

        if (in_array($locale, $current)) {
            return $this->error("Language '{$locale}' is already configured.");
        }

        $this->runtime->addLanguage($locale);

        return $this->success([
            'locale'    => $locale,
            'languages' => $this->runtime->getSupportedLanguages(),
        ], "Language '{$locale}' added. Run translate to generate the files.");
    }

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

        $this->runtime->setDisabled($locale, !$enabled);

        return $this->success([
            'locale'  => $locale,
            'enabled' => $enabled,
        ], "Language '{$locale}' " . ($enabled ? 'enabled' : 'disabled') . '.');
    }

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

        $this->runtime->removeLanguage($locale);

        return $this->success([
            'locale'    => $locale,
            'languages' => $this->runtime->getSupportedLanguages(),
        ], "Language '{$locale}' removed.");
    }

    public function stats()
    {
        try {
            $config     = config('ai-translator', []);
            $sourceLang = $config['default_language'] ?? 'en';
            $languages  = array_filter(
                $this->runtime->getSupportedLanguages(),
                fn ($l) => $l !== $sourceLang
            );

            $runtimePaths = $this->runtime->get('scan_paths', $config['scan_paths'] ?? [resource_path('views')]);
            $runtimeExts  = $this->runtime->get('scan_extensions', ['blade.php']);
            $scanPaths    = array_filter((array) $runtimePaths, fn ($p) => is_dir($p));

            $scanner     = new ViewScanner(array_values($scanPaths), null, $runtimeExts);
            $scannedKeys = $scanner->scanAll();

            $sourceLangJson = lang_path($sourceLang . '.json');
            $jsonSourceKeys = [];
            if (file_exists($sourceLangJson)) {
                $jsonData = @json_decode(file_get_contents($sourceLangJson), true);
                if (is_array($jsonData)) {
                    $jsonSourceKeys = array_keys($jsonData);
                }
            }

            $allKeys   = array_unique(array_merge($scannedKeys, $jsonSourceKeys));
            $totalKeys = count($allKeys);

            $coverage    = [];
            $langCount   = count($languages);

            $storage      = new LockStorage();
            $lockManager  = new LockManager($storage);
            $lockedCount  = 0;
            $lockedByLang = [];
            foreach ($lockManager->getAll() as $lLang => $llocks) {
                if (is_array($llocks)) {
                    $lockedCount          += count($llocks);
                    $lockedByLang[$lLang]  = array_keys($llocks);
                }
            }

            foreach ($languages as $lang) {
                $langPath   = lang_path($lang);
                $langKeys   = $this->loadLangKeys($langPath);
                $lockedKeys = $lockedByLang[$lang] ?? [];
                $missing    = count(array_filter(
                    array_diff($allKeys, array_keys($langKeys)),
                    fn ($k) => !in_array($k, $lockedKeys)
                ));
                $translated = $totalKeys - $missing;
                $pct        = $totalKeys > 0 ? round(($translated / $totalKeys) * 100) : 0;
                $coverage[] = compact('lang', 'translated', 'missing', 'pct');
            }

            // Only average languages that have been translated at least once
            $activeCoverage  = array_filter($coverage, fn ($c) => $c['translated'] > 0);
            $translatedCount = count($activeCoverage) > 0
                ? (int) round(array_sum(array_column($activeCoverage, 'translated')) / count($activeCoverage))
                : 0;

            // FIX: only sum missing from languages that have STARTED translation
            // Brand-new languages with 0 translated keys are excluded — they show
            // as "not started" in coverage, not as "missing" in the aggregate
            $missingCount = array_sum(array_column(
                array_filter($coverage, fn ($c) => $c['translated'] > 0),
                'missing'
            ));

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
        $keys = [];

        if (is_dir($langPath)) {
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
        }

        $locale   = basename($langPath);
        $jsonPath = lang_path($locale . '.json');
        if (file_exists($jsonPath)) {
            $json = @json_decode(file_get_contents($jsonPath), true);
            if (is_array($json)) {
                foreach (array_keys($json) as $key) {
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
