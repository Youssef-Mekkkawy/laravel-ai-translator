<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;

class CleanController extends DashboardController
{
    /**
     * Scan for unused keys — returns list without deleting.
     */
    public function scan()
    {
        try {
            $config     = config('ai-translator', []);
            $sourceLang = $config['default_language'] ?? 'en';
            $scanPaths  = array_filter(
                $config['scan_paths'] ?? [resource_path('views')],
                fn ($p) => is_dir($p)
            );

            // Get used keys from views
            $scanner  = new ViewScanner(array_values($scanPaths));
            $usedKeys = array_flip($scanner->scanAll());

            // Also add JSON source keys as used
            $jsonPath = lang_path($sourceLang . '.json');
            if (File::exists($jsonPath)) {
                $json = @json_decode(File::get($jsonPath), true);
                if (is_array($json)) {
                    foreach (array_keys($json) as $key) {
                        $usedKeys[$key] = true;
                    }
                }
            }

            // Load all source lang keys
            $sourceLangPath = lang_path($sourceLang);
            $allKeys        = $this->loadAllKeys($sourceLangPath);

            // Find unused
            $unused = [];
            foreach ($allKeys as $fullKey => $meta) {
                if (!isset($usedKeys[$fullKey])) {
                    $unused[] = [
                        'key'  => $fullKey,
                        'file' => $meta['file'],
                    ];
                }
            }

            return $this->success([
                'unused'     => $unused,
                'total'      => count($allKeys),
                'used'       => count($usedKeys),
                'unusedCount'=> count($unused),
            ]);

        } catch (\Throwable $e) {
            return $this->error('Scan failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete specific unused keys from all language files.
     */
    public function delete(Request $request)
    {
        $keys       = $request->input('keys', []);
        $config     = config('ai-translator', []);
        $sourceLang = $config['default_language'] ?? 'en';
        $languages  = $config['languages'] ?? ['en'];

        if (empty($keys)) {
            return $this->error('No keys provided.');
        }

        $deleted = 0;

        foreach ($keys as $keyData) {
            $file      = $keyData['file'] ?? '';
            $fullKey   = $keyData['key'] ?? '';
            $nestedKey = str_contains($fullKey, '.') ? substr($fullKey, strlen($file) + 1) : $fullKey;

            foreach ($languages as $lang) {
                $filePath = lang_path("{$lang}/{$file}.php");

                if (!File::exists($filePath)) {
                    continue;
                }

                $translations = @include $filePath;
                if (!is_array($translations)) {
                    continue;
                }

                $keyParts     = explode('.', $nestedKey);
                $translations = $this->removeNestedKey($translations, $keyParts);

                File::put($filePath, $this->generatePhpFile($translations));
                $deleted++;
            }
        }

        return $this->success([
            'deleted' => count($keys),
            'files'   => $deleted,
        ], count($keys) . ' key(s) deleted from all language files.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function loadAllKeys(string $langPath): array
    {
        $keys = [];

        if (!File::exists($langPath)) {
            return $keys;
        }

        foreach (File::files($langPath) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $group        = $file->getBasename('.php');
            $translations = @include $file->getPathname();

            if (!is_array($translations)) {
                continue;
            }

            foreach ($this->flattenKeys($translations, $group) as $fullKey => $nestedKey) {
                $keys[$fullKey] = ['file' => $group, 'key' => $nestedKey];
            }
        }

        return $keys;
    }

    private function flattenKeys(array $array, string $prefix = '', string $keyPrefix = ''): array
    {
        $result = [];
        foreach ($array as $k => $v) {
            $fullKey   = $prefix ? "{$prefix}.{$k}" : $k;
            $nestedKey = $keyPrefix ? "{$keyPrefix}.{$k}" : $k;
            if (is_array($v)) {
                $result = array_merge($result, $this->flattenKeys($v, $fullKey, $nestedKey));
            } else {
                $result[$fullKey] = $nestedKey;
            }
        }
        return $result;
    }

    private function removeNestedKey(array $array, array $keys): array
    {
        $key = array_shift($keys);
        if (empty($keys)) {
            unset($array[$key]);
        } elseif (isset($array[$key]) && is_array($array[$key])) {
            $array[$key] = $this->removeNestedKey($array[$key], $keys);
            if (empty($array[$key])) unset($array[$key]);
        }
        return $array;
    }

    private function generatePhpFile(array $translations): string
    {
        return "<?php\n\nreturn " . $this->arrayToPhp($translations) . ";\n";
    }

    private function arrayToPhp(array $array, int $depth = 0): string
    {
        if (empty($array)) return '[]';
        $indent     = str_repeat('    ', $depth);
        $nextIndent = str_repeat('    ', $depth + 1);
        $lines      = ['['];
        foreach ($array as $key => $value) {
            $k = "'" . addslashes((string) $key) . "'";
            if (is_array($value)) {
                $lines[] = "{$nextIndent}{$k} => " . $this->arrayToPhp($value, $depth + 1) . ',';
            } else {
                $lines[] = "{$nextIndent}{$k} => '" . addslashes((string) $value) . "',";
            }
        }
        $lines[] = "{$indent}]";
        return implode("\n", $lines);
    }
}
