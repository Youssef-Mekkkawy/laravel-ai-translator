<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Services\Backup\BackupService;
use Carbon\Carbon;

class ListBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:backup:list
                            {--details : Show detailed information including file list for each backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available translation backups';

    /**
     * BackupService instance
     */
    protected BackupService $backupService;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $config = config('laravel-ai-translator');
        $this->backupService = new BackupService($config['backup'] ?? []);

        $backups = $this->backupService->listBackups();

        if (empty($backups)) {
            $this->warn('⚠️  No backups found.');
            $this->comment("💡 Backups are created automatically when you run 'php artisan lang:translate'.");
            return self::SUCCESS;
        }

        $count = count($backups);
        $this->info("📦 Available backups ({$count} total):");
        $this->newLine();

        if ($this->option('details')) {
            $this->showDetailed($backups);
        } else {
            $this->showTable($backups);
        }

        $this->newLine();
        $this->comment("💡 Use 'php artisan lang:restore <timestamp>' to restore a backup.");

        return self::SUCCESS;
    }

    /**
     * Display backups in a compact summary table
     */
    protected function showTable(array $backups): void
    {
        $rows = [];

        foreach ($backups as $backup) {
            $rows[] = [
                $this->formatTimestamp($backup['timestamp']),
                $this->countFiles($backup['path']),
                $this->backupService->formatBytes($backup['size']),
                $this->formatAge($backup['date']),
            ];
        }

        $this->table(['Timestamp', 'Files', 'Size', 'Age'], $rows);
    }

    /**
     * Display detailed information for each backup
     */
    protected function showDetailed(array $backups): void
    {
        foreach ($backups as $index => $backup) {
            if ($index > 0) {
                $this->newLine();
                $this->line(str_repeat('─', 60));
                $this->newLine();
            }

            $languages = $this->detectLanguages($backup['path']);
            $fileCount = $this->countFiles($backup['path']);

            $this->info("📦 Backup: " . $this->formatTimestamp($backup['timestamp']));
            $this->newLine();

            $this->table(
                ['Field', 'Value'],
                [
                    ['Languages', implode(', ', $languages) ?: 'n/a'],
                    ['Files', $fileCount],
                    ['Size', $this->backupService->formatBytes($backup['size'])],
                    ['Created', $this->formatAge($backup['date'])],
                ]
            );

            $files = File::allFiles($backup['path']);

            if (!empty($files)) {
                $this->newLine();
                $this->line('Files included:');

                foreach ($files as $file) {
                    $relativePath = $file->getRelativePathname();
                    $size = $this->backupService->formatBytes($file->getSize());
                    $this->line("  - {$relativePath} ({$size})");
                }
            }
        }
    }

    /**
     * Count the number of files in a backup directory
     */
    protected function countFiles(string $path): int
    {
        if (!File::exists($path)) {
            return 0;
        }

        return count(File::allFiles($path));
    }

    /**
     * Detect language subdirectories in a backup directory
     */
    protected function detectLanguages(string $backupPath): array
    {
        if (!File::exists($backupPath)) {
            return [];
        }

        return array_map('basename', File::directories($backupPath));
    }

    /**
     * Format a raw timestamp string (Y-m-d_H-i-s) for display
     */
    protected function formatTimestamp(string $timestamp): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d_H-i-s', $timestamp)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return $timestamp;
        }
    }

    /**
     * Return a human-readable age string for the given Carbon date
     */
    protected function formatAge(?Carbon $date): string
    {
        if ($date === null) {
            return 'unknown';
        }

        return $date->diffForHumans();
    }
}
