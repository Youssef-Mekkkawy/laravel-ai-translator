<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Tracking;

class HashGenerator
{
    /**
     * Generate MD5 hash for a single text
     *
     * @param  string  $text  The text to hash
     * @return string MD5 hash (16 characters)
     */
    public function generate(string $text): string
    {
        // Normalize the text before hashing
        $normalized = $this->normalize($text);

        // Generate MD5 hash (fast and sufficient for our needs)
        return md5($normalized);
    }

    /**
     * Generate hashes for multiple texts
     *
     * @param  array  $texts  Associative array ['key' => 'text']
     * @return array Associative array ['key' => 'hash']
     */
    public function generateBatch(array $texts): array
    {
        $hashes = [];

        foreach ($texts as $key => $text) {
            $hashes[$key] = $this->generate($text);
        }

        return $hashes;
    }

    /**
     * Normalize text before hashing
     *
     * This ensures consistent hashes even with minor whitespace differences
     *
     * @param  string  $text  The text to normalize
     * @return string Normalized text
     */
    protected function normalize(string $text): string
    {
        // Convert to lowercase for case-insensitive comparison
        $text = mb_strtolower($text, 'UTF-8');

        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim leading/trailing whitespace
        $text = trim($text);

        return $text;
    }

    /**
     * Compare two hashes
     *
     * @param  string  $hash1  First hash
     * @param  string  $hash2  Second hash
     * @return bool True if hashes match
     */
    public function compare(string $hash1, string $hash2): bool
    {
        return $hash1 === $hash2;
    }

    /**
     * Check if text has changed by comparing hashes
     *
     * @param  string  $text  Current text
     * @param  string  $storedHash  Previously stored hash
     * @return bool True if text has changed
     */
    public function hasChanged(string $text, string $storedHash): bool
    {
        $currentHash = $this->generate($text);

        return ! $this->compare($currentHash, $storedHash);
    }

    /**
     * Get changed keys by comparing two hash arrays
     *
     * @param  array  $currentHashes  ['key' => 'hash']
     * @param  array  $storedHashes  ['key' => 'hash']
     * @return array List of keys that changed
     */
    public function getChangedKeys(array $currentHashes, array $storedHashes): array
    {
        $changed = [];

        foreach ($currentHashes as $key => $hash) {
            // Key doesn't exist in stored hashes = new key
            if (! isset($storedHashes[$key])) {
                $changed[] = $key;

                continue;
            }

            // Hash changed = content changed
            if (! $this->compare($hash, $storedHashes[$key])) {
                $changed[] = $key;
            }
        }

        return $changed;
    }

    /**
     * Get new keys (keys in current but not in stored)
     *
     * @param  array  $currentHashes  ['key' => 'hash']
     * @param  array  $storedHashes  ['key' => 'hash']
     * @return array List of new keys
     */
    public function getNewKeys(array $currentHashes, array $storedHashes): array
    {
        $newKeys = [];

        foreach ($currentHashes as $key => $hash) {
            if (! isset($storedHashes[$key])) {
                $newKeys[] = $key;
            }
        }

        return $newKeys;
    }

    /**
     * Get deleted keys (keys in stored but not in current)
     *
     * @param  array  $currentHashes  ['key' => 'hash']
     * @param  array  $storedHashes  ['key' => 'hash']
     * @return array List of deleted keys
     */
    public function getDeletedKeys(array $currentHashes, array $storedHashes): array
    {
        $deletedKeys = [];

        foreach ($storedHashes as $key => $hash) {
            if (! isset($currentHashes[$key])) {
                $deletedKeys[] = $key;
            }
        }

        return $deletedKeys;
    }

    /**
     * Get statistics about changes
     *
     * @param  array  $currentHashes  ['key' => 'hash']
     * @param  array  $storedHashes  ['key' => 'hash']
     * @return array Statistics
     */
    public function getChangeStatistics(array $currentHashes, array $storedHashes): array
    {
        $changed = $this->getChangedKeys($currentHashes, $storedHashes);
        $new = $this->getNewKeys($currentHashes, $storedHashes);
        $deleted = $this->getDeletedKeys($currentHashes, $storedHashes);

        return [
            'total_current' => count($currentHashes),
            'total_stored' => count($storedHashes),
            'changed' => count($changed),
            'new' => count($new),
            'deleted' => count($deleted),
            'unchanged' => count($currentHashes) - count($changed),
            'changed_keys' => $changed,
            'new_keys' => $new,
            'deleted_keys' => $deleted,
        ];
    }
}
