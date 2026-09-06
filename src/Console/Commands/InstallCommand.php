<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature   = 'ai-translator:install';
    protected $description = 'Install and configure the Laravel AI Translator package';

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=bright-green>     ___  ____   ______                      __      __            </>');
        $this->line('  <fg=bright-green>    / _ |\/  /  /_  __/______ ____  ___ ____/ /___ _/ /____  ____  </>');
        $this->line('  <fg=bright-green>   / __ |/ /     / / / __/ _ `/ _ \(_-</ __/ / _ `/ __/ _ \/ __/  </>');
        $this->line('  <fg=bright-green>  /_/ |_/_/     /_/ /_/  \_,_/_//_/___/\__/_/\_,_/\__/\___/_/     </>');
        $this->newLine();
        $this->line('  <fg=gray>  Laravel AI Translator — automatic translation for Laravel apps</>');
        $this->newLine();

        // ── Step 1: Publish config ─────────────────────────────────────────
        $this->info('📦 Step 1: Publishing config file...');

        if (File::exists(config_path('ai-translator.php'))) {
            if (!$this->confirm('  Config file already exists. Overwrite?', false)) {
                $this->line('  <fg=yellow>⏩ Skipped config publish.</>');
            } else {
                $this->callSilent('vendor:publish', [
                    '--tag'   => 'ai-translator-config',
                    '--force' => true,
                ]);
                $this->line('  <fg=green>✅ Config published to config/ai-translator.php</>');
            }
        } else {
            $this->callSilent('vendor:publish', ['--tag' => 'ai-translator-config']);
            $this->line('  <fg=green>✅ Config published to config/ai-translator.php</>');
        }

        // ── Step 2: Detect driver preference ──────────────────────────────
        $this->newLine();
        $this->info('🤖 Step 2: Choose your AI translation provider');
        $this->newLine();

        $driver = $this->choice(
            '  Which provider do you want to use?',
            [
                'ollama' => 'Ollama (local, free — recommended for development)',
                'deepl'  => 'DeepL (cloud, free tier: 500k chars/month)',
                'claude' => 'Claude by Anthropic (cloud, paid)',
                'openai' => 'ChatGPT by OpenAI (cloud, paid)',
                'gemini' => 'Gemini by Google (cloud, paid)',
            ],
            'ollama'
        );

        // ── Step 3: Languages ──────────────────────────────────────────────
        $this->newLine();
        $this->info('🌍 Step 3: Configure languages');
        $this->newLine();

        $this->line('  Common codes: ar (Arabic), fr (French), es (Spanish), de (German),');
        $this->line('                zh (Chinese), ja (Japanese), tr (Turkish), ru (Russian)');
        $this->newLine();

        $langs = $this->ask(
            '  Enter target languages (comma-separated, e.g: ar,fr,es)',
            'ar,fr,es'
        );

        $sourceLang = $this->ask('  Source language (your app\'s language)', 'en');

        // ── Step 4: Write .env ─────────────────────────────────────────────
        $this->newLine();
        $this->info('⚙️  Step 4: Updating .env...');

        $envPath  = base_path('.env');
        $allLangs = $sourceLang . ',' . trim($langs, ',');

        $envKeys = [
            'AUTO_TRANSLATE_DRIVER'   => $driver,
            'SUPPORTED_LANGUAGES'     => $allLangs,
            'DEFAULT_LANGUAGE'        => $sourceLang,
            'AUTO_TRANSLATE_BACKUP'   => 'true',
            'AUTO_TRANSLATE_CHUNK_SIZE' => '20',
        ];

        // Provider-specific keys
        match ($driver) {
            'ollama' => $envKeys += [
                'OLLAMA_MODEL'   => 'llama3.2',
                'OLLAMA_API_URL' => 'http://localhost:11434',
            ],
            'deepl'  => $envKeys += [
                'DEEPL_API_KEY' => '',
                'DEEPL_PLAN'    => 'free',
            ],
            'claude' => $envKeys += [
                'ANTHROPIC_API_KEY' => '',
                'ANTHROPIC_MODEL'   => 'claude-sonnet-4-5',
            ],
            'openai' => $envKeys += [
                'OPENAI_API_KEY' => '',
                'OPENAI_MODEL'   => 'gpt-4o-mini',
            ],
            'gemini' => $envKeys += [
                'GEMINI_API_KEY' => '',
                'GEMINI_MODEL'   => 'gemini-1.5-flash',
            ],
            default => [],
        };

        if (File::exists($envPath)) {
            $env = File::get($envPath);

            foreach ($envKeys as $key => $value) {
                if (preg_match('/^' . preg_quote($key, '/') . '=/m', $env)) {
                    $env = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', "{$key}={$value}", $env);
                } else {
                    $env .= PHP_EOL . "{$key}={$value}";
                }
            }

            File::put($envPath, $env);
            $this->line('  <fg=green>✅ .env updated successfully</>');
        } else {
            $this->line('  <fg=yellow>⚠️  .env file not found — please add these keys manually:</>');
            foreach ($envKeys as $key => $value) {
                $this->line("  <fg=gray>  {$key}={$value}</>");
            }
        }

        // ── Step 5: Check Ollama if selected ──────────────────────────────
        if ($driver === 'ollama') {
            $this->newLine();
            $this->info('🔍 Step 5: Checking Ollama...');

            try {
                $response = \Illuminate\Support\Facades\Http::timeout(3)
                    ->get('http://localhost:11434/api/tags');

                if ($response->successful()) {
                    $models = collect($response->json('models', []))->pluck('name');
                    $this->line('  <fg=green>✅ Ollama is running</>');

                    if ($models->contains(fn ($m) => str_contains($m, 'llama3.2'))) {
                        $this->line('  <fg=green>✅ llama3.2 is available</>');
                    } else {
                        $this->line('  <fg=yellow>⚠️  llama3.2 not found. Run:</>');
                        $this->line('  <fg=cyan>     ollama pull llama3.2</>');
                    }
                } else {
                    $this->showOllamaHelp();
                }
            } catch (\Throwable $e) {
                $this->showOllamaHelp();
            }
        } else {
            $apiKeyVar = match($driver) {
                'deepl'  => 'DEEPL_API_KEY',
                'claude' => 'ANTHROPIC_API_KEY',
                'openai' => 'OPENAI_API_KEY',
                'gemini' => 'GEMINI_API_KEY',
                default  => null,
            };

            if ($apiKeyVar) {
                $this->newLine();
                $this->line("  <fg=yellow>⚠️  Don't forget to add your API key to .env:</>");
                $this->line("  <fg=cyan>     {$apiKeyVar}=your-api-key-here</>");
            }
        }

        // ── Step 6: Clear config cache ─────────────────────────────────────
        $this->callSilent('config:clear');

        // ── Done ───────────────────────────────────────────────────────────
        $this->newLine();
        $this->line('  ┌─────────────────────────────────────────────────────┐');
        $this->line('  │  <fg=bright-green>🎉  Laravel AI Translator is ready!</>              │');
        $this->line('  └─────────────────────────────────────────────────────┘');
        $this->newLine();
        $this->line('  <fg=gray>Next steps:</> ');
        $this->line('  <fg=cyan>  1. php artisan lang:scan</>         <fg=gray>— find translation keys</>');
        $this->line('  <fg=cyan>  2. php artisan lang:translate</>    <fg=gray>— translate your app</>');
        $this->line('  <fg=cyan>  3. Open /ai-translator</>           <fg=gray>— dashboard</>');
        $this->newLine();
        $this->line('  <fg=gray>  ⭐ Star the project:</> <fg=blue>https://github.com/Youssef-Mekkkawy/laravel-ai-translator</>');
        $this->line('  <fg=gray>  ☕ Buy me a coffee:  </> <fg=blue>https://buymeacoffee.com/youssef.mekkawy</>');
        $this->newLine();

        return self::SUCCESS;
    }

    private function showOllamaHelp(): void
    {
        $this->line('  <fg=red>❌ Ollama is not running.</>');
        $this->newLine();
        $this->line('  Install Ollama from <fg=blue>https://ollama.com</> then run:');
        $this->line('  <fg=cyan>     ollama pull llama3.2</>');
        $this->line('  <fg=cyan>     ollama serve</>');
    }
}
