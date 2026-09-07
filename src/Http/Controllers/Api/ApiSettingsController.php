<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;

class ApiSettingsController extends DashboardController
{
    public function save(Request $request)
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return $this->error('.env file not found.');
        }

        // Build the updates map — keep null values out but allow empty strings
        $updates = [];

        $map = [
            'AUTO_TRANSLATE_DRIVER' => $request->input('driver'),
            'SUPPORTED_LANGUAGES' => $request->input('languages'),
            'DEFAULT_LANGUAGE' => $request->input('source_lang'),
            'AUTO_TRANSLATE_CHUNK_SIZE' => $request->input('chunk_size'),
            'AUTO_TRANSLATE_CONTEXT' => $request->input('context'),
            'OLLAMA_MODEL' => $request->input('ollama_model'),
            'OLLAMA_API_URL' => $request->input('ollama_url'),
            'DEEPL_API_KEY' => $request->input('deepl_key'),
            'ANTHROPIC_API_KEY' => $request->input('claude_key'),
            'OPENAI_API_KEY' => $request->input('openai_key'),
            'GEMINI_API_KEY' => $request->input('gemini_key'),
        ];

        foreach ($map as $key => $value) {
            if ($value !== null) {
                $updates[$key] = $value;
            }
        }

        if (empty($updates)) {
            return $this->error('No settings to save.');
        }

        try {
            $env = File::get($envPath);

            foreach ($updates as $envKey => $value) {
                // Wrap values containing spaces in quotes
                $formatted = str_contains((string) $value, ' ')
                    ? '"'.$value.'"'
                    : (string) $value;

                // Use anchored regex to avoid partial key matches
                if (preg_match('/^'.preg_quote($envKey, '/').'=/m', $env)) {
                    $env = preg_replace(
                        '/^'.preg_quote($envKey, '/').'=.*/m',
                        $envKey.'='.$formatted,
                        $env
                    );
                } else {
                    $env .= PHP_EOL.$envKey.'='.$formatted;
                }
            }

            File::put($envPath, $env);

            // Clear config cache so changes take effect immediately
            Artisan::call('config:clear');

        } catch (\Throwable $e) {
            return $this->error('Failed to save: '.$e->getMessage());
        }

        return $this->success([], 'Settings saved successfully.');
    }
}
