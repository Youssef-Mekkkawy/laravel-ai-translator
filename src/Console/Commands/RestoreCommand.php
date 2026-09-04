<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Services\Backup\BackupService;
use Carbon\Carbon;

class RestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:restore
                            {timestamp? : Specific backup timestamp to restore (e.g. 2026-04-16_14-30-00)}
                            {--latest : Restore the most recent backup without prompting}
                            {--list : List available backups without restoring}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore translations from a backup';

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

        // --list flag: just display and exit
        if ($this->option('list')) {
            return $this->listBackups($backups);
        }

        if (empty($backups)) {
            $this->warn('⚠️  No backups available.');
            return self::SUCCESS;
        }

        // Resolve which backup to restore
        $selected = $this->resolveBackup($backups);

        if ($selected === null) {
            $this->warn('❌ No backup selected. Aborting.');
            return self::SUCCESS;
        }

        // Show backup details
        $this->showBackupDetails($selected);

        // Confirm
        if (!$this->confirmRestore()) {
            $this->warn('❌ Restore cancelled.');
            return self::SUCCESS;
        }

        return $this->performRestore($selected);
    }

    /**
     * Display the backups table and return SUCCESS
     */
    protected function listBackups(array $backups): int
    {
        if (empty($backups)) {
            $this->warn('⚠️  No backups available.');
            return self::SUCCESS;
        }

        $this->info('📦 Available backups:');
        $this->newLine();

        $rows = [];
        foreach ($backups as $index => $backup) {
            $rows[] = [
                '#' => $index + 1,
                'Timestamp' => $this->formatTimestamp($backup['timestamp']),
                'Files' => $this->countBackupFiles($backup['path']),
                'Size' => $this->backupService->formatBytes($backup['size']),
                'Age' => $this->formatAge($backup['date']),
            ];
        }

        $this->table(['#', 'Timestamp', 'Files', 'Size', 'Age'], $rows);
        $this->newLine();
        $this->comment("💡 Use 'php artisan lang:restore <timestamp>' to restore a specific backup.");

        return self::SUCCESS;
    }

    /**
     * Determine which backup to restore based on flags/argument/interactive choice
     */
    protected function resolveBackup(array $backups): ?array
    {
        // Specific timestamp argument provided
        $timestamp = $this->argument('timestamp');
        if ($timestamp) {
            foreach ($backups as $backup) {
                if ($backup['timestamp'] === $timestamp) {
                    return $backup;
                }
            }
            $this->error("❌ Backup not found: {$timestamp}");
            return null;
        }

        // --latest flag: return the newest backup (already sorted newest-first)
        if ($this->option('latest')) {
            return $backups[0];
        }

        // Interactive selection
        $this->info('📦 Available backups:');
        $this->newLine();

        $rows = [];
        foreach ($backups as $index => $backup) {
            $rows[] = [
                $index + 1,
                $this->formatTimestamp($backup['timestamp']),
                $this->countBackupFiles($backup['path']),
                $this->backupService->formatBytes($backup['size']),
                $this->formatAge($backup['date']),
            ];
        }

        $this->table(['#', 'Timestamp', 'Files', 'Size', 'Age'], $rows);
        $this->newLine();

        $choices = range(1, count($backups));
        $choiceStrings = array_map('strval', $choices);

        $answer = $this->ask('Which backup to restore?', '1');

        $index = (int) $answer - 1;

        if (!isset($backups[$index])) {
            $this->error("❌ Invalid selection: {$answer}");
            return null;
        }

        return $backups[$index];
    }

    /**
     * Show details for the selected backup
     */
    protected function showBackupDetails(array $backup): void
    {
        $this->newLine();
        $this->info('📋 Backup details:');

        $fileCount = $this->countBackupFiles($backup['path']);
        $languages = $this->detectLanguages($backup['path']);

        $this->table(
            ['Field', 'Value'],
            [
                ['Timestamp', $this->formatTimestamp($backup['timestamp'])],
                ['Files', $fileCount],
                ['Languages', implode(', ', $languages) ?: 'n/a'],
                ['Size', $this->backupService->formatBytes($backup['size'])],
                ['Age', $this->formatAge($backup['date'])],
            ]
        );
        $this->newLine();
    }

    /**
     * Confirm the restore operation with the user
     */
    protected function confirmRestore(): bool
    {
        $this->warn('⚠️  This will overwrite your current translations.');
        return $this->confirm('Continue?', false);
    }

    /**
     * Create safety backup, then restore the selected backup
     */
    protected function performRestore(array $backup): int
    {
        // Safety backup of current state
        $this->info('💾 Creating safety backup of current state...');
        $safetyPath = $this->backupService->backup();
        $safetyName = basename($safetyPath);
        $this->line("  <fg=green>✓</> Safety backup created: {$safetyName}");
        $this->newLine();

        // Restore
        $this->info('🔄 Restoring files...');

        try {
            $langPath = base_path('lang');
            $backupDir = $backup['path'];

            // Remove current lang dir (excluding .backup subdirectory)
            $this->removeLangFiles($langPath);

            // Copy files from backup
            $fileCount = $this->copyBackupFiles($backupDir, $langPath);

            $this->line("  <fg=green>✓</> Restored {$fileCount} file(s)");
            $this->newLine();
            $this->info('✅ Restore complete!');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Restore failed: ' . $e->getMessage());
            $this->warn("💡 Your safety backup is available: {$safetyName}");
            return self::FAILURE;
        }
    }

    /**
     * Remove all PHP language files from lang/ without touching .backup/
     */
    protected function removeLangFiles(string $langPath): void
    {
        if (!File::exists($langPath)) {
            return;
        }

        foreach (File::directories($langPath) as $dir) {
            // Skip the backup directory
            if (basename($dir) === '.backup') {
                continue;
            }
            File::deleteDirectory($dir);
        }

        foreach (File::files($langPath) as $file) {
            File::delete($file->getPathname());
        }
    }

    /**
     * Copy files from a backup directory to the lang directory
     *
     * @return int Number of files copied
     */
    protected function copyBackupFiles(string $source, string $destination): int
    {
        $count = 0;

        foreach (File::allFiles($source) as $file) {
            // Skip anything inside a nested .backup directory
            if (str_contains($file->getPath(), DIRECTORY_SEPARATOR . '.backup')) {
                continue;
            }

            $relative = $file->getRelativePathname();
            $target = $destination . DIRECTORY_SEPARATOR . $relative;
            $targetDir = dirname($target);

            if (!File::exists($targetDir)) {
                File::makeDirectory($targetDir, 0755, true);
            }

            File::copy($file->getPathname(), $target);
            $count++;
        }

        return $count;
    }

    /**
     * Count the number of files in a backup directory
     */
    protected function countBackupFiles(string $path): int
    {
        if (!File::exists($path)) {
            return 0;
        }

        return count(File::allFiles($path));
    }

    /**
     * Detect language codes from backup subdirectories
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
            return Carbon::createFromFormat('Y-m-d_H-i-s', $timestamp)->format('Y-m-d H:i:s');
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
