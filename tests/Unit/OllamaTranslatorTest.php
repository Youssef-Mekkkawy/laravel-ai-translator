<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\OllamaTranslator;

// ── Helpers ────────────────────────────────────────────────────────────────

/**
 * Return a fake Ollama chat/completions response.
 */
function ollamaResponse(string $content): array
{
    return [
        'choices' => [
            ['message' => ['content' => $content]],
        ],
    ];
}

/**
 * Return a fake Ollama /api/tags response (server up).
 */
function ollamaTagsResponse(): array
{
    return ['models' => [['name' => 'llama3:latest']]];
}

/**
 * Build a default OllamaTranslator with test config.
 */
function makeOllama(array $overrides = []): OllamaTranslator
{
    return new OllamaTranslator(array_merge([
        'model' => 'llama3',
        'api_url' => 'http://localhost:11434',
        'timeout' => 5,
    ], $overrides));
}

// ── Tests ──────────────────────────────────────────────────────────────────

describe('OllamaTranslator', function () {

    // ── Instantiation ──────────────────────────────────────────────────────

    it('can be instantiated', function () {
        $translator = makeOllama();
        expect($translator)->toBeInstanceOf(OllamaTranslator::class);
    });

    it('reports its name as ollama', function () {
        expect(makeOllama()->getName())->toBe('ollama');
    });

    // ── Availability ───────────────────────────────────────────────────────

    it('is available when Ollama server is running', function () {
        Http::fake([
            '*/api/tags' => Http::response(ollamaTagsResponse(), 200),
        ]);

        expect(makeOllama()->isAvailable())->toBeTrue();
    });

    it('is not available when Ollama server is down', function () {
        Http::fake([
            '*/api/tags' => Http::response('', 500),
        ]);

        expect(makeOllama()->isAvailable())->toBeFalse();
    });

    it('is not available when connection is refused', function () {
        Http::fake([
            '*/api/tags' => fn () => throw new ConnectionException('refused'),
        ]);

        expect(makeOllama()->isAvailable())->toBeFalse();
    });

    // ── Single translation ─────────────────────────────────────────────────

    it('translates a single text', function () {
        Http::fake([
            '*/v1/chat/completions' => Http::response(
                ollamaResponse('مرحبا بالعالم'), 200
            ),
        ]);

        $result = makeOllama()->translate('Hello world', 'ar');

        expect($result)->toBe('مرحبا بالعالم');
    });

    it('returns empty string unchanged', function () {
        Http::fake(); // no HTTP calls should be made

        $result = makeOllama()->translate('   ', 'ar');

        Http::assertNothingSent();
        expect(trim($result))->toBe('');
    });

    // ── Placeholder preservation ───────────────────────────────────────────

    it('preserves colon-style placeholders', function () {
        Http::fake([
            '*/v1/chat/completions' => Http::response(
                // Simulate the model returning markers intact
                ollamaResponse('مرحبا ___PLACEHOLDER_0___ لديك ___PLACEHOLDER_1___ رسائل'),
                200
            ),
        ]);

        $result = makeOllama()->translate('Hello :name you have :count messages', 'ar');

        expect($result)->toContain(':name')
            ->and($result)->toContain(':count');
    });

    it('preserves numbered placeholders', function () {
        Http::fake([
            '*/v1/chat/completions' => Http::response(
                ollamaResponse('الصفحة ___PLACEHOLDER_0___ من ___PLACEHOLDER_1___'),
                200
            ),
        ]);

        $result = makeOllama()->translate('Page {0} of {1}', 'ar');

        expect($result)->toContain('{0}')
            ->and($result)->toContain('{1}');
    });

    it('preserves HTML tags', function () {
        Http::fake([
            '*/v1/chat/completions' => Http::response(
                ollamaResponse('مرحبا ___TAG_0___بالعالم___TAG_1___'),
                200
            ),
        ]);

        $result = makeOllama()->translate('Hello <strong>world</strong>', 'ar');

        expect($result)->toContain('<strong>')
            ->and($result)->toContain('</strong>');
    });

    // ── Batch translation ──────────────────────────────────────────────────

    it('translates a batch in a single request', function () {
        $batchResponse = json_encode([
            0 => 'مرحبا',
            1 => 'وداعا',
            2 => 'شكرا',
        ]);

        Http::fake([
            '*/v1/chat/completions' => Http::response(
                ollamaResponse($batchResponse), 200
            ),
        ]);

        $results = makeOllama()->translateBatch(
            ['Hello', 'Goodbye', 'Thank you'],
            'ar'
        );

        expect($results)->toHaveCount(3)
            ->and($results[0])->toBe('مرحبا')
            ->and($results[1])->toBe('وداعا')
            ->and($results[2])->toBe('شكرا');

        // Only ONE HTTP call should be made (batch)
        Http::assertSentCount(1);
    });

    it('falls back to individual calls when batch JSON is malformed', function () {
        Http::fake([
            '*/v1/chat/completions' => Http::sequence()
                ->push(ollamaResponse('not valid json at all'), 200) // batch fails
                ->push(ollamaResponse('مرحبا'), 200)                // individual: Hello
                ->push(ollamaResponse('وداعا'), 200),               // individual: Goodbye
        ]);

        $results = makeOllama()->translateBatch(['Hello', 'Goodbye'], 'ar');

        expect($results)->toHaveCount(2)
            ->and($results[0])->toBe('مرحبا')
            ->and($results[1])->toBe('وداعا');
    });

    it('handles markdown-wrapped JSON from the model', function () {
        $batchResponse = "```json\n".json_encode([0 => 'مرحبا', 1 => 'وداعا'])."\n```";

        Http::fake([
            '*/v1/chat/completions' => Http::response(
                ollamaResponse($batchResponse), 200
            ),
        ]);

        $results = makeOllama()->translateBatch(['Hello', 'Goodbye'], 'ar');

        expect($results[0])->toBe('مرحبا')
            ->and($results[1])->toBe('وداعا');
    });

    it('returns empty strings in batch without making API calls for them', function () {
        Http::fake([
            '*/v1/chat/completions' => Http::response(
                ollamaResponse(json_encode([1 => 'شكرا'])), 200
            ),
        ]);

        $results = makeOllama()->translateBatch(['', 'Thank you', ''], 'ar');

        expect($results)->toHaveCount(3)
            ->and($results[0])->toBe('')
            ->and($results[1])->toBe('شكرا')
            ->and($results[2])->toBe('');
    });

    // ── Cost estimation ────────────────────────────────────────────────────

    it('estimates zero cost (local model)', function () {
        $cost = makeOllama()->estimateCost(
            ['Hello', 'World'],
            ['ar', 'fr']
        );

        expect($cost['cost'])->toBe(0.0)
            ->and($cost['estimated_cost'])->toBe(0.0)
            ->and($cost['characters'])->toBe(20); // (5+5) chars * 2 langs = 20
    });

    // ── Config ─────────────────────────────────────────────────────────────

    it('uses the configured model name', function () {
        Http::fake([
            '*/v1/chat/completions' => Http::response(
                ollamaResponse('مرحبا'), 200
            ),
        ]);

        makeOllama(['model' => 'mistral'])->translate('Hello', 'ar');

        Http::assertSent(fn ($request) => $request->data()['model'] === 'mistral'
        );
    });

    it('uses the configured api_url', function () {
        Http::fake([
            'http://my-server:11434/*' => Http::response(
                ollamaResponse('مرحبا'), 200
            ),
        ]);

        makeOllama(['api_url' => 'http://my-server:11434'])->translate('Hello', 'ar');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'my-server:11434')
        );
    });

    // ── Error handling ─────────────────────────────────────────────────────

    it('throws when Ollama returns a server error', function () {
        Http::fake([
            '*/v1/chat/completions' => Http::response('Internal Server Error', 500),
        ]);

        expect(fn () => makeOllama()->translate('Hello', 'ar'))
            ->toThrow(RuntimeException::class);
    });

});
