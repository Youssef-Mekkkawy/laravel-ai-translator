<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Http;

class OllamaController extends DashboardController
{
    /**
     * Return installed Ollama models from the local Ollama instance.
     */
    public function models()
    {
        $url = config('ai-translator.providers.ollama.api_url', 'http://localhost:11434');
        $url = rtrim(str_replace('/v1', '', $url), '/');

        try {
            $response = Http::timeout(4)->get($url . '/api/tags');

            if (!$response->successful()) {
                return $this->error('Ollama not reachable.', 503);
            }

            $models = collect($response->json('models', []))
                ->map(fn ($m) => $m['name'])
                ->filter()
                ->values()
                ->toArray();

            return $this->success(['models' => $models]);

        } catch (\Throwable $e) {
            return $this->error('Ollama not running: ' . $e->getMessage(), 503);
        }
    }
}
