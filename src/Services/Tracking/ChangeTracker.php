<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Tracking;

class ChangeTracker
{
    protected HashGenerator $hashGenerator;

    protected MetadataManager $metadataManager;

    public function __construct(
        HashGenerator $hashGenerator,
        MetadataManager $metadataManager
    ) {
        $this->hashGenerator = $hashGenerator;
        $this->metadataManager = $metadataManager;
    }

    /**
     * Analyze what needs to be translated
     *
     * @param  array  $currentTranslations  ['key' => 'text']
     * @param  string  $sourceLanguage  Source language code (e.g., 'en')
     * @return array Analysis results
     */
    public function analyze(array $currentTranslations, string $sourceLanguage): array
    {
        // Generate hashes for current translations
        $currentHashes = $this->hashGenerator->generateBatch($currentTranslations);

        // Load stored metadata
        $metadata = $this->metadataManager->load();
        $storedHashes = $metadata['hashes'][$sourceLanguage] ?? [];

        // Get statistics
        $stats = $this->hashGenerator->getChangeStatistics($currentHashes, $storedHashes);

        return [
            'needs_translation' => $stats['changed_keys'],
            'new_keys' => $stats['new_keys'],
            'deleted_keys' => $stats['deleted_keys'],
            'unchanged_count' => $stats['unchanged'],
            'total_keys' => $stats['total_current'],
            'statistics' => $stats,
        ];
    }

    /**
     * Get keys that need translation
     *
     * @param  array  $currentTranslations  ['key' => 'text']
     * @param  string  $sourceLanguage  Source language code
     * @param  bool  $forceAll  Force re-translation of all keys
     * @return array Keys that need translation
     */
    public function getKeysToTranslate(
        array $currentTranslations,
        string $sourceLanguage,
        bool $forceAll = false
    ): array {
        // If forcing all, return all keys
        if ($forceAll) {
            return array_keys($currentTranslations);
        }

        // Analyze changes
        $analysis = $this->analyze($currentTranslations, $sourceLanguage);

        return $analysis['needs_translation'];
    }

    /**
     * Filter translations to only those that need translation
     *
     * @param  array  $currentTranslations  ['key' => 'text']
     * @param  string  $sourceLanguage  Source language code
     * @param  bool  $forceAll  Force re-translation of all keys
     * @return array Filtered translations that need translation
     */
    public function filterChanged(
        array $currentTranslations,
        string $sourceLanguage,
        bool $forceAll = false
    ): array {
        // If forcing all, return all translations
        if ($forceAll) {
            return $currentTranslations;
        }

        // Get keys to translate
        $keysToTranslate = $this->getKeysToTranslate($currentTranslations, $sourceLanguage, $forceAll);

        // Filter to only changed keys
        $filtered = [];
        foreach ($keysToTranslate as $key) {
            if (isset($currentTranslations[$key])) {
                $filtered[$key] = $currentTranslations[$key];
            }
        }

        return $filtered;
    }

    /**
     * Update metadata after successful translation
     *
     * @param  array  $translations  ['key' => 'text']
     * @param  string  $sourceLanguage  Source language code
     */
    public function updateHashes(array $translations, string $sourceLanguage): void
    {
        // Generate hashes for the translations
        $newHashes = $this->hashGenerator->generateBatch($translations);

        // Load current metadata
        $metadata = $this->metadataManager->load();

        // Initialize if not exists
        if (! isset($metadata['hashes'][$sourceLanguage])) {
            $metadata['hashes'][$sourceLanguage] = [];
        }

        // Update hashes (merge with existing)
        $metadata['hashes'][$sourceLanguage] = array_merge(
            $metadata['hashes'][$sourceLanguage],
            $newHashes
        );

        // Update last sync timestamp
        $metadata['last_full_sync'] = now()->toIso8601String();

        // Save metadata
        $this->metadataManager->save($metadata);
    }

    /**
     * Remove deleted keys from metadata
     *
     * @param  array  $currentTranslations  ['key' => 'text']
     * @param  string  $sourceLanguage  Source language code
     * @return int Number of keys removed
     */
    public function cleanupDeletedKeys(array $currentTranslations, string $sourceLanguage): int
    {
        // Load metadata
        $metadata = $this->metadataManager->load();

        if (! isset($metadata['hashes'][$sourceLanguage])) {
            return 0;
        }

        // Get deleted keys
        $currentHashes = $this->hashGenerator->generateBatch($currentTranslations);
        $deletedKeys = $this->hashGenerator->getDeletedKeys(
            $currentHashes,
            $metadata['hashes'][$sourceLanguage]
        );

        // Remove deleted keys from metadata
        foreach ($deletedKeys as $key) {
            unset($metadata['hashes'][$sourceLanguage][$key]);
        }

        // Save if any were deleted
        if (count($deletedKeys) > 0) {
            $this->metadataManager->save($metadata);
        }

        return count($deletedKeys);
    }

    /**
     * Check if a specific key has changed
     *
     * @param  string  $key  Translation key
     * @param  string  $text  Current text
     * @param  string  $sourceLanguage  Source language code
     * @return bool True if changed or new
     */
    public function hasKeyChanged(string $key, string $text, string $sourceLanguage): bool
    {
        // Load metadata
        $metadata = $this->metadataManager->load();

        // If no stored hash, it's a new key
        if (! isset($metadata['hashes'][$sourceLanguage][$key])) {
            return true;
        }

        // Check if hash changed
        $storedHash = $metadata['hashes'][$sourceLanguage][$key];

        return $this->hashGenerator->hasChanged($text, $storedHash);
    }

    /**
     * Get detailed change report
     *
     * @param  array  $currentTranslations  ['key' => 'text']
     * @param  string  $sourceLanguage  Source language code
     * @return array Detailed report
     */
    public function getChangeReport(array $currentTranslations, string $sourceLanguage): array
    {
        $analysis = $this->analyze($currentTranslations, $sourceLanguage);
        $metadata = $this->metadataManager->load();

        return [
            'source_language' => $sourceLanguage,
            'total_keys' => $analysis['total_keys'],
            'new_keys' => [
                'count' => count($analysis['new_keys']),
                'keys' => $analysis['new_keys'],
            ],
            'changed_keys' => [
                'count' => count($analysis['needs_translation']) - count($analysis['new_keys']),
                'keys' => array_diff($analysis['needs_translation'], $analysis['new_keys']),
            ],
            'deleted_keys' => [
                'count' => count($analysis['deleted_keys']),
                'keys' => $analysis['deleted_keys'],
            ],
            'unchanged_keys' => [
                'count' => $analysis['unchanged_count'],
            ],
            'needs_translation' => [
                'count' => count($analysis['needs_translation']),
                'keys' => $analysis['needs_translation'],
            ],
            'last_sync' => $metadata['last_full_sync'] ?? 'Never',
            'metadata_version' => $metadata['version'] ?? 'Unknown',
        ];
    }

    /**
     * Reset all tracking data (force full re-sync)
     */
    public function reset(): void
    {
        $this->metadataManager->reset();
    }

    /**
     * Check if tracking data exists
     *
     * @return bool True if metadata exists
     */
    public function hasTrackingData(): bool
    {
        return $this->metadataManager->exists();
    }
}
