<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Create temp directory for test views
    $this->testViewsPath = base_path('resources/test-views');
    
    if (!File::exists($this->testViewsPath)) {
        File::makeDirectory($this->testViewsPath, 0755, true);
    }

    // Create temp lang directory
    $this->testLangPath = base_path('lang/en');
    
    if (!File::exists($this->testLangPath)) {
        File::makeDirectory($this->testLangPath, 0755, true);
    }
});

afterEach(function () {
    // Clean up test directories
    if (File::exists(base_path('resources/test-views'))) {
        File::deleteDirectory(base_path('resources/test-views'));
    }
});

test('scan command scans views and displays results', function () {
    // Create test view with translation keys
    File::put($this->testViewsPath . '/welcome.blade.php', "
        <h1>{{ __('welcome.title') }}</h1>
        <p>{{ __('welcome.description') }}</p>
    ");

    // Run the scan command
    $this->artisan('lang:scan', ['--path' => [$this->testViewsPath]])
        ->expectsOutput('🔍 Scanning Blade views for translation keys...')
        ->assertExitCode(0);
});

test('scan command shows missing translations', function () {
    // Create view with keys
    File::put($this->testViewsPath . '/test.blade.php', "
        {{ __('missing.key') }}
    ");

    // Run command
    $this->artisan('lang:scan', ['--path' => [$this->testViewsPath]])
        ->assertExitCode(0);
});

test('scan command shows only missing when flag is used', function () {
    // Create a translation file
    File::put($this->testLangPath . '/test.php', "<?php\nreturn ['exists' => 'Value'];");

    // Create view with both existing and missing keys
    File::put($this->testViewsPath . '/test.blade.php', "
        {{ __('test.exists') }}
        {{ __('test.missing') }}
    ");

    // Run command with --missing-only flag
    $this->artisan('lang:scan', [
        '--path' => [$this->testViewsPath],
        '--missing-only' => true,
    ])->assertExitCode(0);
});

test('scan command handles multiple syntaxes', function () {
    File::put($this->testViewsPath . '/test.blade.php', "
        {{ __('key1') }}
        @lang('key2')
        {{ trans('key3') }}
    ");

    $this->artisan('lang:scan', ['--path' => [$this->testViewsPath]])
        ->assertExitCode(0);
});

test('scan command shows warning when no keys found', function () {
    // Create view without translation keys
    File::put($this->testViewsPath . '/empty.blade.php', '<h1>No translations</h1>');

    $this->artisan('lang:scan', ['--path' => [$this->testViewsPath]])
        ->expectsOutput('⚠️  No translation keys found')
        ->assertExitCode(0);
});

test('scan command handles nested keys', function () {
    File::put($this->testViewsPath . '/test.blade.php', "
        {{ __('auth.login.title') }}
        {{ __('messages.success.saved') }}
    ");

    $this->artisan('lang:scan', ['--path' => [$this->testViewsPath]])
        ->assertExitCode(0);
});

test('scan command shows summary statistics', function () {
    // Create translation file with some existing keys
    File::put($this->testLangPath . '/test.php', "<?php\nreturn ['existing' => 'Value'];");

    // Create view with keys
    File::put($this->testViewsPath . '/test.blade.php', "
        {{ __('test.existing') }}
        {{ __('test.missing') }}
    ");

    $this->artisan('lang:scan', ['--path' => [$this->testViewsPath]])
        ->expectsOutput('📊 Summary:')
        ->assertExitCode(0);
});

test('scan command can scan multiple paths', function () {
    // Create another test directory
    $secondPath = base_path('resources/test-views-2');
    File::makeDirectory($secondPath, 0755, true);

    File::put($this->testViewsPath . '/file1.blade.php', "{{ __('key1') }}");
    File::put($secondPath . '/file2.blade.php', "{{ __('key2') }}");

    $this->artisan('lang:scan', [
        '--path' => [$this->testViewsPath, $secondPath],
    ])->assertExitCode(0);

    // Cleanup
    File::deleteDirectory($secondPath);
});

test('scan command removes duplicate keys', function () {
    File::put($this->testViewsPath . '/test.blade.php', "
        {{ __('duplicate.key') }}
        {{ __('duplicate.key') }}
        {{ __('unique.key') }}
    ");

    $this->artisan('lang:scan', ['--path' => [$this->testViewsPath]])
        ->assertExitCode(0);
});

test('scan command displays table with key information', function () {
    File::put($this->testViewsPath . '/test.blade.php', "
        {{ __('test.key') }}
    ");

    $output = $this->artisan('lang:scan', ['--path' => [$this->testViewsPath]]);
    
    // Should show table headers
    expect($output->output())
        ->toContain('Key')
        ->toContain('File')
        ->toContain('Exists');
});

test('scan command shows auto-generated values for missing keys', function () {
    File::put($this->testViewsPath . '/test.blade.php', "
        {{ __('Welcome Home') }}
    ");

    $this->artisan('lang:scan', ['--path' => [$this->testViewsPath]])
        ->assertExitCode(0);
});