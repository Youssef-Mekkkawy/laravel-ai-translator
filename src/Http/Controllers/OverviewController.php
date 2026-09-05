<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers;

use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\MetadataManager;
use Illuminate\Support\Facades\File;

class OverviewController extends DashboardController
{
    public function index()
    {
        $config     = config('ai-translator', []);
        $sourceLang = $config['default_language'] ?? 'en';
        $languages  = array_filter($config['languages'] ?? [], fn ($l) => $l !== $sourceLang);

        // Scan for total keys
        $scanPaths = $config['scan_paths'] ?? [resource_path('views')];
        $scanner   = new ViewScanner(array_filter($scanPaths, fn ($p) => is_dir($p)));
        $allKeys   = $scanner->scanAll();
        $totalKeys = count($allKeys);

        // Count translated vs missing per language
        $translatedCount = 0;
        $missingCount    = 0;
        $coverage        = [];

        foreach ($languages as $lang) {
            $langPath    = lang_path($lang);
            $langKeys    = $this->loadLangKeys($langPath);
            $missing     = count(array_diff($allKeys, array_keys($langKeys)));
            $translated  = $totalKeys - $missing;
            $pct         = $totalKeys > 0 ? round(($translated / $totalKeys) * 100) : 0;

            $coverage[]       = compact('lang', 'translated', 'missing', 'pct');
            $translatedCount  += $translated;
            $missingCount     += $missing;
        }

        // Locked keys count
        $storage     = new LockStorage();
        $lockManager = new LockManager($storage);
        $lockedCount = $lockManager->count();

        // Last sync info from metadata
        $metaFile    = $config['storage']['metadata_file'] ?? base_path('lang/.translations-meta.json');
        $lastSync    = null;
        $lastDuration = null;

        if (File::exists($metaFile)) {
            $meta      = json_decode(File::get($metaFile), true) ?? [];
            $lastSync  = $meta['last_full_sync'] ?? null;
        }

        return $this->view('overview', [
            'totalKeys'       => $totalKeys,
            'translatedCount' => $translatedCount,
            'missingCount'    => $missingCount,
            'lockedCount'     => $lockedCount,
            'coverage'        => $coverage,
            'lastSync'        => $lastSync,
            'lastDuration'    => $lastDuration,
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
