<?php

use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\LanguageFileWriter;
use YoussefMekkkawy\LaravelAiTranslator\Services\Backup\BackupService;
use Illuminate\Support\Facades\File;

describe('LanguageFileWriter', function () {
    
    beforeEach(function () {
        // Create test directory
        $this->testPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lang-writer-test-' . time();

        // Create lang directory
        $langPath = $this->testPath . DIRECTORY_SEPARATOR . 'lang';
        File::makeDirectory($langPath, 0755, true);
        
        // Create backup service
        $backupConfig = [
            'enabled' => true,
            'lang_path' => $langPath,
            'path' => $langPath . DIRECTORY_SEPARATOR . '.backup',
            'keep' => 5,
        ];

        $writerConfig = ['lang_path' => $langPath];

        $this->backupService = new BackupService($backupConfig);
        $this->writer = new LanguageFileWriter($this->backupService, $writerConfig);
    });

    afterEach(function () {
        // Clean up test directory
        if (File::exists($this->testPath)) {
            File::deleteDirectory($this->testPath);
        }
    });

    // Task 6.3.1: Test file creation
    it('creates a new translation file', function () {
        $translations = [
            'welcome' => 'مرحبا',
            'hello' => 'أهلا',
        ];

        $filePath = $this->writer->write('ar', 'welcome', $translations, false);

        expect(File::exists($filePath))->toBeTrue();
        
        $content = include $filePath;
        expect($content)->toBeArray()
            ->and($content['welcome'])->toBe('مرحبا')
            ->and($content['hello'])->toBe('أهلا');
    });

    it('creates language directory if missing', function () {
        $langPath = $this->testPath . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'fr';
        
        expect(File::exists($langPath))->toBeFalse();

        $this->writer->write('fr', 'test', ['key' => 'value']);

        expect(File::exists($langPath))->toBeTrue();
    });

    // Task 6.3.2: Test array structure preservation
    it('preserves existing translations when merging', function () {
        // First write
        $original = [
            'existing' => 'Original value',
            'old' => 'Old translation',
        ];
        $this->writer->write('ar', 'test', $original, false);

        // Second write (merge)
        $new = [
            'new' => 'New translation',
            'existing' => 'Updated value',
        ];
        $this->writer->write('ar', 'test', $new, true);

        // Read result
        $result = $this->writer->read('ar', 'test');

        expect($result)->toHaveKey('old')
            ->and($result['old'])->toBe('Old translation')
            ->and($result['new'])->toBe('New translation')
            ->and($result['existing'])->toBe('Updated value');
    });

    it('replaces all content when not merging', function () {
        // First write
        $this->writer->write('ar', 'test', ['old' => 'value'], false);

        // Second write (replace)
        $this->writer->write('ar', 'test', ['new' => 'value'], false);

        $result = $this->writer->read('ar', 'test');

        expect($result)->not->toHaveKey('old')
            ->and($result)->toHaveKey('new');
    });

    // Task 6.3.3: Test nested array handling
    it('handles nested translations correctly', function () {
        $translations = [
            'auth' => [
                'login' => 'تسجيل الدخول',
                'password' => [
                    'reset' => 'إعادة تعيين كلمة المرور',
                    'confirm' => 'تأكيد كلمة المرور',
                ],
            ],
            'simple' => 'قيمة بسيطة',
        ];

        $this->writer->write('ar', 'auth', $translations, false);
        $result = $this->writer->read('ar', 'auth');

        expect($result['auth'])->toBeArray()
            ->and($result['auth']['login'])->toBe('تسجيل الدخول')
            ->and($result['auth']['password'])->toBeArray()
            ->and($result['auth']['password']['reset'])->toBe('إعادة تعيين كلمة المرور');
    });

    it('organizes flat keys into nested structure', function () {
        $flatTranslations = [
            'auth.login' => 'تسجيل الدخول',
            'auth.password.reset' => 'إعادة تعيين',
            'welcome.title' => 'عنوان',
        ];

        $organized = $this->writer->organizeByFile($flatTranslations);

        expect($organized)->toHaveKey('auth')
            ->and($organized)->toHaveKey('welcome')
            ->and($organized['auth']['login'])->toBe('تسجيل الدخول')
            ->and($organized['auth']['password']['reset'])->toBe('إعادة تعيين')
            ->and($organized['welcome']['title'])->toBe('عنوان');
    });

    // Task 6.3.4: Test formatting consistency
    it('generates properly formatted PHP code', function () {
        $translations = [
            'simple' => 'value',
            'nested' => [
                'key' => 'value',
            ],
        ];

        $filePath = $this->writer->write('ar', 'format', $translations, false);
        $content = File::get($filePath);

        // Check PHP opening tag
        expect($content)->toContain('<?php');
        
        // Check proper array syntax
        expect($content)->toContain("return [");
        expect($content)->toContain("'simple' => 'value'");
        
        // Check closing
        expect($content)->toEndWith(";\n");
    });

    it('adds auto-generated header to files', function () {
        $this->writer->write('ar', 'test', ['key' => 'value']);
        
        $filePath = $this->testPath . '/lang/ar/test.php';
        $content = File::get($filePath);

        expect($content)->toContain('Auto-generated Translation File')
            ->and($content)->toContain('Laravel AI Translator')
            ->and($content)->toContain('WARNING: Do not edit this file manually');
    });

    it('escapes special characters in values', function () {
        $translations = [
            'quote' => "It's a test",
            'backslash' => "Path: C:\\Users\\test",
            'mixed' => "Value with 'quotes' and \\ backslash",
        ];

        $this->writer->write('ar', 'special', $translations, false);
        $result = $this->writer->read('ar', 'special');

        expect($result['quote'])->toBe("It's a test")
            ->and($result['backslash'])->toBe("Path: C:\\Users\\test")
            ->and($result['mixed'])->toBe("Value with 'quotes' and \\ backslash");
    });

    // Task 6.3.5: Test backup functionality
    it('creates backup before overwriting', function () {
        // Write initial file
        $this->writer->write('ar', 'test', ['old' => 'value'], false);
        
        // Wait a second to ensure different timestamp
        sleep(1);
        
        // Overwrite (should create backup)
        $this->writer->write('ar', 'test', ['new' => 'value'], false);

        // Check backup exists
        $backups = $this->writer->listBackups();
        
        expect($backups)->not->toBeEmpty();
    });

    it('keeps configured number of backups', function () {
        // Create 7 backups (config says keep 5)
        for ($i = 0; $i < 7; $i++) {
            $this->writer->write('ar', 'test', ['version' => $i], false);
            sleep(1); // Different timestamps
        }

        $backups = $this->writer->listBackups();

        expect(count($backups))->toBeLessThanOrEqual(5);
    });

    it('can restore from backup', function () {
        // Write version 1 (no prior file, no backup created)
        $this->writer->write('ar', 'test', ['version' => 1], false);
        sleep(1);

        // Write version 2 — this creates a backup of version 1
        $this->writer->write('ar', 'test', ['version' => 2], false);

        // Get backup timestamp (this is the backup of version 1)
        $backups = $this->writer->listBackups();
        $timestamp = $backups[0]['timestamp'];

        // Verify version 2 is active
        $current = $this->writer->read('ar', 'test');
        expect($current['version'])->toBe(2);

        // Restore version 1
        $this->writer->restore($timestamp);

        // Verify version 1 is back
        $restored = $this->writer->read('ar', 'test');
        expect($restored['version'])->toBe(1);
    });

    it('lists backups in correct order (newest first)', function () {
        // Create 3 backups
        for ($i = 1; $i <= 3; $i++) {
            $this->writer->write('ar', 'test', ['version' => $i], false);
            sleep(1);
        }

        $backups = $this->writer->listBackups();

        expect($backups)->toHaveCount(3);
        
        // Check order (newest first)
        expect($backups[0]['timestamp'])->toBeGreaterThan($backups[1]['timestamp'])
            ->and($backups[1]['timestamp'])->toBeGreaterThan($backups[2]['timestamp']);
    });

    // Additional helper tests
    it('can write multiple files at once', function () {
        $fileTranslations = [
            'auth' => ['login' => 'تسجيل الدخول'],
            'welcome' => ['title' => 'عنوان'],
            'messages' => ['success' => 'نجح'],
        ];

        $written = $this->writer->writeMultiple('ar', $fileTranslations);

        expect($written)->toHaveKey('auth')
            ->and($written)->toHaveKey('welcome')
            ->and($written)->toHaveKey('messages')
            ->and(File::exists($written['auth']))->toBeTrue()
            ->and(File::exists($written['welcome']))->toBeTrue()
            ->and(File::exists($written['messages']))->toBeTrue();
    });

    it('checks if file exists', function () {
        expect($this->writer->exists('ar', 'nonexistent'))->toBeFalse();

        $this->writer->write('ar', 'exists', ['key' => 'value']);

        expect($this->writer->exists('ar', 'exists'))->toBeTrue();
    });

    it('gets all files for a language', function () {
        $this->writer->write('ar', 'auth', ['key' => 'value']);
        $this->writer->write('ar', 'welcome', ['key' => 'value']);
        $this->writer->write('ar', 'messages', ['key' => 'value']);

        $files = $this->writer->getFiles('ar');

        expect($files)->toContain('auth')
            ->and($files)->toContain('welcome')
            ->and($files)->toContain('messages');
    });

    it('can delete a file with backup', function () {
        $this->writer->write('ar', 'delete-test', ['key' => 'value']);
        
        expect($this->writer->exists('ar', 'delete-test'))->toBeTrue();

        $deleted = $this->writer->delete('ar', 'delete-test');

        expect($deleted)->toBeTrue()
            ->and($this->writer->exists('ar', 'delete-test'))->toBeFalse();
        
        // Check backup was created
        $backups = $this->writer->listBackups();
        expect($backups)->not->toBeEmpty();
    });
});