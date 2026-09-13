<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Scanner;

use Illuminate\Support\Facades\File;

class ViewScanner
{
    protected array $paths;
    protected array $excludePatterns;
    protected array $extensions;
    protected array $statistics = [
        'total_files'   => 0,
        'total_keys'    => 0,
        'files_scanned' => [],
    ];
    protected bool $hasScanned = false;

    protected array $patterns = [
        'php' => [
            '/(?:__|trans|@lang|trans_choice)\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
        ],
        'vue' => [
            '/\$t\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
            '/\bt\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
        ],
        'jsx' => [
            '/(?:__|i18n\.t|t)\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
        ],
        'tsx' => [
            '/(?:__|i18n\.t|t)\s*\(\s*[\'"]([^\'"]+)[\'"]/u',
        ],
    ];

    public function __construct(
        array $paths            = [],
        ?array $excludePatterns = null,
        array $extensions       = ['blade.php']
    ) {
        $this->paths = empty($paths)
            ? config('ai-translator.scan_paths', [resource_path('views')])
            : $paths;

        if ($excludePatterns === null && empty($paths)) {
            $configExcludes        = config('ai-translator.exclude_files', []);
            $this->excludePatterns = is_array($configExcludes) ? $configExcludes : [];
        } else {
            $this->excludePatterns = $excludePatterns ?? [];
        }

        $this->extensions = $extensions;
    }

    /**
     * Scan all configured paths and return unique translation keys.
     */
    public function scanAll(): array
    {
        if ($this->hasScanned) {
            return array_unique($this->statistics['files_scanned']
                ? $this->getAllKeys()
                : []);
        }

        $allKeys = [];

        foreach ($this->paths as $path) {
            if (!File::exists($path)) continue;

            foreach (File::allFiles($path) as $file) {
                if (!$this->shouldScanFile($file)) continue;

                $this->statistics['total_files']++;
                $keys = $this->extractKeysFromFile($file);

                if (!empty($keys)) {
                    $this->statistics['files_scanned'][$file->getPathname()] = $keys;
                    $allKeys = array_merge($allKeys, $keys);
                }
            }
        }

        $allKeys = array_values(array_unique($allKeys));
        $this->statistics['total_keys'] = count($allKeys);
        $this->hasScanned               = true;

        return $allKeys;
    }

    /**
     * Return all blade/vue/jsx file paths found in the configured paths.
     * Respects exclude patterns and extension filters.
     * Used by tests and the Clean page.
     */
    public function scanForBladeFiles(): array
    {
        $files = [];

        foreach ($this->paths as $path) {
            if (!File::exists($path)) continue;

            foreach (File::allFiles($path) as $file) {
                if (!$this->shouldScanFile($file)) continue;
                $files[] = $file->getPathname();
            }
        }

        sort($files); // consistent, predictable ordering
        return $files;
    }

    /**
     * Extract translation keys from a single file by path.
     * Used by tests and callers that need per-file extraction.
     */
    public function scanFile(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }

        return $this->extractKeysFromFile(new \SplFileInfo($path));
    }

    /**
     * Return scanner statistics.
     * Auto-triggers a scan if scanAll() has not been called yet,
     * so tests can call getStatistics() directly.
     */
    public function getStatistics(): array
    {
        if (!$this->hasScanned) {
            $this->scanAll();
        }

        return $this->statistics;
    }

    // ── Protected helpers ──────────────────────────────────────────────────

    /**
     * Check if this file should be scanned.
     *
     * Extension check: must match one of the configured extensions.
     * Exclude check:   pattern matches full path, filename, OR any directory
     *                  component in the path — so 'vendor' excludes everything
     *                  inside a vendor/ subdirectory, not just files named vendor.
     */
    protected function shouldScanFile(\SplFileInfo $file): bool
    {
        $filename = $file->getFilename();
        $path     = $file->getPathname();

        // Extension check
        $matched = false;
        foreach ($this->extensions as $ext) {
            if (str_ends_with($filename, '.' . ltrim($ext, '.'))) {
                $matched = true;
                break;
            }
        }

        if (!$matched) return false;

        // Exclude pattern check
        // Normalize to forward slashes so str_contains works on Windows too
        $normalizedPath = str_replace('\\', '/', $path);

        foreach ($this->excludePatterns as $pattern) {
            // Glob match against full path or filename
            if (fnmatch($pattern, $path) || fnmatch($pattern, $filename)) {
                return false;
            }
            // Directory component match: 'vendor' excludes files inside vendor/ dirs
            if (str_contains($normalizedPath, '/' . $pattern . '/')) {
                return false;
            }
        }

        return true;
    }

    protected function extractKeysFromFile(\SplFileInfo $file): array
    {
        $content  = File::get($file->getPathname());
        $ext      = $this->getFileType($file);
        $patterns = $this->patterns[$ext] ?? $this->patterns['php'];
        $keys     = [];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $key) {
                    $key = trim($key);
                    if (!empty($key) && strlen($key) < 500) {
                        $keys[] = $key;
                    }
                }
            }
        }

        return array_values(array_unique($keys));
    }

    protected function getFileType(\SplFileInfo $file): string
    {
        $name = $file->getFilename();

        if (str_ends_with($name, '.blade.php')) return 'php';
        if (str_ends_with($name, '.vue'))       return 'vue';
        if (str_ends_with($name, '.jsx'))       return 'jsx';
        if (str_ends_with($name, '.tsx'))       return 'tsx';
        if (str_ends_with($name, '.php'))       return 'php';

        return 'php';
    }

    protected function getAllKeys(): array
    {
        $keys = [];
        foreach ($this->statistics['files_scanned'] as $fileKeys) {
            $keys = array_merge($keys, $fileKeys);
        }
        return array_unique($keys);
    }
}
