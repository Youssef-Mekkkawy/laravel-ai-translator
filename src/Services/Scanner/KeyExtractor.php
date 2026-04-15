<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Scanner;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class KeyExtractor
{
    /**
     * Parse a translation key and determine its file and key name
     *
     * @param string $key Translation key (e.g., 'auth.login' or 'Welcome')
     * @return array ['file' => 'auth', 'key' => 'login', 'value' => null]
     */
    public function parseKey(string $key): array
    {
        // Check if key contains dot notation (e.g., 'auth.login')
        if (str_contains($key, '.')) {
            return $this->parseDotNotationKey($key);
        }

        // Plain text key without dots (e.g., 'Welcome')
        return $this->parsePlainKey($key);
    }

    /**
     * Parse dot notation key (e.g., 'auth.login' or 'messages.success')
     */
    protected function parseDotNotationKey(string $key): array
    {
        $parts = explode('.', $key);
        $originalPartsCount = count($parts);
        
        // First part is the file name
        $file = array_shift($parts);
        
        // Remaining parts form the nested key
        $nestedKey = implode('.', $parts);

        return [
            'file' => $file,
            'key' => $nestedKey,
            'full_key' => $key,
            'is_nested' => $originalPartsCount > 2, // More than 2 parts means nested (e.g., messages.success.saved)
        ];
    }

    /**
     * Parse plain text key (e.g., 'Welcome to our platform')
     */
    protected function parsePlainKey(string $key): array
    {
        // Convert to snake_case for the key name
        $snakeKey = $this->toSnakeCase($key);

        return [
            'file' => 'auto', // Auto-generated keys go to 'auto.php'
            'key' => $snakeKey,
            'full_key' => $key,
            'is_nested' => false,
            'original_text' => $key,
        ];
    }

    /**
     * Convert text to snake_case
     */
    protected function toSnakeCase(string $text): string
    {
        // Remove special characters
        $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
        
        // Convert to snake_case
        $text = Str::snake($text);
        
        // Remove multiple underscores
        $text = preg_replace('/_+/', '_', $text);
        
        // Trim underscores from start and end
        return trim($text, '_');
    }

    /**
     * Check if a key exists in language files
     */
    public function keyExists(string $key, string $language = 'en'): bool
    {
        $parsed = $this->parseKey($key);
        $filePath = base_path("lang/{$language}/{$parsed['file']}.php");

        if (!File::exists($filePath)) {
            return false;
        }

        $translations = include $filePath;

        // Check for nested keys (e.g., 'auth.login' -> $translations['auth']['login'])
        if ($parsed['is_nested']) {
            return $this->hasNestedKey($translations, $parsed['key']);
        }

        return isset($translations[$parsed['key']]);
    }

    /**
     * Check if nested key exists in array
     */
    protected function hasNestedKey(array $array, string $key): bool
    {
        $keys = explode('.', $key);
        
        foreach ($keys as $segment) {
            if (!isset($array[$segment])) {
                return false;
            }
            
            if (!is_array($array[$segment])) {
                return true; // Reached a string value
            }
            
            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Get the value of a translation key
     */
    public function getKeyValue(string $key, string $language = 'en'): ?string
    {
        if (!$this->keyExists($key, $language)) {
            return null;
        }

        $parsed = $this->parseKey($key);
        $filePath = base_path("lang/{$language}/{$parsed['file']}.php");
        $translations = include $filePath;

        if ($parsed['is_nested']) {
            return $this->getNestedValue($translations, $parsed['key']);
        }

        return $translations[$parsed['key']] ?? null;
    }

    /**
     * Get nested value from array
     */
    protected function getNestedValue(array $array, string $key): ?string
    {
        $keys = explode('.', $key);
        
        foreach ($keys as $segment) {
            if (!isset($array[$segment])) {
                return null;
            }
            
            if (is_string($array[$segment])) {
                return $array[$segment];
            }
            
            $array = $array[$segment];
        }

        return is_string($array) ? $array : null;
    }

    /**
     * Generate a default value for a key
     */
    public function generateDefaultValue(string $key): string
    {
        $parsed = $this->parseKey($key);

        // If it's a plain text key, return the original text
        if (isset($parsed['original_text'])) {
            return $parsed['original_text'];
        }

        // For dot notation, generate from the key name
        // Example: 'user_settings' -> 'User Settings'
        return Str::headline($parsed['key']);
    }

    /**
     * Organize keys by file
     *
     * @param array $keys Array of translation keys
     * @return array Grouped by file ['auth' => [['key' => 'login', ...]], ...]
     */
    public function organizeKeysByFile(array $keys): array
    {
        $organized = [];

        foreach ($keys as $key) {
            // Parse the key
            $parsed = $this->parseKey($key);
            $file = $parsed['file'];

            // Initialize array for this file if not exists
            if (!isset($organized[$file])) {
                $organized[$file] = [];
            }

            // Add the key data as an array
            $organized[$file][] = [
                'key' => $parsed['key'],
                'full_key' => $key, // Use original key, not parsed['full_key']
                'is_nested' => $parsed['is_nested'] ?? false,
                'default_value' => $this->generateDefaultValue($key),
            ];
        }

        return $organized;
    }

    /**
     * Get missing keys (keys that don't exist in language files)
     */
    public function getMissingKeys(array $keys, string $language = 'en'): array
    {
        $missing = [];

        foreach ($keys as $key) {
            if (!$this->keyExists($key, $language)) {
                $missing[] = $key;
            }
        }

        return $missing;
    }
}