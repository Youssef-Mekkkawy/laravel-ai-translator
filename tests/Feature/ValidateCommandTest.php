<?php

use Illuminate\Support\Facades\File;

// ─────────────────────────────────────────────────
// Test setup: isolated temp filesystem per test
// ─────────────────────────────────────────────────

beforeEach(function () {
    $this->tempPath = sys_get_temp_dir().'/ai-translator-validate-test-'.uniqid();
    $this->langPath = $this->tempPath.'/lang';

    File::makeDirectory($this->langPath.'/en', 0755, true);
    File::makeDirectory($this->langPath.'/ar', 0755, true);
    File::makeDirectory($this->langPath.'/fr', 0755, true);

    $this->app->useLangPath($this->langPath);

    config([
        'laravel-ai-translator.languages' => ['en', 'ar', 'fr'],
        'laravel-ai-translator.default_language' => 'en',
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

function writeLang(string $path, array $content): void
{
    File::ensureDirectoryExists(dirname($path));
    File::put($path, '<?php return '.var_export($content, true).';');
}

// ─────────────────────────────────────────────────
// Tests
// ─────────────────────────────────────────────────

test('validate command passes when all translations are complete', function () {
    writeLang($this->langPath.'/en/auth.php', ['login' => 'Login', 'logout' => 'Logout']);
    writeLang($this->langPath.'/ar/auth.php', ['login' => 'تسجيل الدخول', 'logout' => 'تسجيل الخروج']);
    writeLang($this->langPath.'/fr/auth.php', ['login' => 'Connexion', 'logout' => 'Déconnexion']);

    // 🟢 Restored to proper assertion using the exact output text
    $this->artisan('lang:validate')
        ->expectsOutputToContain('All translations valid')
        ->expectsOutputToContain('All keys present')
        ->assertSuccessful();
});

test('validate command detects missing translations', function () {
    writeLang($this->langPath.'/en/auth.php', ['login' => 'Login', 'logout' => 'Logout', 'register' => 'Register']);
    // ar is missing 'register'
    writeLang($this->langPath.'/ar/auth.php', ['login' => 'تسجيل الدخول', 'logout' => 'تسجيل الخروج']);
    writeLang($this->langPath.'/fr/auth.php', ['login' => 'Connexion', 'logout' => 'Déconnexion', 'register' => 'S\'inscrire']);

    $this->artisan('lang:validate')
        ->expectsOutputToContain('missing')
        ->expectsOutputToContain('auth.register')
        ->assertFailed();
});

test('validate command detects colon-style placeholder mismatches', function () {
    writeLang($this->langPath.'/en/messages.php', ['greeting' => 'Hello :name, you have :count messages.']);
    // ar translation is missing :count
    writeLang($this->langPath.'/ar/messages.php', ['greeting' => 'مرحبا :name']);
    writeLang($this->langPath.'/fr/messages.php', ['greeting' => 'Bonjour :name, vous avez :count messages.']);

    $this->artisan('lang:validate')
        ->expectsOutputToContain('placeholder')
        ->expectsOutputToContain('messages.greeting')
        ->assertFailed();
});

test('validate command passes when all placeholders are preserved', function () {
    writeLang($this->langPath.'/en/messages.php', ['greeting' => 'Hello :name']);
    writeLang($this->langPath.'/ar/messages.php', ['greeting' => 'مرحبا :name']);
    writeLang($this->langPath.'/fr/messages.php', ['greeting' => 'Bonjour :name']);

    $this->artisan('lang:validate')
        ->expectsOutputToContain('All placeholders preserved')
        ->assertSuccessful();
});

test('validate command detects index-style placeholder mismatches', function () {
    writeLang($this->langPath.'/en/pagination.php', ['nav' => 'Page {0} of {1}']);
    // ar missing {1}
    writeLang($this->langPath.'/ar/pagination.php', ['nav' => 'صفحة {0}']);
    writeLang($this->langPath.'/fr/pagination.php', ['nav' => 'Page {0} sur {1}']);

    $this->artisan('lang:validate')
        ->expectsOutputToContain('placeholder')
        ->assertFailed();
});

test('validate command detects HTML tag mismatches', function () {
    writeLang($this->langPath.'/en/welcome.php', ['title' => 'Welcome to <strong>our</strong> site']);
    // ar is missing <strong> tags
    writeLang($this->langPath.'/ar/welcome.php', ['title' => 'مرحبا بكم في موقعنا']);
    writeLang($this->langPath.'/fr/welcome.php', ['title' => 'Bienvenue sur <strong>notre</strong> site']);

    $this->artisan('lang:validate')
        ->expectsOutputToContain('HTML tag')
        ->expectsOutputToContain('welcome.title')
        ->assertFailed();
});

test('validate command passes when HTML tags are preserved', function () {
    writeLang($this->langPath.'/en/ui.php', ['label' => 'Click <a href="#">here</a>']);
    writeLang($this->langPath.'/ar/ui.php', ['label' => 'انقر <a href="#">هنا</a>']);
    writeLang($this->langPath.'/fr/ui.php', ['label' => 'Cliquez <a href="#">ici</a>']);

    $this->artisan('lang:validate')
        ->expectsOutputToContain('All HTML tags preserved')
        ->assertSuccessful();
});

test('validate command warns on excessive length (>3x source)', function () {
    $shortEnglish = 'Hi';
    $veryLongArabic = str_repeat('ط', 10); // 10 chars > 2 * 3 = 6 threshold

    writeLang($this->langPath.'/en/ui.php', ['greeting' => $shortEnglish]);
    writeLang($this->langPath.'/ar/ui.php', ['greeting' => $veryLongArabic]);
    writeLang($this->langPath.'/fr/ui.php', ['greeting' => 'Salut']);

    $this->artisan('lang:validate')
        ->expectsOutputToContain('length warning')
        ->assertSuccessful(); // warnings alone don't fail without --strict
});

test('validate command --strict flag returns failure on warnings', function () {
    $shortEnglish = 'Hi';
    $veryLongArabic = str_repeat('ط', 10);

    writeLang($this->langPath.'/en/ui.php', ['greeting' => $shortEnglish]);
    writeLang($this->langPath.'/ar/ui.php', ['greeting' => $veryLongArabic]);
    writeLang($this->langPath.'/fr/ui.php', ['greeting' => 'Salut']);

    $this->artisan('lang:validate --strict')
        ->assertFailed();
});

test('validate command --lang flag restricts check to one language', function () {
    writeLang($this->langPath.'/en/auth.php', ['login' => 'Login']);
    // ar has the key but fr does not
    writeLang($this->langPath.'/ar/auth.php', ['login' => 'تسجيل الدخول']);

    // Checking only ar should pass
    $this->artisan('lang:validate --lang=ar')
        ->expectsOutputToContain('keys present for [ar]')
        ->assertSuccessful();
});

test('validate command shows summary statistics', function () {
    writeLang($this->langPath.'/en/auth.php', ['login' => 'Login']);
    writeLang($this->langPath.'/ar/auth.php', ['login' => 'تسجيل الدخول']);
    writeLang($this->langPath.'/fr/auth.php', ['login' => 'Connexion']);

    $this->artisan('lang:validate')
        ->expectsOutputToContain('Summary')
        ->expectsOutputToContain('Total keys checked')
        ->assertSuccessful();
});
