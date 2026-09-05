<?php

use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\DeepLTranslator;

describe('DeepLTranslator', function () {

    beforeEach(function () {
        // Mock config (you'll need real API key for integration tests)
        $this->config = [
            'api_key' => 'test-api-key',
            'batch_size' => 50,
            'timeout' => 30,
        ];
    });

    it('can be instantiated', function () {
        $translator = new DeepLTranslator($this->config);

        expect($translator)->toBeInstanceOf(DeepLTranslator::class);
        expect($translator->getName())->toBe('DeepL');
    });

    it('checks availability based on API key', function () {
        $translator = new DeepLTranslator(['api_key' => 'test-key']);
        expect($translator->isAvailable())->toBeTrue();

        $translator = new DeepLTranslator([]);
        expect($translator->isAvailable())->toBeFalse();
    });

    it('normalizes language codes correctly', function () {
        $translator = new DeepLTranslator($this->config);

        // Use reflection to test protected method
        $reflection = new ReflectionClass($translator);
        $method = $reflection->getMethod('normalizeLanguageCode');
        $method->setAccessible(true);

        expect($method->invoke($translator, 'en'))->toBe('EN-US');
        expect($method->invoke($translator, 'pt'))->toBe('PT-BR');
        expect($method->invoke($translator, 'ar'))->toBe('AR');
        expect($method->invoke($translator, 'fr'))->toBe('FR');
    });

    it('preserves Laravel placeholders', function () {
        $translator = new DeepLTranslator($this->config);
        $reflection = new ReflectionClass($translator);
        $method = $reflection->getMethod('prepareText');
        $method->setAccessible(true);

        $text = 'Welcome back, :name! You have :count messages.';
        [$prepared, $placeholders, $tags] = $method->invoke($translator, $text);

        expect($prepared)->toContain('___PLACEHOLDER_');
        expect($placeholders)->not->toBeEmpty();
    });

    it('preserves numbered placeholders', function () {
        $translator = new DeepLTranslator($this->config);
        $reflection = new ReflectionClass($translator);
        $method = $reflection->getMethod('prepareText');
        $method->setAccessible(true);

        $text = 'Hello {0}, you have {1} items in your cart.';
        [$prepared, $placeholders, $tags] = $method->invoke($translator, $text);

        expect($prepared)->toContain('___PLACEHOLDER_');
        expect($placeholders)->not->toBeEmpty();
    });

    it('preserves HTML tags', function () {
        $translator = new DeepLTranslator($this->config);
        $reflection = new ReflectionClass($translator);
        $method = $reflection->getMethod('prepareText');
        $method->setAccessible(true);

        $text = 'Click <a href="/link">here</a> for more <strong>info</strong>.';
        [$prepared, $placeholders, $tags] = $method->invoke($translator, $text);

        expect($prepared)->toContain('___TAG_');
        expect($tags)->not->toBeEmpty();
    });

    it('preserves both placeholders and HTML tags', function () {
        $translator = new DeepLTranslator($this->config);
        $reflection = new ReflectionClass($translator);
        $method = $reflection->getMethod('prepareText');
        $method->setAccessible(true);

        $text = 'Welcome <strong>:name</strong>, you have <a href="#">:count</a> messages.';
        [$prepared, $placeholders, $tags] = $method->invoke($translator, $text);

        expect($prepared)->toContain('___PLACEHOLDER_');
        expect($prepared)->toContain('___TAG_');
        expect($placeholders)->not->toBeEmpty();
        expect($tags)->not->toBeEmpty();
    });

    it('restores text correctly after preparation', function () {
        $translator = new DeepLTranslator($this->config);
        $reflection = new ReflectionClass($translator);
        $prepareMethod = $reflection->getMethod('prepareText');
        $prepareMethod->setAccessible(true);
        $restoreMethod = $reflection->getMethod('restoreText');
        $restoreMethod->setAccessible(true);

        $original = 'Hello <strong>:name</strong>, you have :count new messages.';
        [$prepared, $placeholders, $tags] = $prepareMethod->invoke($translator, $original);
        $restored = $restoreMethod->invoke($translator, $prepared, $placeholders, $tags);

        expect($restored)->toBe($original);
    });

    it('estimates cost correctly', function () {
        $translator = new DeepLTranslator($this->config);

        $texts = [
            'Hello world',
            'How are you?',
            'This is a test',
        ];

        $targetLangs = ['ar', 'fr', 'es'];

        $estimate = $translator->estimateCost($texts, $targetLangs);

        expect($estimate)->toHaveKey('characters');
        expect($estimate)->toHaveKey('cost');
        expect($estimate)->toHaveKey('currency');
        expect($estimate['currency'])->toBe('USD');
        expect($estimate['characters'])->toBeGreaterThan(0);
    });

    it('throws exception when API key is missing', function () {
        $translator = new DeepLTranslator([]);

        expect(fn () => $translator->translate('test', 'ar'))
            ->toThrow(RuntimeException::class, 'DeepL API key not configured');
    });

    it('handles empty batch translation', function () {
        $translator = new DeepLTranslator($this->config);

        $result = $translator->translateBatch([], 'ar');

        expect($result)->toBe([]);
    });

    // Note: The following tests require a real DeepL API key
    // Mark them as @group integration to run separately

    it('can translate text with real API', function () {
        // Skip if no API key
        if (empty(env('DEEPL_API_KEY'))) {
            $this->markTestSkipped('DEEPL_API_KEY not set');
        }

        $translator = new DeepLTranslator([
            'api_key' => env('DEEPL_API_KEY'),
        ]);

        $result = $translator->translate('Hello world', 'ar');

        expect($result)->not->toBeEmpty();
        expect($result)->not->toBe('Hello world');
    })->group('integration', 'requires-api');

    it('can translate batch with real API', function () {
        // Skip if no API key
        if (empty(env('DEEPL_API_KEY'))) {
            $this->markTestSkipped('DEEPL_API_KEY not set');
        }

        $translator = new DeepLTranslator([
            'api_key' => env('DEEPL_API_KEY'),
        ]);

        $texts = ['Hello', 'World', 'How are you?'];
        $results = $translator->translateBatch($texts, 'ar');

        expect($results)->toHaveCount(3);
        expect($results[0])->not->toBe('Hello');
    })->group('integration', 'requires-api');

    it('preserves placeholders in real translation', function () {
        // Skip if no API key
        if (empty(env('DEEPL_API_KEY'))) {
            $this->markTestSkipped('DEEPL_API_KEY not set');
        }

        $translator = new DeepLTranslator([
            'api_key' => env('DEEPL_API_KEY'),
        ]);

        $result = $translator->translate('Welcome back, :name!', 'ar');

        expect($result)->toContain(':name');
    })->group('integration', 'requires-api');
});
