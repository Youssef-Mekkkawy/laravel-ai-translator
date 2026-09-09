<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Writers;

use Illuminate\Support\Facades\File;

class JsonLanguageWriter
{
    /**
     * Read all keys from a JSON language file.
     */
    public function readJson(string $locale): array
    {
        $path = lang_path($locale.'.json');

        if (! File::exists($path)) {
            return [];
        }

        try {
            $data = json_decode(File::get($path), true);

            return is_array($data) ? $data : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Write translated keys to a JSON language file.
     * Merges with existing translations (preserves untranslated keys).
     */
    public function writeJson(string $locale, array $translations): string
    {
        $path = lang_path($locale.'.json');
        $existing = $this->readJson($locale);

        // Merge — translated values override existing
        $merged = array_merge($existing, $translations);

        // Sort keys alphabetically for clean diffs
        ksort($merged);

        File::ensureDirectoryExists(lang_path());
        File::put($path, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);

        return $path;
    }

    /**
     * Check if a JSON source file exists.
     */
    public function hasJsonSource(string $locale): bool
    {
        return File::exists(lang_path($locale.'.json'));
    }

    /**
     * Get all keys from the source JSON file as a flat key=>value map.
     */
    public function getSourceJsonKeys(string $sourceLang): array
    {
        return $this->readJson($sourceLang);
    }
}
