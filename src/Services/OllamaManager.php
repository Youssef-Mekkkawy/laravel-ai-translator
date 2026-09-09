<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services;

use Illuminate\Support\Facades\Http;

class OllamaManager
{
    protected string $apiUrl;

    protected string $model;

    public function __construct()
    {
        $this->apiUrl = rtrim(
            str_replace('/v1', '', config('ai-translator.providers.ollama.api_url', 'http://localhost:11434')),
            '/'
        );
        $this->model = config('ai-translator.providers.ollama.model', 'llama3.2');
    }

    // ── Status ────────────────────────────────────────────────────────────

    /**
     * Check if Ollama is running.
     */
    public function isRunning(): bool
    {
        try {
            $response = Http::timeout(3)->get($this->apiUrl.'/api/tags');

            return $response->successful();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check if the configured model is available.
     */
    public function hasModel(?string $model = null): bool
    {
        $model = $model ?? $this->model;

        try {
            $response = Http::timeout(5)->get($this->apiUrl.'/api/tags');
            if (! $response->successful()) {
                return false;
            }

            $models = collect($response->json('models', []))->pluck('name');

            return $models->contains(fn ($m) => str_starts_with($m, $model));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * List available models.
     */
    public function listModels(): array
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl.'/api/tags');
            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('models', []))->pluck('name')->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── Start ─────────────────────────────────────────────────────────────

    /**
     * Start Ollama in the background (cross-platform).
     */
    public function start(): bool
    {
        if ($this->isRunning()) {
            return true;
        }

        try {
            $os = PHP_OS_FAMILY;
            $ollamaPath = $this->findOllamaPath();

            if (! $ollamaPath) {
                return false;
            }

            if ($os === 'Windows') {
                pclose(popen("start /B \"\" \"{$ollamaPath}\" serve", 'r'));
            } else {
                exec("\"{$ollamaPath}\" serve > /dev/null 2>&1 &");
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Find the ollama executable path.
     */
    protected function findOllamaPath(): ?string
    {
        // Check config first
        $configured = config('ai-translator.providers.ollama.executable', '');
        if ($configured && file_exists($configured)) {
            return $configured;
        }

        // Common locations
        $os = PHP_OS_FAMILY;
        $paths = $os === 'Windows' ? [
            getenv('LOCALAPPDATA').'\\Programs\\Ollama\\ollama.exe',
            'C:\\Program Files\\Ollama\\ollama.exe',
            'C:\\ollama\\ollama.exe',
        ] : [
            '/usr/local/bin/ollama',
            '/usr/bin/ollama',
            '/opt/homebrew/bin/ollama',
            getenv('HOME').'/.ollama/ollama',
        ];

        foreach ($paths as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        // Try which/where as fallback
        $cmd = $os === 'Windows' ? 'where ollama 2>NUL' : 'which ollama 2>/dev/null';
        $result = trim(shell_exec($cmd) ?? '');

        if ($result && file_exists($result)) {
            return $result;
        }

        return null;
    }

    /**
     * Wait until Ollama is ready (up to $seconds).
     */
    public function waitUntilReady(int $seconds = 10): bool
    {
        $start = time();

        while ((time() - $start) < $seconds) {
            if ($this->isRunning()) {
                return true;
            }
            sleep(1);
        }

        return false;
    }

    // ── Model ─────────────────────────────────────────────────────────────

    /**
     * Pull a model in the background (fire and forget).
     */
    public function pullModelBackground(?string $model = null): void
    {
        $model = $model ?? $this->model;
        $ollamaPath = $this->findOllamaPath();
        if (! $ollamaPath) {
            return;
        }

        $os = PHP_OS_FAMILY;
        try {
            if ($os === 'Windows') {
                pclose(popen("start /B \"\" \"{$ollamaPath}\" pull {$model} > NUL 2>&1", 'r'));
            } else {
                exec("\"{$ollamaPath}\" pull {$model} > /dev/null 2>&1 &");
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * Pull a model synchronously (CLI only).
     */
    public function pullModelSync(?string $model = null): bool
    {
        $model = $model ?? $this->model;
        $ollamaPath = $this->findOllamaPath();
        if (! $ollamaPath) {
            return false;
        }

        $result = 0;
        system("\"{$ollamaPath}\" pull {$model}", $result);

        return $result === 0;
    }

    // ── Ensure ready ──────────────────────────────────────────────────────

    /**
     * Full check: ensure Ollama is running and model exists.
     * Returns ['ok' => bool, 'started' => bool, 'pulling' => bool, 'message' => string]
     */
    public function ensureReady(?string $model = null): array
    {
        $model = $model ?? $this->model;
        $started = false;
        $pulling = false;

        // Check if running
        if (! $this->isRunning()) {
            $this->start();
            $ready = $this->waitUntilReady(10);

            if (! $ready) {
                return [
                    'ok' => false,
                    'started' => false,
                    'pulling' => false,
                    'message' => 'Could not start Ollama. Make sure it is installed.',
                ];
            }

            $started = true;
        }

        // Check if model exists
        if (! $this->hasModel($model)) {
            // Start pull in background
            $this->pullModelBackground($model);
            $pulling = true;

            return [
                'ok' => false,
                'started' => $started,
                'pulling' => true,
                'message' => "Model '{$model}' is downloading in the background. This may take a few minutes. Try again shortly.",
            ];
        }

        return [
            'ok' => true,
            'started' => $started,
            'pulling' => false,
            'message' => $started ? 'Ollama started successfully.' : 'Ollama is ready.',
        ];
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
