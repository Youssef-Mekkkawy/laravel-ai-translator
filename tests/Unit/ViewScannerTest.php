<?php


use LaravelAiTranslator\Services\Scanner\ViewScanner;
use Illuminate\Support\Facades\File;



beforeEach(function () {
    // Create temp directory for test files
    $this->tempDir = __DIR__ . '/../temp/views';
    
    if (!File::exists($this->tempDir)) {
        File::makeDirectory($this->tempDir, 0755, true);
    }
});

afterEach(function () {
    // Clean up temp directory
    if (File::exists(__DIR__ . '/../temp')) {
        File::deleteDirectory(__DIR__ . '/../temp');
    }
});

test('scanner finds blade files in directory', function () {
    // ── 1️⃣ Temporary folder inside resources/views
    $temp = resource_path('views/tests-scanner');
    File::ensureDirectoryExists($temp);

    // ── 2️⃣ Create test files
    File::put($temp . '/test1.blade.php', '<h1>Test</h1>');
    File::put($temp . '/test2.blade.php', '<p>Test</p>');
    File::put($temp . '/not-blade.php', '<?php echo "test"; ?>');

    // ── 3️⃣ Verify folder existence & that it contains files
    expect(File::exists($temp))->toBeTrue()
        ->and(File::isDirectory($temp))->toBeTrue()
        ->and(count(File::files($temp)))->toBeGreaterThan(0);   // top‑level only

    // ── 4️⃣ Run the scanner
    $scanner = new ViewScanner([$temp]);
    $files   = $scanner->scanForBladeFiles();

    // ── 5️⃣ Debug output (optional, shows when you run with --debug)
    dump($files);

    // ── 6️⃣ Assertions
    expect($files)->toHaveCount(2)
        ->and($files[0])->toContain('test1.blade.php')
        ->and($files[1])->toContain('test2.blade.php');

    // ── 7️⃣ Cleanup
    File::deleteDirectory($temp);
});

test('scanner extracts keys from double underscore syntax', function () {
    $content = "
        <h1>{{ __('welcome.title') }}</h1>
        <p>{{ __('welcome.description') }}</p>
        <span>{{ __('auth.login') }}</span>
    ";

    File::put($this->tempDir . '/test.blade.php', $content);

    $scanner = new ViewScanner([$this->tempDir]);
    $keys = $scanner->scanFile($this->tempDir . '/test.blade.php');

    expect($keys)->toHaveCount(3)
        ->toContain('welcome.title', 'welcome.description', 'auth.login');
});

test('scanner extracts keys from lang directive', function () {
    $content = "
        <h1>@lang('messages.hello')</h1>
        <p>@lang('messages.goodbye')</p>
    ";

    File::put($this->tempDir . '/test.blade.php', $content);

    $scanner = new ViewScanner([$this->tempDir]);
    $keys = $scanner->scanFile($this->tempDir . '/test.blade.php');

    expect($keys)->toHaveCount(2)
        ->toContain('messages.hello', 'messages.goodbye');
});

test('scanner extracts keys from trans function', function () {
    $content = "
        <h1>{{ trans('page.title') }}</h1>
        <p>{{ trans('page.subtitle') }}</p>
    ";

    File::put($this->tempDir . '/test.blade.php', $content);

    $scanner = new ViewScanner([$this->tempDir]);
    $keys = $scanner->scanFile($this->tempDir . '/test.blade.php');

    expect($keys)->toHaveCount(2)
        ->toContain('page.title', 'page.subtitle');
});

test('scanner handles mixed translation syntaxes', function () {
    $content = "
        {{ __('key1') }}
        @lang('key2')
        {{ trans('key3') }}
    ";

    File::put($this->tempDir . '/test.blade.php', $content);

    $scanner = new ViewScanner([$this->tempDir]);
    $keys = $scanner->scanFile($this->tempDir . '/test.blade.php');

    expect($keys)->toHaveCount(3)
        ->toContain('key1', 'key2', 'key3');
});

test('scanner removes duplicate keys', function () {
    $content = "
        {{ __('duplicate.key') }}
        {{ __('duplicate.key') }}
        {{ __('unique.key') }}
    ";

    File::put($this->tempDir . '/test.blade.php', $content);

    $scanner = new ViewScanner([$this->tempDir]);
    $keys = $scanner->scanFile($this->tempDir . '/test.blade.php');

    expect($keys)->toHaveCount(2)
        ->toContain('duplicate.key', 'unique.key');
});

test('scanner scans multiple files', function () {
    File::put($this->tempDir . '/file1.blade.php', "{{ __('key1') }}");
    File::put($this->tempDir . '/file2.blade.php', "{{ __('key2') }}");

    $scanner = new ViewScanner([$this->tempDir]);
    $keys = $scanner->scanAll();

    expect($keys)->toHaveCount(2)
        ->toContain('key1', 'key2');
});

test('scanner provides statistics', function () {
    File::put($this->tempDir . '/test1.blade.php', "{{ __('key1') }}");
    File::put($this->tempDir . '/test2.blade.php', "{{ __('key2') }}");

    $scanner = new ViewScanner([$this->tempDir]);
    $stats = $scanner->getStatistics();

    expect($stats)->toBeArray()
        ->and($stats)->toHaveKey('total_files')
        ->and($stats)->toHaveKey('total_keys')
        ->and($stats)->toHaveKey('files_scanned')
        ->and($stats['total_files'])->toBe(2)
        ->and($stats['total_keys'])->toBe(2);
});

test('scanner excludes files matching patterns', function () {
    File::put($this->tempDir . '/include.blade.php', "{{ __('key1') }}");
    
    // Create vendor subdirectory
    $vendorDir = $this->tempDir . '/vendor';
    File::makeDirectory($vendorDir, 0755, true);
    File::put($vendorDir . '/exclude.blade.php', "{{ __('key2') }}");

    $scanner = new ViewScanner([$this->tempDir], ['vendor']);
    $files = $scanner->scanForBladeFiles();

    expect($files)->toHaveCount(1)
        ->and($files[0])->toContain('include.blade.php')
        ->and($files[0])->not->toContain('vendor');
});