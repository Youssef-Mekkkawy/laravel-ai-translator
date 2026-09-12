<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers;

use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;

class SettingsController extends DashboardController
{
    public function index()
    {
        $config  = config('ai-translator', []);
        $runtime = new RuntimeConfig();

        // Read from RuntimeConfig first (reflects dashboard saves),
        // fall back to static config for fresh installs.
        $driver      = $runtime->get('driver',       $config['driver']                               ?? 'ollama');
        $chunkSize   = $runtime->get('chunk_size',   $config['options']['chunk_size']                ?? 50);
        $context     = $runtime->get('context',      $config['options']['context']                   ?? '');
        $ollamaModel = $runtime->get('ollama_model', $config['providers']['ollama']['model']         ?? 'llama3');
        $ollamaUrl   = $runtime->get('ollama_url',   $config['providers']['ollama']['api_url']       ?? 'http://localhost:11434');
        $sourceLang  = $runtime->get('source_lang',  $config['default_language']                     ?? 'en');

        return $this->view('settings', [
            'driver'      => $driver,
            'providers'   => $config['providers'] ?? [],
            'languages'   => $config['languages'] ?? [],
            'sourceLang'  => $sourceLang,
            'chunkSize'   => $chunkSize,
            'context'     => $context,
            'ollamaModel' => $ollamaModel,
            'ollamaUrl'   => $ollamaUrl,
        ]);
    }
}
