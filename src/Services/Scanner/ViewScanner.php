<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Scanner;

use Illuminate\Support\Facades\File;

class ViewScanner
{
    /**
     * Paths to scan for blade files.
     *
     * @var array
     */
    protected array $paths;

    /**
     * Patterns to exclude from scanning.
     *
     * @var array
     */
    protected array $excludePatterns;

    /**
     * Statistics about the scan.
     *
     * @var array
     */
    protected array $statistics = [
        'total_files' => 0,
        'total_keys' => 0,
        'files_scanned' => [],
    ];

    /**
     * Whether a scan has been performed.
     *
     * @var bool
     */
    protected bool $hasScanned = false;

    /**
     * Create a new ViewScanner instance.
     *
     * @param  array  $paths
     * @param  array|null  $excludePatterns
     */
    public function __construct(array $paths = [], ?array $excludePatterns = null)
    {
        $this->paths = empty($paths) 
            ? config('ai-translator.scan_paths', [resource_path('views')]) 
            : $paths;
            
        // Only use config defaults if NO exclude patterns were explicitly passed
        // If empty array is passed, use that (allows disabling exclusions)
        if ($excludePatterns === null && empty($paths)) {
            $configExcludes = config('ai-translator.exclude_files', []);
            $this->excludePatterns = is_array($configExcludes) ? $configExcludes : [];
        } else {
            $this->excludePatterns = $excludePatterns ?? [];
        }
    }

    /**
     * Scan for all blade files in configured paths.
     *
     * @return array
     */
    public function scanForBladeFiles(): array
    {
        $bladeFiles = [];

        foreach ($this->paths as $path) {
            if (!File::exists($path)) {
                continue;
            }

            if (File::isFile($path)) {
                // Single file provided
                if (str_ends_with($path, '.blade.php')) {
                    $bladeFiles[] = $path;
                }
                continue;
            }

            // Directory - scan recursively
            $files = File::allFiles($path);

            foreach ($files as $file) {
                $filepath = $file->getPathname();

                // Skip if matches exclude pattern
                if ($this->shouldExclude($filepath)) {
                    continue;
                }

                // Only include .blade.php files
                if (str_ends_with($file->getFilename(), '.blade.php')) {
                    $bladeFiles[] = str_replace('\\', '/', $filepath);
                }
            }
        }

        return $bladeFiles;
    }

    /**
     * Scan a single file for translation keys.
     *
     * @param  string  $filepath
     * @return array
     */
    public function scanFile(string $filepath): array
    {
        if (!File::exists($filepath)) {
            return [];
        }

        $content = File::get($filepath);
        $keys = $this->extractKeysFromContent($content);

        // Remove duplicates
        return array_unique($keys);
    }

    /**
     * Scan all configured paths for translation keys.
     *
     * @return array
     */
    public function scanAll(): array
    {
        $files = $this->scanForBladeFiles();
        $allKeys = [];

        foreach ($files as $file) {
            $keys = $this->scanFile($file);
            $allKeys = array_merge($allKeys, $keys);
            $this->statistics['files_scanned'][] = $file;
        }

        // Remove duplicates and update statistics
        $allKeys = array_unique($allKeys);
        $this->statistics['total_files'] = count($files);
        $this->statistics['total_keys'] = count($allKeys);
        $this->hasScanned = true;

        return $allKeys;
    }

    /**
     * Scan a specific directory path for translation keys.
     * This method is used by commands that need to scan custom paths.
     *
     * @param  string  $path
     * @return array
     */
    public function scan(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }

        // Temporarily set path and scan
        $originalPaths = $this->paths;
        $this->paths = [$path];
        
        $keys = $this->scanAll();
        
        // Restore original paths
        $this->paths = $originalPaths;

        return $keys;
    }

    /**
     * Get statistics about the scan.
     * If no scan has been performed yet, automatically trigger one.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        // Auto-scan if not scanned yet
        if (!$this->hasScanned) {
            $this->scanAll();
        }

        return $this->statistics;
    }

    /**
     * Check if a filepath should be excluded.
     *
     * @param  string  $filepath
     * @return bool
     */
    protected function shouldExclude(string $filepath): bool
    {
        // If no exclude patterns, don't exclude anything
        if (empty($this->excludePatterns)) {
            return false;
        }

        // Normalize path separators for consistent matching
        $filepath = str_replace('\\', '/', $filepath);

        foreach ($this->excludePatterns as $pattern) {
            // Normalize pattern
            $pattern = str_replace('\\', '/', trim($pattern));
            
            if (empty($pattern)) {
                continue;
            }

            // Remove ** wildcards for simple matching
            $simplePattern = str_replace('**', '', $pattern);
            $simplePattern = str_replace('*', '', $simplePattern);
            $simplePattern = trim($simplePattern, '/');

            // Check if the simple pattern appears anywhere in the path
            if (!empty($simplePattern) && str_contains($filepath, $simplePattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract translation keys from file content.
     *
     * @param  string  $content
     * @return array
     */
    protected function extractKeysFromContent(string $content): array
    {
        $keys = [];

        // Pattern 1: __('key') or __("key")
        preg_match_all('/__\([\'"]([^\'"]+)[\'"]\)/', $content, $matches1);
        $keys = array_merge($keys, $matches1[1]);

        // Pattern 2: @lang('key') or @lang("key")
        preg_match_all('/@lang\([\'"]([^\'"]+)[\'"]\)/', $content, $matches2);
        $keys = array_merge($keys, $matches2[1]);

        // Pattern 3: trans('key') or trans("key")
        preg_match_all('/trans\([\'"]([^\'"]+)[\'"]\)/', $content, $matches3);
        $keys = array_merge($keys, $matches3[1]);

        return $keys;
    }
}