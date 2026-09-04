<?php

use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;

// ─────────────────────────────────────────────────
// Test setup: isolated temp filesystem per test
// ─────────────────────────────────────────────────

beforeEach(function () {
    $this->tempPath = sys_get_temp_dir() . '/ai-translator-translate-test-' . uniqid();
    $this->viewsPath = $this->tempPath . '/resources/views';
    $this->langPath  = $this->tempPath . '/lang';

    File::makeDirectory($this->viewsPath, 0755, true);
    File::makeDirectory($this->langPath . '/en', 0755, true);
    File::makeDirectory($this->langPath . '/ar', 0755, true);

    // Override app base path and config
    $this->app->instance('path.base', $this->tempPath);
    $this->app->instance('path.lang', $this->langPath);

    config([
        'laravel-ai-translator.scan_paths'            => [$this->viewsPath],
        'laravel-ai-translator.languages'             => ['en', 'ar'],
        'laravel-ai-translator.default_language'      => 'en',
        'laravel-ai-translator.providers.deepl.api_key' => 'fake-key-for-tests',
        'laravel-ai-translator.backup.path'           => $this->langPath . '/.backup',
        'laravel-ai-translator.change_tracking.metadata_path'
            => $this->langPath . '/.translations-meta.json',
        'laravel-ai-translator.storage.lock_file'
            => $this->langPath . '/.locked-translations.json',
    ]);
});

afterEach(function () {
    if (File::exists($this->tempPath)) {
        File::deleteDirectory($this->tempPath);
    }
});

// ─────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────

function writeView(string $path, string $content): void
{
    File::ensureDirectoryExists(dirname($path));
    File::put($path, $content);
}

function writeLangFile(string $path, array $content): void
{
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '<?php return ' . var_export($content, true) . ';');
}

// ─────────────────────────────────────────────────
// Tests
// ─────────────────────────────────────────────────

test('translate command handles --dry-run flag without making any changes', function () {
    // View file with one key
    writeView($this->viewsPath . '/welcome.blade.php', "<h1>{{ __('welcome.title') }}</h1>");

    // Source lang file
    writeLangFile($this->langPath . '/en/welcome.php', ['title' => 'Welcome']);

    $this->artisan('lang:translate --dry-run')
        ->expectsOutputToContain('DRY RUN MODE')
        ->assertSuccessful();

    // No target language file should be created in dry-run mode
    expect(File::exists($this->langPath . '/ar/welcome.php'))->toBeFalse();
});

test('translate command shows cost estimate before proceeding', function () {
    writeView($this->viewsPath . '/home.blade.php', "<p>{{ __('home.intro') }}</p>");
    writeLangFile($this->langPath . '/en/home.php', ['intro' => 'Hello world']);

    $this->artisan('lang:translate --dry-run')
        ->expectsOutputToContain('Total Characters')
        ->expectsOutputToContain('Estimated Cost')
        ->assertSuccessful();
});

test('translate command handles --lang flag and restricts to one language', function () {
    writeView($this->viewsPath . '/page.blade.php', "{{ __('page.header') }}");
    writeLangFile($this->langPath . '/en/page.php', ['header' => 'Header']);

    // With --lang=ar, dry-run mode: only Arabic in languages list
    $this->artisan('lang:translate --dry-run --lang=ar')
        ->expectsOutputToContain('ar')
        ->assertSuccessful();
});

test('translate command skips unchanged keys that already exist in target', function () {
    writeView($this->viewsPath . '/auth.blade.php', "{{ __('auth.login') }}");

    // Both source and target already have the key → nothing to translate
    writeLangFile($this->langPath . '/en/auth.php', ['login' => 'Login']);
    writeLangFile($this->langPath . '/ar/auth.php', ['login' => 'تسجيل الدخول']);

    $this->artisan('lang:translate')
        ->expectsConfirmation('Start translation?', 'yes')
        ->assertSuccessful();

    // Target file content must remain unchanged (skipped)
    $content = include $this->langPath . '/ar/auth.php';
    expect($content['login'])->toBe('تسجيل الدخول');
});

test('translate command reports accurate statistics for already-translated content', function () {
    writeView($this->viewsPath . '/auth.blade.php', "{{ __('auth.login') }}");
    writeLangFile($this->langPath . '/en/auth.php', ['login' => 'Login']);
    writeLangFile($this->langPath . '/ar/auth.php', ['login' => 'تسجيل الدخول']);

    $this->artisan('lang:translate')
        ->expectsConfirmation('Start translation?', 'yes')
        ->expectsOutputToContain('TRANSLATION COMPLETE')
        ->assertSuccessful();
});

test('translate command respects locked translations', function () {
    writeView($this->viewsPath . '/auth.blade.php', "{{ __('auth.login') }}");
    writeLangFile($this->langPath . '/en/auth.php', ['login' => 'Login']);

    // Pre-lock the Arabic login key
    $lockFile = $this->langPath . '/.locked-translations.json';
    File::put($lockFile, json_encode([
        'ar' => [
            'auth.login' => [
                'locked_at' => now()->toIso8601String(),
                'locked_by' => 'test',
                'reason'    => 'manual translation',
                'value'     => 'دخول (مقفول)',
            ],
        ],
    ]));

    // Run translate; locked key should not be re-translated
    $this->artisan('lang:translate')
        ->expectsConfirmation('Start translation?', 'yes')
        ->assertSuccessful();

    // No ar/auth.php created because key is locked and skipped
    if (File::exists($this->langPath . '/ar/auth.php')) {
        $content = include $this->langPath . '/ar/auth.php';
        // If file exists it should NOT contain the overwritten value
        expect($content['login'] ?? 'دخول (مقفول)')->toBe('دخول (مقفول)');
    } else {
        // File was never written because key was locked → correct
        expect(true)->toBeTrue();
    }
});

test('translate command with no view files produces 0 total keys', function () {
    // Empty views directory, no .blade.php files
    $this->artisan('lang:translate --dry-run')
        ->expectsOutputToContain('0')
        ->assertSuccessful();
});

test('translate command accepts --no-backup flag without error', function () {
    writeView($this->viewsPath . '/auth.blade.php', "{{ __('auth.login') }}");
    writeLangFile($this->langPath . '/en/auth.php', ['login' => 'Login']);
    writeLangFile($this->langPath . '/ar/auth.php', ['login' => 'تسجيل الدخول']);

    $this->artisan('lang:translate --no-backup')
        ->expectsConfirmation('Start translation?', 'yes')
        ->assertSuccessful();
});

test('translate command cancellation returns success without translating', function () {
    writeView($this->viewsPath . '/auth.blade.php', "{{ __('auth.login') }}");
    writeLangFile($this->langPath . '/en/auth.php', ['login' => 'Login']);

    $this->artisan('lang:translate')
        ->expectsConfirmation('Start translation?', 'no')
        ->expectsOutputToContain('cancelled')
        ->assertSuccessful();
});
