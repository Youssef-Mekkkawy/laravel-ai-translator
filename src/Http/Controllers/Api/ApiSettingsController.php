<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;

class ApiSettingsController extends DashboardController
{
    public function save(Request $request)
    {
        $runtime = new RuntimeConfig;

        try {
            // All non-sensitive settings saved to RuntimeConfig — no .env writes, no server restarts
            $updates = array_filter([
                'driver'       => $request->input('driver'),
                'source_lang'  => $request->input('source_lang'),
                'chunk_size'   => $request->input('chunk_size'),
                'context'      => $request->input('context'),
                'ollama_model' => $request->input('ollama_model'),
                'ollama_url'   => $request->input('ollama_url'),
                // FIX: persist backup settings from the backups page
                'backup_keep'  => $request->input('backup_keep'),
                'backup_auto'  => $request->input('backup_auto') !== null
                    ? (bool) $request->input('backup_auto')
                    : null,
            ], fn ($v) => $v !== null);

            if (!empty($updates)) {
                $runtime->merge($updates);
            }

            // API keys still go to .env (sensitive — should not be in lang/ folder)
            $apiKeyMap = [
                'DEEPL_API_KEY'     => $request->input('deepl_key'),
                'ANTHROPIC_API_KEY' => $request->input('claude_key'),
                'OPENAI_API_KEY'    => $request->input('openai_key'),
                'GEMINI_API_KEY'    => $request->input('gemini_key'),
            ];

            $envUpdates = array_filter($apiKeyMap, fn ($v) => !empty($v));

            if (!empty($envUpdates)) {
                $envPath = base_path('.env');
                if (File::exists($envPath)) {
                    $env = File::get($envPath);
                    foreach ($envUpdates as $key => $value) {
                        if (preg_match('/^' . preg_quote($key, '/') . '=/m', $env)) {
                            $env = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', "{$key}={$value}", $env);
                        } else {
                            $env .= PHP_EOL . "{$key}={$value}";
                        }
                    }
                    File::put($envPath, $env);
                }
            }

        } catch (\Throwable $e) {
            return $this->error('Failed to save: ' . $e->getMessage());
        }

        return $this->success([], 'Settings saved successfully.');
    }
}
