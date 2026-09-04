<?php

use Illuminate\Support\Facades\File;

// ─────────────────────────────────────────────────
// Test setup: isolated temp filesystem per test
// ─────────────────────────────────────────────────

beforeEach(function () {
    $this->tempPath = sys_get_temp_dir() . '/ai-translator-lock-test-' . uniqid();
    $this->langPath = $this->tempPath . '/lang';

    File::makeDirectory($this->langPath . '/ar', 0755, true);
    File::makeDirectory($this->langPath . '/fr', 0755, true);

    // Write a source translation file so keys exist
    File::put($this->langPath . '/ar/auth.php', '<?php return ["login" => "تسجيل الدخول", "logout" => "تسجيل الخروج"];');
    File::put($this->langPath . '/fr/auth.php', '<?php return ["login" => "Connexion"];');

    $this->app->useLangPath($this->langPath);
});

afterEach(function () {
    if (File::exists($this->tempPath)) {
        File::deleteDirectory($this->tempPath);
    }
});

// ─────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────

function lockFilePath(string $langPath): string
{
    return $langPath . '/.locked-translations.json';
}

function readLocks(string $langPath): array
{
    $file = lockFilePath($langPath);
    return File::exists($file) ? json_decode(File::get($file), true) ?? [] : [];
}

// ─────────────────────────────────────────────────
// Tests
// ─────────────────────────────────────────────────

test('lock command locks a translation key', function () {
    $this->artisan('lang:lock ar auth.login')
        ->expectsOutputToContain('Locked')
        ->assertSuccessful();

    $locks = readLocks($this->langPath);

    expect($locks)->toHaveKey('ar');
    expect($locks['ar'])->toHaveKey('auth.login');
    expect($locks['ar']['auth.login']['locked_at'])->not->toBeEmpty();
});

test('lock command stores the reason when provided', function () {
    $this->artisan('lang:lock ar auth.login --reason="Manual review"')
        ->assertSuccessful();

    $locks = readLocks($this->langPath);
    expect($locks['ar']['auth.login']['reason'])->toBe('Manual review');
});

test('lock command warns when key is already locked', function () {
    // Lock it once
    $this->artisan('lang:lock ar auth.login')->assertSuccessful();

    // Attempt to lock again — should warn and ask if user wants to update
    $this->artisan('lang:lock ar auth.login')
        ->expectsOutputToContain('already locked')
        ->expectsConfirmation('Do you want to update the lock?', 'no')
        ->assertSuccessful();
});

test('unlock command removes a locked translation', function () {
    // First lock it
    $this->artisan('lang:lock ar auth.login')->assertSuccessful();
    $locks = readLocks($this->langPath);
    expect($locks['ar'])->toHaveKey('auth.login');

    // Now unlock it
    $this->artisan('lang:unlock ar auth.login')
        ->expectsOutputToContain('Unlocked')
        ->assertSuccessful();

    $locks = readLocks($this->langPath);
    expect($locks['ar'] ?? [])->not->toHaveKey('auth.login');
});

test('locked command shows all locked translations', function () {
    // Lock two keys
    $this->artisan('lang:lock ar auth.login')->assertSuccessful();
    $this->artisan('lang:lock ar auth.logout')->assertSuccessful();

    $this->artisan('lang:locked')
        ->expectsOutputToContain('auth.login')
        ->expectsOutputToContain('auth.logout')
        ->assertSuccessful();
});

test('locked command filters by language with --lang flag', function () {
    $this->artisan('lang:lock ar auth.login')->assertSuccessful();
    $this->artisan('lang:lock fr auth.login')->assertSuccessful();

    // --lang=ar should show ar locks only
    $this->artisan('lang:locked --lang=ar')
        ->expectsOutputToContain('ar')
        ->assertSuccessful();
});

test('locked command shows no-locks message when nothing is locked', function () {
    $this->artisan('lang:locked')
        ->expectsOutputToContain('No locked translations')
        ->assertSuccessful();
});

test('unlock command reports failure when key is not locked', function () {
    $this->artisan('lang:unlock ar auth.login')
        ->expectsOutputToContain('not locked')
        ->assertSuccessful();
});

test('lock command still locks even when translation key does not exist yet', function () {
    // 'auth.future' does not exist in the language file
    $this->artisan('lang:lock ar auth.future')
        ->assertSuccessful();

    $locks = readLocks($this->langPath);
    expect($locks['ar'])->toHaveKey('auth.future');
});
