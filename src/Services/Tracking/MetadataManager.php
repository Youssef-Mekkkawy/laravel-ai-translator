<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Tracking;

use Illuminate\Support\Facades\File;

class MetadataManager
{
    protected string $metadataPath;

    public function __construct(?string $metadataPath = null)
    {
        $this->metadataPath = $metadataPath ?? lang_path('.translations-meta.json');
    }

    // ── Public interface used by ChangeTracker ────────────────────────────

    /**
     * Load full metadata structure (used by ChangeTracker).
     */
    public function load(): array
    {
        if (! File::exists($this->metadataPath)) {
            return $this->emptyStructure();
        }

        try {
            $data = json_decode(File::get($this->metadataPath), true);
            if (! is_array($data)) {
                return $this->emptyStructure();
            }
            // Ensure required keys exist
            if (! isset($data['hashes'])) {
                $data['hashes'] = [];
            }
            if (! isset($data['runs'])) {
                $data['runs'] = [];
            }

            return $data;
        } catch (\Throwable $e) {
            return $this->emptyStructure();
        }
    }

    /**
     * Save full metadata structure (used by ChangeTracker).
     */
    public function save(array $data): bool
    {
        try {
            $this->ensureDirectory();
            File::put(
                $this->metadataPath,
                json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ── Run history ───────────────────────────────────────────────────────

    /**
     * Record a completed translation run.
     */
    public function recordRun(array $runData): void
    {
        $data = $this->load();

        array_unshift($data['runs'], array_merge([
            'started_at' => now()->toISOString(),
            'status' => 'success',
            'keys_translated' => 0,
            'languages' => [],
            'provider' => config('ai-translator.driver', 'ollama'),
            'model' => config('ai-translator.providers.'.config('ai-translator.driver', 'ollama').'.model', ''),
            'cost' => 0.0,
            'duration_ms' => 0,
        ], $runData));

        // Keep only last 50 runs
        $data['runs'] = array_slice($data['runs'], 0, 50);
        $data['last_full_sync'] = now()->toISOString();

        $this->save($data);
    }

    /**
     * Get all recorded runs, newest first.
     */
    public function getRuns(): array
    {
        return $this->load()['runs'] ?? [];
    }

    // ── Hash helpers ──────────────────────────────────────────────────────

    public function getHashes(string $lang): array
    {
        return $this->load()['hashes'][$lang] ?? [];
    }

    // ── Utility ───────────────────────────────────────────────────────────

    public function exists(): bool
    {
        return File::exists($this->metadataPath);
    }

    public function reset(): bool
    {
        try {
            if (File::exists($this->metadataPath)) {
                File::delete($this->metadataPath);
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getPath(): string
    {
        return $this->metadataPath;
    }

    // ── Internal ──────────────────────────────────────────────────────────

    protected function emptyStructure(): array
    {
        return [
            'version' => '1.0',
            'last_full_sync' => null,
            'hashes' => [],
            'runs' => [],
        ];
    }

    protected function ensureDirectory(): void
    {
        $dir = dirname($this->metadataPath);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
    }
}
