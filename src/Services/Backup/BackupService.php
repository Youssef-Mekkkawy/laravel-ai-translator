<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services\Backup;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class BackupService
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    protected function getLangPath(): string
    {
        return $this->config['lang_path'] ?? lang_path();
    }

    protected function getBackupPath(): string
    {
        return $this->config['path'] ?? base_path('lang/.backup');
    }

    /**
     * Generate a unique timestamp for a new backup directory.
     * Keeps the standard Y-m-d_H-i-s format when possible.
     * If a directory with that timestamp already exists (collision),
     * appends microseconds so the existing backup is never overwritten.
     */
    protected function makeTimestamp(): string
    {
        $base = Carbon::now()->format('Y-m-d_H-i-s');
        $dir = $this->getBackupPath().DIRECTORY_SEPARATOR.$base;

        if (! File::exists($dir)) {
            return $base;
        }

        // Collision — append microseconds to make it unique
        $micro = sprintf('%06d', (int) (fmod(microtime(true), 1) * 1_000_000));

        return $base.'_'.$micro;
    }

    /**
     * Create a full backup of the lang directory.
     *
     * @return string Backup directory path
     */
    public function backup(): string
    {
        $langPath = $this->getLangPath();
        $backupPath = $this->getBackupPath();
        $timestamp = $this->makeTimestamp();
        $backupDir = $backupPath.DIRECTORY_SEPARATOR.$timestamp;

        if (! File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        if (File::exists($langPath)) {
            $backupDirName = basename($backupPath);

            foreach (File::directories($langPath) as $dir) {
                if (basename($dir) !== $backupDirName) {
                    File::copyDirectory($dir, $backupDir.DIRECTORY_SEPARATOR.basename($dir));
                }
            }

            foreach (File::files($langPath) as $file) {
                File::copy($file->getPathname(), $backupDir.DIRECTORY_SEPARATOR.$file->getFilename());
            }
        }

        $this->cleanOldBackups();

        return $backupDir;
    }

    /**
     * Backup a specific file.
     */
    public function backupFile(string $filePath): ?string
    {
        if (! File::exists($filePath)) {
            return null;
        }

        $sep = DIRECTORY_SEPARATOR;
        $langPath = rtrim(str_replace(['/', '\\'], $sep, $this->getLangPath()), $sep);
        $normalizedFile = str_replace(['/', '\\'], $sep, $filePath);
        $relativePath = ltrim(str_replace($langPath.$sep, '', $normalizedFile), $sep);

        $backupPath = $this->getBackupPath();
        $timestamp = $this->makeTimestamp();
        $backupDir = $backupPath.DIRECTORY_SEPARATOR.$timestamp;
        $backupFile = $backupDir.DIRECTORY_SEPARATOR.$relativePath;
        $backupFileDir = dirname($backupFile);

        if (! File::exists($backupFileDir)) {
            File::makeDirectory($backupFileDir, 0755, true);
        }

        File::copy($filePath, $backupFile);
        $this->cleanOldBackups();

        return $backupFile;
    }

    /**
     * List all available backups, newest first.
     */
    public function listBackups(): array
    {
        $backupPath = $this->getBackupPath();

        if (! File::exists($backupPath)) {
            return [];
        }

        $backups = [];

        foreach (File::directories($backupPath) as $dir) {
            $timestamp = basename($dir);
            $backups[] = [
                'timestamp' => $timestamp,
                'path' => $dir,
                'date' => $this->parseTimestamp($timestamp),
                'size' => $this->getDirectorySize($dir),
            ];
        }

        usort($backups, fn ($a, $b) => strcmp($b['timestamp'], $a['timestamp']));

        return $backups;
    }

    /**
     * Restore from a backup.
     */
    public function restore(string $timestamp): bool
    {
        $backupPath = $this->getBackupPath();
        $backupDir = $backupPath.DIRECTORY_SEPARATOR.$timestamp;

        if (! File::exists($backupDir)) {
            throw new \RuntimeException("Backup not found: {$timestamp}");
        }

        $langPath = $this->getLangPath();

        // Safety backup of current state.
        // makeTimestamp() detects collision and appends microseconds if needed,
        // so the original backup is never overwritten.
        $this->backup();

        if (! File::exists($backupDir)) {
            throw new \RuntimeException("Backup removed during safety-backup cleanup: {$timestamp}");
        }

        // Remove all lang files/dirs except the backup folder itself
        $backupDirName = basename($backupPath);

        foreach (File::directories($langPath) as $dir) {
            if (basename($dir) !== $backupDirName) {
                File::deleteDirectory($dir);
            }
        }

        foreach (File::files($langPath) as $file) {
            File::delete($file->getPathname());
        }

        File::copyDirectory($backupDir, $langPath);

        return true;
    }

    protected function cleanOldBackups(): void
    {
        $keep = $this->config['keep'] ?? 5;
        $backups = $this->listBackups();

        if (count($backups) <= $keep) {
            return;
        }

        foreach (array_slice($backups, $keep) as $backup) {
            File::deleteDirectory($backup['path']);
        }
    }

    protected function parseTimestamp(string $timestamp): ?Carbon
    {
        // Try standard format first, then with microsecond suffix
        foreach (['Y-m-d_H-i-s', 'Y-m-d_H-i-s_u'] as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $timestamp);
                if ($dt !== false) {
                    return $dt;
                }
            } catch (\Exception $e) {
                // try next
            }
        }

        return null;
    }

    protected function getDirectorySize(string $path): int
    {
        $size = 0;
        foreach (File::allFiles($path) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;

        return round($bytes / pow(1024, $power), 2).' '.$units[$power];
    }

    public function isEnabled(): bool
    {
        return $this->config['enabled'] ?? true;
    }
}
