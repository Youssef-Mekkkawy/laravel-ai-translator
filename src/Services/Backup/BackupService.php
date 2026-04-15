<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Backup;

use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class BackupService
{
    /**
     * Backup configuration
     */
    protected array $config;

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Create a backup of the lang directory
     *
     * @return string Backup directory path
     */
    public function backup(): string
    {
        $langPath = base_path('lang');
        $backupPath = $this->getBackupPath();
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupDir = $backupPath . DIRECTORY_SEPARATOR . $timestamp;

        // Create backup directory
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        // Copy all language files
        if (File::exists($langPath)) {
            File::copyDirectory($langPath, $backupDir);
        }

        // Clean old backups
        $this->cleanOldBackups();

        return $backupDir;
    }

    /**
     * Backup a specific file
     *
     * @param string $filePath Full path to the file
     * @return string|null Backup file path
     */
    public function backupFile(string $filePath): ?string
    {
        if (!File::exists($filePath)) {
            return null;
        }

        $langPath = base_path('lang');
        $relativePath = str_replace($langPath . DIRECTORY_SEPARATOR, '', $filePath);
        
        $backupPath = $this->getBackupPath();
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupDir = $backupPath . DIRECTORY_SEPARATOR . $timestamp;
        $backupFile = $backupDir . DIRECTORY_SEPARATOR . $relativePath;

        // Create directory
        $backupFileDir = dirname($backupFile);
        if (!File::exists($backupFileDir)) {
            File::makeDirectory($backupFileDir, 0755, true);
        }

        // Copy file
        File::copy($filePath, $backupFile);

        return $backupFile;
    }

    /**
     * List all available backups
     *
     * @return array
     */
    public function listBackups(): array
    {
        $backupPath = $this->getBackupPath();

        if (!File::exists($backupPath)) {
            return [];
        }

        $backups = [];
        $directories = File::directories($backupPath);

        foreach ($directories as $dir) {
            $timestamp = basename($dir);
            $backups[] = [
                'timestamp' => $timestamp,
                'path' => $dir,
                'date' => $this->parseTimestamp($timestamp),
                'size' => $this->getDirectorySize($dir),
            ];
        }

        // Sort by timestamp (newest first)
        usort($backups, function ($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });

        return $backups;
    }

    /**
     * Restore from a backup
     *
     * @param string $timestamp Backup timestamp
     * @return bool
     */
    public function restore(string $timestamp): bool
    {
        $backupPath = $this->getBackupPath();
        $backupDir = $backupPath . DIRECTORY_SEPARATOR . $timestamp;

        if (!File::exists($backupDir)) {
            throw new \RuntimeException("Backup not found: {$timestamp}");
        }

        $langPath = base_path('lang');

        // Backup current state before restoring (safety!)
        $this->backup();

        // Remove current lang directory
        if (File::exists($langPath)) {
            File::deleteDirectory($langPath);
        }

        // Restore from backup
        File::copyDirectory($backupDir, $langPath);

        return true;
    }

    /**
     * Delete old backups keeping only the configured number
     */
    protected function cleanOldBackups(): void
    {
        $keep = $this->config['keep'] ?? 5;
        $backups = $this->listBackups();

        if (count($backups) <= $keep) {
            return;
        }

        // Delete oldest backups
        $toDelete = array_slice($backups, $keep);
        
        foreach ($toDelete as $backup) {
            File::deleteDirectory($backup['path']);
        }
    }

    /**
     * Get backup directory path
     */
    protected function getBackupPath(): string
    {
        return $this->config['path'] ?? base_path('lang/.backup');
    }

    /**
     * Parse timestamp to Carbon instance
     */
    protected function parseTimestamp(string $timestamp): ?Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d_H-i-s', $timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get directory size in bytes
     */
    protected function getDirectorySize(string $path): int
    {
        $size = 0;
        $files = File::allFiles($path);

        foreach ($files as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    /**
     * Format bytes to human readable
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Check if backup is enabled
     */
    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }
}