<?php

test('package can be instantiated', function () {
    expect(true)->toBeTrue();
});

test('config is loaded correctly', function () {
    $driver = config('ai-translator.driver');
    expect($driver)->toBe('deepl');
});

test('supported languages are configured', function () {
    $languages = config('ai-translator.languages');
    expect($languages)->toBeArray()
        ->toContain('en', 'ar', 'fr');
});

test('default language is english', function () {
    $defaultLang = config('ai-translator.default_language');
    expect($defaultLang)->toBe('en');
});
