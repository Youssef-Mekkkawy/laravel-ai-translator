<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Lock;

class LockManager
{
    protected LockStorage $storage;

    public function __construct(LockStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Lock a translation key
     *
     * @param  string  $language  Language code (e.g., 'ar', 'fr')
     * @param  string  $key  Translation key (e.g., 'auth.login')
     * @param  string|null  $reason  Optional reason for locking
     * @return bool Success
     */
    public function lock(string $language, string $key, ?string $reason = null): bool
    {
        $locks = $this->storage->load();

        // Get current value from language file
        $currentValue = $this->getCurrentValue($language, $key);

        // Create lock entry
        if (! isset($locks[$language])) {
            $locks[$language] = [];
        }

        $locks[$language][$key] = [
            'locked_at' => now()->toIso8601String(),
            'locked_by' => $this->getCurrentUser(),
            'reason' => $reason,
            'value' => $currentValue,
        ];

        return $this->storage->save($locks);
    }

    /**
     * Unlock a translation key
     *
     * @param  string  $language  Language code
     * @param  string  $key  Translation key
     * @return bool Success
     */
    public function unlock(string $language, string $key): bool
    {
        $locks = $this->storage->load();

        if (! isset($locks[$language][$key])) {
            return false; // Not locked
        }

        unset($locks[$language][$key]);

        // Clean up empty language arrays
        if (empty($locks[$language])) {
            unset($locks[$language]);
        }

        return $this->storage->save($locks);
    }

    /**
     * Check if a key is locked
     *
     * @param  string  $language  Language code
     * @param  string  $key  Translation key
     */
    public function isLocked(string $language, string $key): bool
    {
        $locks = $this->storage->load();

        return isset($locks[$language][$key]);
    }

    /**
     * Get lock info for a key
     *
     * @param  string  $language  Language code
     * @param  string  $key  Translation key
     * @return array|null Lock info or null if not locked
     */
    public function getLock(string $language, string $key): ?array
    {
        $locks = $this->storage->load();

        return $locks[$language][$key] ?? null;
    }

    /**
     * Get all locked keys
     *
     * @param  string|null  $language  Filter by language (optional)
     */
    public function getAll(?string $language = null): array
    {
        $locks = $this->storage->load();

        if ($language !== null) {
            return $locks[$language] ?? [];
        }

        return $locks;
    }

    /**
     * Lock multiple keys at once
     *
     * @param  string  $language  Language code
     * @param  array  $keys  Array of keys to lock
     * @param  string|null  $reason  Optional reason
     * @return int Number of keys locked
     */
    public function lockMultiple(string $language, array $keys, ?string $reason = null): int
    {
        $count = 0;

        foreach ($keys as $key) {
            if ($this->lock($language, $key, $reason)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Unlock multiple keys at once
     *
     * @param  string  $language  Language code
     * @param  array  $keys  Array of keys to unlock
     * @return int Number of keys unlocked
     */
    public function unlockMultiple(string $language, array $keys): int
    {
        $count = 0;

        foreach ($keys as $key) {
            if ($this->unlock($language, $key)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Lock keys matching a pattern
     *
     * @param  string  $language  Language code
     * @param  string  $pattern  Pattern (e.g., 'auth.*')
     * @param  string|null  $reason  Optional reason
     * @return int Number of keys locked
     */
    public function lockPattern(string $language, string $pattern, ?string $reason = null): int
    {
        // Get all keys in the language
        $allKeys = $this->getAllKeysForLanguage($language);

        // Convert pattern to regex
        $regex = $this->patternToRegex($pattern);

        // Find matching keys
        $matchingKeys = array_filter($allKeys, function ($key) use ($regex) {
            return preg_match($regex, $key);
        });

        return $this->lockMultiple($language, $matchingKeys, $reason);
    }

    /**
     * Unlock keys matching a pattern
     *
     * @param  string  $language  Language code
     * @param  string  $pattern  Pattern (e.g., 'auth.*')
     * @return int Number of keys unlocked
     */
    public function unlockPattern(string $language, string $pattern): int
    {
        $locks = $this->storage->load();
        $languageLocks = $locks[$language] ?? [];

        // Convert pattern to regex
        $regex = $this->patternToRegex($pattern);

        // Find matching locked keys
        $matchingKeys = array_filter(array_keys($languageLocks), function ($key) use ($regex) {
            return preg_match($regex, $key);
        });

        return $this->unlockMultiple($language, $matchingKeys);
    }

    /**
     * Get count of locked keys
     *
     * @param  string|null  $language  Filter by language
     */
    public function count(?string $language = null): int
    {
        $locks = $this->getAll($language);

        if ($language !== null) {
            return count($locks);
        }

        // Count across all languages
        $total = 0;
        foreach ($locks as $languageLocks) {
            $total += count($languageLocks);
        }

        return $total;
    }

    /**
     * Clear all locks
     *
     * @param  string|null  $language  Clear only specific language (optional)
     */
    public function clear(?string $language = null): bool
    {
        if ($language === null) {
            return $this->storage->save([]);
        }

        $locks = $this->storage->load();
        unset($locks[$language]);

        return $this->storage->save($locks);
    }

    /**
     * Get current value of a translation
     *
     * @param  string  $language  Language code
     * @param  string  $key  Translation key
     */
    protected function getCurrentValue(string $language, string $key): ?string
    {
        // Parse key to get file and actual key
        $parts = explode('.', $key, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$file, $actualKey] = $parts;
        $filePath = lang_path("{$language}/{$file}.php");

        if (! file_exists($filePath)) {
            return null;
        }

        $translations = include $filePath;

        return $translations[$actualKey] ?? null;
    }

    /**
     * Get all translation keys for a language
     *
     * @param  string  $language  Language code
     */
    protected function getAllKeysForLanguage(string $language): array
    {
        $langPath = lang_path($language);

        if (! file_exists($langPath)) {
            return [];
        }

        $keys = [];
        $files = glob($langPath.'/*.php');

        foreach ($files as $file) {
            $namespace = basename($file, '.php');
            $translations = include $file;

            if (is_array($translations)) {
                foreach (array_keys($translations) as $key) {
                    $keys[] = $namespace.'.'.$key;
                }
            }
        }

        return $keys;
    }

    /**
     * Convert pattern to regex
     *
     * @param  string  $pattern  Pattern with wildcards (e.g., 'auth.*')
     * @return string Regex pattern
     */
    protected function patternToRegex(string $pattern): string
    {
        // Escape regex special characters except *
        $pattern = preg_quote($pattern, '/');

        // Convert * to .*
        $pattern = str_replace('\*', '.*', $pattern);

        return '/^'.$pattern.'$/';
    }

    /**
     * Get current user (for lock metadata)
     */
    protected function getCurrentUser(): string
    {
        // Try to get from auth
        if (function_exists('auth') && auth()->check()) {
            return auth()->user()->email ?? auth()->user()->name ?? 'authenticated';
        }

        // Try to get from environment
        if ($user = getenv('USER') ?: getenv('USERNAME')) {
            return $user;
        }

        return 'system';
    }
}
