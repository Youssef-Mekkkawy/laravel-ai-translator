<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers;

class SettingsController extends DashboardController
{
    public function index()
    {
        $config = config('ai-translator', []);

        return $this->view('settings', [
            'driver'       => $config['driver']           ?? 'ollama',
            'providers'    => $config['providers']        ?? [],
            'languages'    => $config['languages']        ?? [],
            'sourceLang'   => $config['default_language'] ?? 'en',
            'chunkSize'    => $config['options']['chunk_size'] ?? 20,
            'context'      => $config['options']['context']    ?? '',
            'excludeWords' => $config['options']['exclude_words'] ?? [],
        ]);
    }
}
