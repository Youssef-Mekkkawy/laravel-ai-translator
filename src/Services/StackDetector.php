<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services;

use Illuminate\Support\Facades\File;

class StackDetector
{
    /**
     * Detect the Laravel stack and return full detection result.
     */
    public function detect(): array
    {
        $composer    = $this->readComposer();
        $require     = array_merge(
            $composer['require']     ?? [],
            $composer['require-dev'] ?? []
        );

        // ── Detect packages ───────────────────────────────────────────
        $hasBreeze    = isset($require['laravel/breeze']);
        $hasJetstream = isset($require['laravel/jetstream']);
        $hasLivewire  = isset($require['livewire/livewire']);
        $hasInertia   = isset($require['inertiajs/inertia-laravel']);

        // ── Detect frontend files ──────────────────────────────────────
        $hasVue       = $this->hasFiles('resources/js', ['*.vue'], true);
        $hasReact     = $this->hasFiles('resources/js', ['*.jsx', '*.tsx'], true);
        $hasBlade     = $this->hasFiles('resources/views', ['*.blade.php'], true);
        $hasLivewireComponents = $this->hasFiles('app', ['*.php'], true, ['Livewire', 'Http/Livewire']);

        // ── Determine stack ───────────────────────────────────────────
        $stack        = $this->determineStack($hasInertia, $hasVue, $hasReact, $hasLivewire, $hasBlade);
        $starterKit   = $hasBreeze ? 'breeze' : ($hasJetstream ? 'jetstream' : 'none');

        // ── Determine scan paths and extensions ───────────────────────
        $scanConfig   = $this->buildScanConfig($stack, $hasLivewireComponents);

        // ── Determine default output format ───────────────────────────
        $outputFormat = $this->determineOutputFormat($stack);

        return [
            'stack'        => $stack,
            'starter_kit'  => $starterKit,
            'has_blade'    => $hasBlade,
            'has_livewire' => $hasLivewire,
            'has_vue'      => $hasVue,
            'has_react'    => $hasReact,
            'has_inertia'  => $hasInertia,
            'scan_paths'   => $scanConfig['paths'],
            'scan_extensions' => $scanConfig['extensions'],
            'output_format'=> $outputFormat,
            'detected_at'  => now()->toISOString(),
        ];
    }

    /**
     * Determine the primary stack name.
     */
    protected function determineStack(
        bool $hasInertia,
        bool $hasVue,
        bool $hasReact,
        bool $hasLivewire,
        bool $hasBlade
    ): string {
        if ($hasInertia && $hasVue)   return 'inertia+vue';
        if ($hasInertia && $hasReact) return 'inertia+react';
        if ($hasVue)                  return 'blade+vue';
        if ($hasReact)                return 'blade+react';
        if ($hasLivewire)             return 'blade+livewire';
        if ($hasBlade)                return 'blade';
        return 'blade'; // default fallback
    }

    /**
     * Build scan paths and extensions based on detected stack.
     */
    protected function buildScanConfig(string $stack, bool $hasLivewireComponents): array
    {
        $paths      = [resource_path('views')];
        $extensions = ['blade.php'];

        match (true) {
            str_contains($stack, 'vue') => (function () use (&$paths, &$extensions) {
                $paths[]      = resource_path('js');
                $extensions[] = 'vue';
            })(),

            str_contains($stack, 'react') => (function () use (&$paths, &$extensions) {
                $paths[]      = resource_path('js');
                $extensions[] = 'jsx';
                $extensions[] = 'tsx';
            })(),

            default => null,
        };

        // Add Livewire component paths
        if ($hasLivewireComponents) {
            $livewirePaths = [
                app_path('Livewire'),
                app_path('Http/Livewire'),
            ];
            foreach ($livewirePaths as $path) {
                if (File::exists($path)) {
                    $paths[] = $path;
                }
            }
        }

        return [
            'paths'      => array_filter($paths, fn ($p) => File::exists($p)),
            'extensions' => $extensions,
        ];
    }

    /**
     * Determine default output format based on stack.
     * Vue/React → JSON only (SPA frameworks read JSON)
     * Blade/Livewire → auto (decide per key format)
     */
    protected function determineOutputFormat(string $stack): string
    {
        if (str_contains($stack, 'vue') || str_contains($stack, 'react')) {
            return 'json';
        }
        return 'auto'; // blade and livewire decide per key
    }

    /**
     * Analyze scanned keys to determine if they are mostly PHP or JSON style.
     */
    public function analyzeKeyFormat(array $keys): string
    {
        if (empty($keys)) return 'auto';

        $phpKeys  = 0;
        $jsonKeys = 0;

        foreach ($keys as $key) {
            if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)+$/', $key)) {
                $phpKeys++;
            } else {
                $jsonKeys++;
            }
        }

        $total = count($keys);

        if ($phpKeys / $total >= 0.7)  return 'php';
        if ($jsonKeys / $total >= 0.7) return 'json';

        return 'auto'; // mixed — decide per key
    }

    /**
     * Get a human-readable description of the detected stack.
     */
    public function describe(array $result): string
    {
        return match ($result['stack']) {
            'blade'          => 'Blade (server-rendered)',
            'blade+livewire' => 'Blade + Livewire (reactive components)',
            'blade+vue'      => 'Blade + Vue.js',
            'blade+react'    => 'Blade + React',
            'inertia+vue'    => 'Inertia.js + Vue (SPA)',
            'inertia+react'  => 'Inertia.js + React (SPA)',
            default          => 'Laravel (unknown stack)',
        };
    }

    // ── Helpers ────────────────────────────────────────────────────────

    protected function readComposer(): array
    {
        $path = base_path('composer.json');
        if (!File::exists($path)) return [];
        try {
            return json_decode(File::get($path), true) ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function hasFiles(
        string $dir,
        array $patterns,
        bool $recursive = false,
        array $subDirs = []
    ): bool {
        $searchDirs = empty($subDirs)
            ? [base_path($dir)]
            : array_map(fn ($sub) => base_path($dir . '/' . $sub), $subDirs);

        foreach ($searchDirs as $searchDir) {
            if (!File::exists($searchDir)) continue;

            foreach ($patterns as $pattern) {
                $ext   = ltrim($pattern, '*.');
                $files = $recursive
                    ? File::allFiles($searchDir)
                    : File::files($searchDir);

                foreach ($files as $file) {
                    if (str_ends_with($file->getFilename(), '.' . $ext)
                        || str_ends_with($file->getFilename(), $ext)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
