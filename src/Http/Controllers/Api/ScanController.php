<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;

class ScanController extends DashboardController
{
    public function run()
    {
        try {
            $config    = config('ai-translator', []);
            $scanPaths = array_filter(
                $config['scan_paths'] ?? [resource_path('views')],
                fn ($p) => is_dir($p)
            );

            $scanner   = new ViewScanner(array_values($scanPaths));
            $extractor = new KeyExtractor();
            $keys      = $scanner->scanAll();

            $results = [];
            foreach ($keys as $key) {
                $parsed    = $extractor->extractFromKey($key);
                $results[] = [
                    'key'           => $key,
                    'file'          => $parsed['file'],
                    'default_value' => $parsed['default_value'],
                ];
            }

            return $this->success([
                'total' => count($keys),
                'keys'  => $results,
            ], 'Scan complete.');

        } catch (\Throwable $e) {
            return $this->error('Scan failed: ' . $e->getMessage());
        }
    }
}
