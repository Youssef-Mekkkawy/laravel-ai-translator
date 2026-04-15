<?php

use LaravelAiTranslator\Services\Scanner\KeyExtractor;

test('key extractor parses dot notation keys', function () {
    $extractor = new KeyExtractor();
    $result = $extractor->parseKey('auth.login');

    expect($result)->toBeArray()
        ->and($result['file'])->toBe('auth')
        ->and($result['key'])->toBe('login')
        ->and($result['full_key'])->toBe('auth.login');
});

test('key extractor parses nested keys', function () {
    $extractor = new KeyExtractor();
    $result = $extractor->parseKey('messages.success.saved');

    expect($result)->toBeArray()
        ->and($result['file'])->toBe('messages')
        ->and($result['key'])->toBe('success.saved')
        ->and($result['is_nested'])->toBeTrue();
});

test('key extractor parses plain text keys', function () {
    $extractor = new KeyExtractor();
    $result = $extractor->parseKey('Welcome to our platform');

    expect($result)->toBeArray()
        ->and($result['file'])->toBe('auto')
        ->and($result['key'])->toBe('welcome_to_our_platform')
        ->and($result['original_text'])->toBe('Welcome to our platform');
});

test('key extractor converts text to snake_case', function () {
    $extractor = new KeyExtractor();
    
    $result1 = $extractor->parseKey('User Settings');
    expect($result1['key'])->toBe('user_settings');

    $result2 = $extractor->parseKey('Login Now!');
    expect($result2['key'])->toBe('login_now');

    $result3 = $extractor->parseKey('404 Not Found');
    expect($result3['key'])->toBe('404_not_found');
});

test('key extractor generates default values', function () {
    $extractor = new KeyExtractor();

    // Plain text should return original
    $value1 = $extractor->generateDefaultValue('Welcome Home');
    expect($value1)->toBe('Welcome Home');

    // Snake case should convert to headline
    $value2 = $extractor->generateDefaultValue('user.settings');
    expect($value2)->toContain('Settings');
});

test('key extractor organizes keys by file', function () {
    $extractor = new KeyExtractor();
    
    $keys = [
        'auth.login',
        'auth.register',
        'messages.success',
        'Welcome',
    ];

    $organized = $extractor->organizeKeysByFile($keys);

    expect($organized)->toBeArray()
        ->and($organized)->toHaveKey('auth')
        ->and($organized)->toHaveKey('messages')
        ->and($organized)->toHaveKey('auto')
        ->and($organized['auth'])->toHaveCount(2)
        ->and($organized['messages'])->toHaveCount(1)
        ->and($organized['auto'])->toHaveCount(1);
});

test('key extractor handles special characters in plain text', function () {
    $extractor = new KeyExtractor();
    
    $result = $extractor->parseKey("Hello, World!");
    expect($result['key'])->toBe('hello_world');

    $result2 = $extractor->parseKey("User's Settings");
    expect($result2['key'])->toBe('users_settings');
});

test('key extractor removes multiple underscores', function () {
    $extractor = new KeyExtractor();
    
    $result = $extractor->parseKey('Multiple   Spaces   Here');
    expect($result['key'])->toBe('multiple_spaces_here');
});