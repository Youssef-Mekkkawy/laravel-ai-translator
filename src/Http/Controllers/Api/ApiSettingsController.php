<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ApiSettingsController extends DashboardController
{
    public function save(Request $request)
    {
        // Settings are stored in .env — we update the values there
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            return $this->error('.env file not found.');
        }

        $updates = array_filter([
            'AUTO_TRANSLATE_DRIVER'   => $request->input('driver'),
            'SUPPORTED_LANGUAGES'     => $request->input('languages'),
            'DEFAULT_LANGUAGE'        => $request->input('source_lang'),
            'AUTO_TRANSLATE_CHUNK_SIZE' => $request->input('chunk_size'),
            'AUTO_TRANSLATE_CONTEXT'  => $request->input('context'),
            'OLLAMA_MODEL'            => $request->input('ollama_model'),
            'OLLAMA_API_URL'          => $request->input('ollama_url'),
            'DEEPL_API_KEY'           => $request->input('deepl_key'),
            'ANTHROPIC_API_KEY'       => $request->input('claude_key'),
            'OPENAI_API_KEY'          => $request->input('openai_key'),
        ]);

        $env = File::get($envPath);

        foreach ($updates as $envKey => $value) {
            if (preg_match("/^{$envKey}=/m", $env)) {
                $env = preg_replace("/^{$envKey}=.*/m", "{$envKey}={$value}", $env);
            } else {
                $env .= "\n{$envKey}={$value}";
            }
        }

        File::put($envPath, $env);

        return $this->success([], 'Settings saved. Restart may be needed to apply changes.');
    }
}
