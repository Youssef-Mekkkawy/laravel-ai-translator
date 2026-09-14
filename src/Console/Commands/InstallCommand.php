<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\StackDetector;

class InstallCommand extends Command
{
    protected $signature = 'ai-translator:install';

    protected $description = 'Install and configure the Laravel AI Translator package';

    public function handle(): int
    {
        $this->newLine();
        $this->line('  <fg=bright-green>     ___  ____   ______                      __      __            </>');
        $this->line('  <fg=bright-green>    / _ |\\/  /  /_  __/______ ____  ___ ____/ /___ _/ /____  ____  </>');
        $this->line('  <fg=bright-green>   / __ |/ /     / / / __/ _ `/ _ \\(_-</ __/ / _ `/ __/ _ \\/ __/  </>');
        $this->line('  <fg=bright-green>  /_/ |_/_/     /_/ /_/  \\_,_/_//_/___/\\__/_/\\_,_/\\__/\\___/_/     </>');
        $this->newLine();
        $this->line('  <fg=gray>  Laravel AI Translator — automatic translation for Laravel apps</>');
        $this->newLine();

        // ── Step 1: Publish config ─────────────────────────────────────────
        $this->info('📦 Step 1: Publishing config file...');

        if (File::exists(config_path('ai-translator.php'))) {
            if (! $this->confirm('  Config file already exists. Overwrite?', false)) {
                $this->line('  <fg=yellow>⏩ Skipped config publish.</>');
            } else {
                $this->callSilent('vendor:publish', [
                    '--tag' => 'ai-translator-config',
                    '--force' => true,
                ]);
                $this->line('  <fg=green>✅ Config published to config/ai-translator.php</>');
            }
        } else {
            $this->callSilent('vendor:publish', ['--tag' => 'ai-translator-config']);
            $this->line('  <fg=green>✅ Config published to config/ai-translator.php</>');
        }

        // ── Step 1b: Detect stack ──────────────────────────────────────────
        $this->newLine();
        $this->info('🔍 Detecting your Laravel stack...');

        $detector = new StackDetector;
        $detected = $detector->detect();

        $this->line('  <fg=green>✅ Detected: '.$detector->describe($detected).'</>');
        if ($detected['starter_kit'] !== 'none') {
            $this->line('  <fg=gray>  Starter kit: '.ucfirst($detected['starter_kit']).'</>');
        }
        $this->line('  <fg=gray>  Output format: '.$detected['output_format'].'</>');

        // ── Step 2: Detect driver preference ──────────────────────────────
        $this->newLine();
        $this->info('🤖 Step 2: Choose your AI translation provider');
        $this->newLine();

        $driver = $this->choice(
            '  Which provider do you want to use?',
            [
                'ollama' => 'Ollama (local, free — recommended for development)',
                'deepl' => 'DeepL (cloud, free tier: 500k chars/month)',
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

        // FIX: sanitize source language
        $sourceLang = trim($this->ask('  Source language (your app\'s language)', 'en'));

        // FIX: sanitize target languages — remove spaces, empty entries,
        // duplicates, and the source language if accidentally included.
        // Previously raw input was used directly, causing "ar " or the
        // un-cleared default "ar,fr,es" to be written even when user typed "ar".
        $targetLangs = array_values(array_unique(array_filter(
            array_map('trim', explode(',', $langs)),
            fn ($l) => ! empty($l) && $l !== $sourceLang
        )));

        $allLangs = $sourceLang.','.implode(',', $targetLangs);

        // ── Step 4: Write .env ─────────────────────────────────────────────
        $this->newLine();
        $this->info('⚙️  Step 4: Updating .env...');

        $envPath = base_path('.env');

        $envKeys = [
            'AUTO_TRANSLATE_DRIVER' => $driver,
            'SUPPORTED_LANGUAGES' => $allLangs,
            'DEFAULT_LANGUAGE' => $sourceLang,
            'AUTO_TRANSLATE_BACKUP' => 'true',
            'AUTO_TRANSLATE_CHUNK_SIZE' => '20',
        ];

        // Provider-specific keys
        match ($driver) {
            'ollama' => $envKeys += [
                'OLLAMA_MODEL' => 'llama3.2',
                'OLLAMA_API_URL' => 'http://localhost:11434',
            ],
            'deepl' => $envKeys += [
                'DEEPL_API_KEY' => '',
                'DEEPL_PLAN' => 'free',
            ],
            'claude' => $envKeys += [
                'ANTHROPIC_API_KEY' => '',
                'ANTHROPIC_MODEL' => 'claude-sonnet-4-5',
            ],
            'openai' => $envKeys += [
                'OPENAI_API_KEY' => '',
                'OPENAI_MODEL' => 'gpt-4o-mini',
            ],
            'gemini' => $envKeys += [
                'GEMINI_API_KEY' => '',
                'GEMINI_MODEL' => 'gemini-1.5-flash',
            ],
            default => [],
        };

        if (File::exists($envPath)) {
            $env = File::get($envPath);

            foreach ($envKeys as $key => $value) {
                if (preg_match('/^'.preg_quote($key, '/').'=/m', $env)) {
                    $env = preg_replace(
                        '/^'.preg_quote($key, '/').'=.*/m',
                        "{$key}={$value}",
                        $env
                    );
                } else {
                    $env .= PHP_EOL."{$key}={$value}";
                }
            }

            File::put($envPath, $env);
            $this->line('  <fg=green>✅ .env updated successfully</>');

            // FIX: write selected languages to RuntimeConfig so getTargetLanguages()
            // reads the correct list immediately — without RuntimeConfig the translate
            // command falls back to the static config default (en,ar,fr,es) regardless
            // of what the user entered here.
            $runtime = new RuntimeConfig;
            $runtime->merge([
                'stack' => $detected['stack'],
                'output_format' => $detected['output_format'],
                'scan_paths' => $detected['scan_paths'],
                'scan_extensions' => $detected['scan_extensions'],
                'detected_stack_at' => now()->toISOString(),
                'supported_languages' => array_filter(
                    explode(',', $allLangs),
                    fn ($l) => ! empty(trim($l))
                ),
            ]);

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
                $response = Http::timeout(3)
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
            $apiKeyVar = match ($driver) {
                'deepl' => 'DEEPL_API_KEY',
                'claude' => 'ANTHROPIC_API_KEY',
                'openai' => 'OPENAI_API_KEY',
                'gemini' => 'GEMINI_API_KEY',
                default => null,
            };

            if ($apiKeyVar) {
                $this->newLine();
                $this->line("  <fg=yellow>⚠️  Don't forget to add your API key to .env:</>");
                $this->line("  <fg=cyan>     {$apiKeyVar}=your-api-key-here</>");
            }
        }

        // ── Step 6: Clear config cache ─────────────────────────────────────
        $this->callSilent('config:clear');

        // ── Step 7: Scan views and generate source language files ──────────
        $this->newLine();
        $this->info('🔍 Step 7: Scanning views and generating source language files...');

        try {
            $scanPaths = config('ai-translator.scan_paths', [resource_path('views')]);
            $scanner = new ViewScanner(
                array_filter($scanPaths, fn ($p) => is_dir($p))
            );
            $keys = $scanner->scanAll();

            if (empty($keys)) {
                $this->line('  <fg=yellow>⚠️  No translation keys found in views. Add __() calls first.</>');
            } else {
                $phpKeys = [];
                $jsonKeys = [];

                foreach ($keys as $key) {
                    if (! str_contains($key, ' ') && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)+$/', $key)) {
                        $parts = explode('.', $key, 2);
                        $phpKeys[$parts[0]][$parts[1]] = $parts[1];
                    } else {
                        $jsonKeys[$key] = $key;
                    }
                }

                $sourceLangDir = lang_path($sourceLang);

                if (! empty($phpKeys)) {
                    if (! File::exists($sourceLangDir)) {
                        File::makeDirectory($sourceLangDir, 0755, true);
                    }

                    foreach ($phpKeys as $group => $groupKeys) {
                        $filePath = $sourceLangDir.DIRECTORY_SEPARATOR.$group.'.php';
                        $existing = [];
                        if (File::exists($filePath)) {
                            $existing = @include $filePath;
                            if (! is_array($existing)) {
                                $existing = [];
                            }
                        }
                        $merged = array_merge($groupKeys, $existing);
                        ksort($merged);
                        File::put(
                            $filePath,
                            "<?php\n\nreturn ".$this->arrayToPhp($merged).";\n"
                        );
                    }
                    $this->line("  <fg=green>✅ Source PHP files created in lang/{$sourceLang}/</>");
                }

                if (! empty($jsonKeys)) {
                    $jsonPath = lang_path($sourceLang.'.json');
                    $existing = [];
                    if (File::exists($jsonPath)) {
                        $existing = @json_decode(File::get($jsonPath), true) ?? [];
                    }
                    $merged = array_merge($jsonKeys, $existing);
                    ksort($merged);
                    File::put(
                        $jsonPath,
                        json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL
                    );
                    $this->line("  <fg=green>✅ Source JSON file created: lang/{$sourceLang}.json</>");
                }

                $this->line('  <fg=gray>  Found '.count($keys).' translation keys</>');
            }
        } catch (\Throwable $e) {
            $this->line("  <fg=yellow>⚠️  Could not scan views: {$e->getMessage()}</>");
        }

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
        $this->line('  <fg=gray>  ☕ Buy me a coffee:  </> <fg=blue>https://github.com/sponsors/Youssef-Mekkkawy</>');
        $this->newLine();

        return self::SUCCESS;
    }

    private function arrayToPhp(array $array, int $depth = 0): string
    {
        if (empty($array)) {
            return '[]';
        }
        $indent = str_repeat('    ', $depth);
        $nextIndent = str_repeat('    ', $depth + 1);
        $lines = ['['];
        foreach ($array as $key => $value) {
            $k = "'".addslashes((string) $key)."'";
            if (is_array($value)) {
                $lines[] = "{$nextIndent}{$k} => ".$this->arrayToPhp($value, $depth + 1).',';
            } else {
                $lines[] = "{$nextIndent}{$k} => '".addslashes((string) $value)."',";
            }
        }
        $lines[] = "{$indent}]";

        return implode("\n", $lines);
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
