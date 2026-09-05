<?php

use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Services\Backup\BackupService;

describe('BackupService', function () {

    beforeEach(function () {
        // Create test directory
        $this->testPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'backup-test-'.time();

        // Create lang directory with test files
        $langPath = $this->testPath.DIRECTORY_SEPARATOR.'lang';
        File::makeDirectory($langPath.DIRECTORY_SEPARATOR.'en', 0755, true);
        File::makeDirectory($langPath.DIRECTORY_SEPARATOR.'ar', 0755, true);

        File::put($langPath.'/en/auth.php', '<?php return ["login" => "Login"];');
        File::put($langPath.'/ar/auth.php', '<?php return ["login" => "تسجيل الدخول"];');

        // Create backup service
        $this->config = [
            'enabled' => true,
            'lang_path' => $langPath,
            'path' => $langPath.DIRECTORY_SEPARATOR.'.backup',
            'keep' => 3,
        ];

        $this->backupService = new BackupService($this->config);
    });

    afterEach(function () {
        // Clean up
        if (File::exists($this->testPath)) {
            File::deleteDirectory($this->testPath);
        }
    });

    it('creates a full backup', function () {
        $backupDir = $this->backupService->backup();

        expect(File::exists($backupDir))->toBeTrue();
        expect(File::exists($backupDir.'/en/auth.php'))->toBeTrue();
        expect(File::exists($backupDir.'/ar/auth.php'))->toBeTrue();
    });

    it('creates backup with timestamp directory', function () {
        $backupDir = $this->backupService->backup();
        $dirName = basename($backupDir);

        // Check timestamp format: YYYY-MM-DD_HH-MM-SS
        expect($dirName)->toMatch('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}$/');
    });

    it('backs up a specific file', function () {
        $langPath = $this->testPath.'/lang';
        $filePath = $langPath.'/en/auth.php';

        $backupFile = $this->backupService->backupFile($filePath);

        expect($backupFile)->not->toBeNull();
        expect(File::exists($backupFile))->toBeTrue();

        // Check content is same
        $original = File::get($filePath);
        $backed = File::get($backupFile);
        expect($backed)->toBe($original);
    });

    it('returns null when backing up non-existent file', function () {
        $result = $this->backupService->backupFile('/non/existent/file.php');

        expect($result)->toBeNull();
    });

    it('lists all backups', function () {
        // Create 3 backups
        $this->backupService->backup();
        sleep(1);
        $this->backupService->backup();
        sleep(1);
        $this->backupService->backup();

        $backups = $this->backupService->listBackups();

        expect($backups)->toHaveCount(3);
        expect($backups[0])->toHaveKey('timestamp');
        expect($backups[0])->toHaveKey('path');
        expect($backups[0])->toHaveKey('date');
        expect($backups[0])->toHaveKey('size');
    });

    it('lists backups in descending order (newest first)', function () {
        $this->backupService->backup();
        sleep(1);
        $this->backupService->backup();
        sleep(1);
        $this->backupService->backup();

        $backups = $this->backupService->listBackups();

        expect($backups[0]['timestamp'])->toBeGreaterThan($backups[1]['timestamp']);
        expect($backups[1]['timestamp'])->toBeGreaterThan($backups[2]['timestamp']);
    });

    it('cleans old backups keeping configured number', function () {
        // Config says keep 3, create 5
        for ($i = 0; $i < 5; $i++) {
            $this->backupService->backup();
            sleep(1);
        }

        $backups = $this->backupService->listBackups();

        expect(count($backups))->toBe(3); // Only 3 kept
    });

    it('restores from a backup', function () {
        $langPath = $this->testPath.'/lang';

        // Create backup
        $this->backupService->backup();
        $backups = $this->backupService->listBackups();
        $timestamp = $backups[0]['timestamp'];

        // Modify current files
        File::put($langPath.'/en/auth.php', '<?php return ["login" => "MODIFIED"];');

        // Restore
        $restored = $this->backupService->restore($timestamp);

        expect($restored)->toBeTrue();

        // Check original content is back
        $content = File::get($langPath.'/en/auth.php');
        expect($content)->toContain('Login');
        expect($content)->not->toContain('MODIFIED');
    });

    it('throws exception when restoring non-existent backup', function () {
        expect(fn () => $this->backupService->restore('2020-01-01_00-00-00'))
            ->toThrow(RuntimeException::class, 'Backup not found');
    });

    it('creates backup before restoring (safety)', function () {
        // Create initial backup
        $this->backupService->backup();
        sleep(1);

        $backups = $this->backupService->listBackups();
        $initialCount = count($backups);
        $timestamp = $backups[0]['timestamp'];

        // Restore (should create new backup first)
        $this->backupService->restore($timestamp);

        $backupsAfter = $this->backupService->listBackups();

        // Should have one more backup
        expect(count($backupsAfter))->toBeGreaterThan($initialCount);
    });

    it('formats bytes to human readable', function () {
        expect($this->backupService->formatBytes(500))->toBe('500 B');
        expect($this->backupService->formatBytes(1024))->toBe('1 KB');
        expect($this->backupService->formatBytes(1048576))->toBe('1 MB');
        expect($this->backupService->formatBytes(2097152))->toBe('2 MB');
    });

    it('checks if backup is enabled', function () {
        $service = new BackupService(['enabled' => true]);
        expect($service->isEnabled())->toBeTrue();

        $service = new BackupService(['enabled' => false]);
        expect($service->isEnabled())->toBeFalse();

        $service = new BackupService([]); // Default
        expect($service->isEnabled())->toBeTrue();
    });
});
