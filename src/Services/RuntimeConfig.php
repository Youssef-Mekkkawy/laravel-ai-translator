<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services;

use Illuminate\Support\Facades\File;

class RuntimeConfig
{
    protected string $path;

    protected array $data = [];

    protected bool $loaded = false;

    public function __construct()
    {
        $this->path = lang_path('.ai-translator-runtime.json');
    }

    // ── Read ──────────────────────────────────────────────────────────────

    public function get(string $key, mixed $default = null): mixed
    {
        $this->load();

        return data_get($this->data, $key, $default);
    }

    public function all(): array
    {
        $this->load();

        return $this->data;
    }

    // ── Write ─────────────────────────────────────────────────────────────

    public function set(string $key, mixed $value): void
    {
        $this->load();
        data_set($this->data, $key, $value);
        $this->save();
    }

    public function merge(array $values): void
    {
        $this->load();
        $this->data = array_merge($this->data, $values);
        $this->save();
    }

    // ── Language helpers ──────────────────────────────────────────────────

    public function getSupportedLanguages(): array
    {
        $this->load();

        // Runtime config overrides, fallback to env/config
        $langs = $this->get('supported_languages');
        if ($langs !== null) {
            return is_array($langs) ? $langs : explode(',', $langs);
        }

        return config('ai-translator.languages', []);
    }

    public function setSupportedLanguages(array $languages): void
    {
        $this->set('supported_languages', array_values($languages));
    }

    public function addLanguage(string $locale): void
    {
        $langs = $this->getSupportedLanguages();
        if (! in_array($locale, $langs)) {
            $langs[] = $locale;
            $this->setSupportedLanguages($langs);
        }
    }

    public function removeLanguage(string $locale): void
    {
        $langs = array_values(array_filter(
            $this->getSupportedLanguages(),
            fn ($l) => $l !== $locale
        ));
        $this->setSupportedLanguages($langs);

        // Also remove from disabled
        $disabled = array_values(array_filter(
            $this->getDisabledLanguages(),
            fn ($l) => $l !== $locale
        ));
        $this->set('disabled_languages', $disabled);
    }

    public function getDisabledLanguages(): array
    {
        $disabled = $this->get('disabled_languages', []);

        return is_array($disabled) ? $disabled : explode(',', $disabled);
    }

    public function isDisabled(string $locale): bool
    {
        return in_array($locale, $this->getDisabledLanguages());
    }

    public function setDisabled(string $locale, bool $disabled): void
    {
        $current = $this->getDisabledLanguages();

        if ($disabled && ! in_array($locale, $current)) {
            $current[] = $locale;
        } elseif (! $disabled) {
            $current = array_values(array_filter($current, fn ($l) => $l !== $locale));
        }

        $this->set('disabled_languages', array_unique($current));
    }

    // ── Settings helpers ──────────────────────────────────────────────────

    public function getDriver(): string
    {
        return $this->get('driver', config('ai-translator.driver', 'ollama'));
    }

    public function setDriver(string $driver): void
    {
        $this->set('driver', $driver);
    }

    public function getOllamaModel(): string
    {
        return $this->get('ollama_model', config('ai-translator.providers.ollama.model', 'llama3.2'));
    }

    public function getOllamaUrl(): string
    {
        return $this->get('ollama_url', config('ai-translator.providers.ollama.api_url', 'http://localhost:11434'));
    }

    // ── Internal ──────────────────────────────────────────────────────────

    protected function load(): void
    {
        if ($this->loaded) {
            return;
        }

        if (File::exists($this->path)) {
            try {
                $data = json_decode(File::get($this->path), true);
                $this->data = is_array($data) ? $data : [];
            } catch (\Throwable $e) {
                $this->data = [];
            }
        }

        $this->loaded = true;
    }

    protected function save(): void
    {
        $dir = dirname($this->path);
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put(
            $this->path,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL
        );
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
