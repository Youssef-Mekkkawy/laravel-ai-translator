<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Lock;

use Illuminate\Support\Facades\File;

class LockStorage
{
    protected string $filePath;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? lang_path('.locked-translations.json');
    }

    /**
     * Load locks from file
     *
     * @return array
     */
    public function load(): array
    {
        if (!File::exists($this->filePath)) {
            return [];
        }

        try {
            $content = File::get($this->filePath);
            $data = json_decode($content, true);

            if (!is_array($data)) {
                return [];
            }

            return $data;
        } catch (\Exception $e) {
            // If file is corrupted, return empty
            return [];
        }
    }

    /**
     * Save locks to file
     *
     * @param array $locks
     * @return bool
     */
    public function save(array $locks): bool
    {
        try {
            // Ensure directory exists
            $directory = dirname($this->filePath);
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            // Format JSON nicely
            $json = json_encode($locks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            return File::put($this->filePath, $json) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if storage file exists
     *
     * @return bool
     */
    public function exists(): bool
    {
        return File::exists($this->filePath);
    }

    /**
     * Delete storage file
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists()) {
            return true;
        }

        return File::delete($this->filePath);
    }

    /**
     * Get file path
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Get file size in bytes
     *
     * @return int
     */
    public function getSize(): int
    {
        if (!$this->exists()) {
            return 0;
        }

        return File::size($this->filePath);
    }

    /**
     * Backup current locks
     *
     * @return bool
     */
    public function backup(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $backupPath = $this->filePath . '.backup';

        try {
            return File::copy($this->filePath, $backupPath);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Restore from backup
     *
     * @return bool
     */
    public function restore(): bool
    {
        $backupPath = $this->filePath . '.backup';

        if (!File::exists($backupPath)) {
            return false;
        }

        try {
            return File::copy($backupPath, $this->filePath);
        } catch (\Exception $e) {
            return false;
        }
    }
}