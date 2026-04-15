<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Tracking;

use Illuminate\Support\Facades\File;

class MetadataManager
{
    protected string $metadataPath;
    protected string $backupPath;
    protected int $lockTimeout = 5; // seconds

    public function __construct(?string $metadataPath = null)
    {
        // Default to lang/.translations-meta.json
        $this->metadataPath = $metadataPath ?? lang_path('.translations-meta.json');
        $this->backupPath = $this->metadataPath . '.backup';
    }

    /**
     * Load metadata from JSON file
     *
     * @return array Metadata structure
     */
    public function load(): array
    {
        // If file doesn't exist, return empty structure
        if (!File::exists($this->metadataPath)) {
            return $this->getEmptyStructure();
        }

        try {
            // Read file with shared lock
            $handle = fopen($this->metadataPath, 'r');
            
            if ($handle === false) {
                return $this->getEmptyStructure();
            }

            // Acquire shared lock (allows multiple readers)
            if (!flock($handle, LOCK_SH, $wouldBlock)) {
                fclose($handle);
                return $this->getEmptyStructure();
            }

            // Read content
            $content = stream_get_contents($handle);

            // Release lock and close
            flock($handle, LOCK_UN);
            fclose($handle);

            // Parse JSON
            $data = json_decode($content, true);

            // Validate structure
            if (!$this->validateStructure($data)) {
                // If invalid, backup and return empty
                $this->backupCorrupted();
                return $this->getEmptyStructure();
            }

            return $data;

        } catch (\Exception $e) {
            // On any error, return empty structure
            return $this->getEmptyStructure();
        }
    }

    /**
     * Save metadata to JSON file
     *
     * @param array $data Metadata to save
     * @return bool Success status
     */
    public function save(array $data): bool
    {
        try {
            // Validate before saving
            if (!$this->validateStructure($data)) {
                throw new \InvalidArgumentException('Invalid metadata structure');
            }

            // Create directory if doesn't exist
            $directory = dirname($this->metadataPath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Backup existing file
            if (File::exists($this->metadataPath)) {
                $this->backup();
            }

            // Open file for writing
            $handle = fopen($this->metadataPath, 'w');
            
            if ($handle === false) {
                return false;
            }

            // Acquire exclusive lock (blocks all other access)
            if (!flock($handle, LOCK_EX, $wouldBlock)) {
                fclose($handle);
                return false;
            }

            // Write JSON with pretty print
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            fwrite($handle, $json);

            // Release lock and close
            flock($handle, LOCK_UN);
            fclose($handle);

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if metadata file exists
     *
     * @return bool True if exists
     */
    public function exists(): bool
    {
        return File::exists($this->metadataPath);
    }

    /**
     * Delete metadata file (reset tracking)
     *
     * @return bool Success status
     */
    public function reset(): bool
    {
        try {
            if (File::exists($this->metadataPath)) {
                // Backup before deleting
                $this->backup();
                File::delete($this->metadataPath);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create backup of current metadata
     *
     * @return bool Success status
     */
    public function backup(): bool
    {
        try {
            if (File::exists($this->metadataPath)) {
                File::copy($this->metadataPath, $this->backupPath);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Restore from backup
     *
     * @return bool Success status
     */
    public function restore(): bool
    {
        try {
            if (File::exists($this->backupPath)) {
                File::copy($this->backupPath, $this->metadataPath);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Backup corrupted file
     *
     * @return void
     */
    protected function backupCorrupted(): void
    {
        try {
            if (File::exists($this->metadataPath)) {
                $corruptedPath = $this->metadataPath . '.corrupted.' . time();
                File::copy($this->metadataPath, $corruptedPath);
            }
        } catch (\Exception $e) {
            // Ignore errors
        }
    }

    /**
     * Get empty metadata structure
     *
     * @return array Empty structure
     */
    protected function getEmptyStructure(): array
    {
        return [
            'version' => '1.0',
            'last_full_sync' => null,
            'hashes' => [],
        ];
    }

    /**
     * Validate metadata structure
     *
     * @param mixed $data Data to validate
     * @return bool True if valid
     */
    protected function validateStructure($data): bool
    {
        // Must be array
        if (!is_array($data)) {
            return false;
        }

        // Must have required keys
        if (!isset($data['version']) || !isset($data['hashes'])) {
            return false;
        }

        // Hashes must be array
        if (!is_array($data['hashes'])) {
            return false;
        }

        // Each language must be array of string keys
        foreach ($data['hashes'] as $language => $hashes) {
            if (!is_string($language) || !is_array($hashes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get metadata file path
     *
     * @return string File path
     */
    public function getPath(): string
    {
        return $this->metadataPath;
    }

    /**
     * Get backup file path
     *
     * @return string Backup path
     */
    public function getBackupPath(): string
    {
        return $this->backupPath;
    }

    /**
     * Get metadata file size
     *
     * @return int File size in bytes, 0 if doesn't exist
     */
    public function getSize(): int
    {
        if (!File::exists($this->metadataPath)) {
            return 0;
        }
        return File::size($this->metadataPath);
    }

    /**
     * Get last modified timestamp
     *
     * @return int|null Unix timestamp or null if doesn't exist
     */
    public function getLastModified(): ?int
    {
        if (!File::exists($this->metadataPath)) {
            return null;
        }
        return File::lastModified($this->metadataPath);
    }

    /**
     * Get metadata statistics
     *
     * @return array Statistics
     */
    public function getStatistics(): array
    {
        $data = $this->load();
        
        $totalKeys = 0;
        $languages = [];
        
        foreach ($data['hashes'] as $language => $hashes) {
            $languages[$language] = count($hashes);
            $totalKeys += count($hashes);
        }
        
        return [
            'version' => $data['version'],
            'last_sync' => $data['last_full_sync'] ?? 'Never',
            'file_exists' => $this->exists(),
            'file_size' => $this->getSize(),
            'last_modified' => $this->getLastModified(),
            'total_keys' => $totalKeys,
            'languages' => $languages,
            'language_count' => count($languages),
        ];
    }
}