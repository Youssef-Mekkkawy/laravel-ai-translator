<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers;

use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;
use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;

class OverviewController extends DashboardController
{
    public function index()
    {
        $config     = config('ai-translator', []);
        $sourceLang = $config['default_language'] ?? 'en';

        // FIX 1: use RuntimeConfig so languages added via dashboard are included.
        // Previously used config('ai-translator.languages') which only reflects
        // the static config file — dashboard-added languages were invisible here.
        $runtime   = new RuntimeConfig();
        $languages = array_filter(
            $runtime->getSupportedLanguages(),
            fn ($l) => $l !== $sourceLang
        );

        // Scan views for keys
        $scanPaths = $config['scan_paths'] ?? [resource_path('views')];
        $scanner   = new ViewScanner(array_filter($scanPaths, fn ($p) => is_dir($p)));
        $allKeys   = $scanner->scanAll();

        // FIX 2: include JSON source keys (lang/en.json) in total.
        // Without this, 145 JSON keys always counted as missing because
        // loadLangKeys() only reads PHP files.
        $sourceLangJson = lang_path($sourceLang . '.json');
        if (file_exists($sourceLangJson)) {
            $jsonData = @json_decode(file_get_contents($sourceLangJson), true);
            if (is_array($jsonData)) {
                $allKeys = array_unique(array_merge($allKeys, array_keys($jsonData)));
            }
        }

        $totalKeys = count($allKeys);

        // Load locked keys
        $storage      = new LockStorage;
        $lockManager  = new LockManager($storage);
        $lockedCount  = 0;
        $lockedByLang = [];
        foreach ($lockManager->getAll() as $lLang => $langLocks) {
            if (is_array($langLocks)) {
                $lockedCount          += count($langLocks);
                $lockedByLang[$lLang]  = array_keys($langLocks);
            }
        }

        $coverage  = [];

        foreach ($languages as $lang) {
            $langPath   = lang_path($lang);
            $langKeys   = $this->loadLangKeys($langPath);
            $lockedKeys = $lockedByLang[$lang] ?? [];

            $missingKeys = array_filter(
                array_diff($allKeys, array_keys($langKeys)),
                fn ($k) => !in_array($k, $lockedKeys)
            );
            $missing    = count($missingKeys);
            $translated = $totalKeys - $missing;
            $pct        = $totalKeys > 0 ? round(($translated / $totalKeys) * 100) : 0;

            $coverage[] = compact('lang', 'translated', 'missing', 'pct');
        }

        // FIX 3: only average languages that have been translated at least once.
        // Brand-new languages with 0% were dragging the average down to near 0.
        $activeCoverage  = array_filter($coverage, fn ($c) => $c['translated'] > 0);
        $translatedCount = count($activeCoverage) > 0
            ? (int) round(array_sum(array_column($activeCoverage, 'translated')) / count($activeCoverage))
            : 0;

        // FIX 4: only sum missing from languages that have started translation.
        // Previously summed all languages — a brand-new untranslated language
        // with 149 missing was inflating the total to 720+.
        $missingCount = array_sum(array_column(
            array_filter($coverage, fn ($c) => $c['translated'] > 0),
            'missing'
        ));

        // Last sync info from metadata
        $metaFile = $config['storage']['metadata_file'] ?? base_path('lang/.translations-meta.json');
        $lastSync = null;

        if (File::exists($metaFile)) {
            $meta     = json_decode(File::get($metaFile), true) ?? [];
            $lastSync = $meta['last_full_sync'] ?? null;
        }

        return $this->view('overview', [
            'totalKeys'       => $totalKeys,
            'translatedCount' => $translatedCount,
            'missingCount'    => $missingCount,
            'lockedCount'     => $lockedCount,
            'coverage'        => $coverage,
            'lastSync'        => $lastSync,
            'lastDuration'    => null,
        ]);
    }

    private function loadLangKeys(string $langPath): array
    {
        if (!is_dir($langPath)) {
            return [];
        }

        $keys  = [];
        $files = glob($langPath . DIRECTORY_SEPARATOR . '*.php') ?: [];

        foreach ($files as $file) {
            $group = pathinfo($file, PATHINFO_FILENAME);
            $data  = @include $file;
            if (is_array($data)) {
                foreach (array_keys($this->flatten($data, $group)) as $key) {
                    $keys[$key] = true;
                }
            }
        }

        // FIX: also read JSON file (e.g. lang/ar.json).
        // Without this, all 145 JSON-translated keys always show as missing
        // because only PHP files were being read.
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
