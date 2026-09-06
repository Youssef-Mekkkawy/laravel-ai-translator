<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Tracking;

use Illuminate\Support\Facades\File;

class MetadataManager
{
    protected string $metaFile;

    public function __construct(string $metaFile)
    {
        $this->metaFile = $metaFile;
    }

    // ── Hash tracking ─────────────────────────────────────────────

    public function getHashes(): array
    {
        return $this->read()['hashes'] ?? [];
    }

    public function saveHashes(array $hashes): void
    {
        $data = $this->read();
        $data['hashes'] = $hashes;
        $this->write($data);
    }

    public function getHash(string $key): ?string
    {
        return $this->getHashes()[$key] ?? null;
    }

    public function hasChanged(string $key, string $value): bool
    {
        return $this->getHash($key) !== md5($value);
    }

    public function updateHash(string $key, string $value): void
    {
        $data = $this->read();
        $data['hashes'][$key] = md5($value);
        $this->write($data);
    }

    // ── Run history ───────────────────────────────────────────────

    /**
     * Record a completed translation run.
     */
    public function recordRun(array $runData): void
    {
        $data = $this->read();

        if (!isset($data['runs'])) {
            $data['runs'] = [];
        }

        // Add new run at the start (newest first)
        array_unshift($data['runs'], array_merge([
            'started_at'      => now()->toISOString(),
            'status'          => 'success',
            'keys_translated' => 0,
            'languages'       => [],
            'provider'        => config('ai-translator.driver', 'ollama'),
            'model'           => config('ai-translator.providers.'.config('ai-translator.driver','ollama').'.model', ''),
            'cost'            => 0.0,
            'duration_ms'     => 0,
        ], $runData));

        // Keep only last 50 runs
        $data['runs'] = array_slice($data['runs'], 0, 50);
        $data['last_full_sync'] = now()->toISOString();

        $this->write($data);
    }

    /**
     * Get all recorded runs, newest first.
     */
    public function getRuns(): array
    {
        return $this->read()['runs'] ?? [];
    }

    // ── Internal ──────────────────────────────────────────────────

    protected function read(): array
    {
        if (!File::exists($this->metaFile)) {
            return [];
        }

        try {
            $data = json_decode(File::get($this->metaFile), true);
            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function write(array $data): void
    {
        $dir = dirname($this->metaFile);

        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($this->metaFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
