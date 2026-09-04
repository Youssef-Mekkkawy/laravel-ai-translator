<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorManager;

class DebugTranslateCommand extends Command
{
    protected $signature = 'lang:debug-translate';
    protected $description = 'Debug TranslateCommand config flow';

    public function handle()
    {
        $this->info("🔍 DEBUGGING CONFIG FLOW\n");

        // Step 1: Load config
        $config = config('laravel-ai-translator');
        $this->line("1️⃣  Config loaded from config('laravel-ai-translator'):");
        $this->line("   - Has 'driver' key: " . (isset($config['driver']) ? '✅ YES' : '❌ NO'));
        $this->line("   - Has 'providers' key: " . (isset($config['providers']) ? '✅ YES' : '❌ NO'));
        $this->line("   - Has 'translators' key: " . (isset($config['translators']) ? '✅ YES' : '❌ NO'));
        
        if (isset($config['driver'])) {
            $this->line("   - Driver value: " . $config['driver']);
        }
        
        if (isset($config['providers']['deepl'])) {
            $apiKey = $config['providers']['deepl']['api_key'] ?? null;
            if ($apiKey) {
                $masked = substr($apiKey, 0, 8) . '...' . substr($apiKey, -3);
                $this->line("   - DeepL API Key: ✅ $masked");
            } else {
                $this->line("   - DeepL API Key: ❌ NULL");
            }
        }

        $this->newLine();

        // Step 2: Test OLD way (buggy)
        $this->line("2️⃣  OLD WAY (BUGGY) - Using \$config['translators']:");
        $oldConfig = $config['translators'] ?? [];
        $this->line("   - Is empty array: " . (empty($oldConfig) ? '❌ YES (BUG!)' : '✅ NO'));
        if (!empty($oldConfig)) {
            dump($oldConfig);
        }

        $this->newLine();

        // Step 3: Test NEW way (fixed)
        $this->line("3️⃣  NEW WAY (FIXED) - Passing full \$config:");
        try {
            $translatorManager = new TranslatorManager($config);
            $translator = $translatorManager->translator('deepl');
            $this->line("   - TranslatorManager created: ✅ SUCCESS");
            $this->line("   - DeepL translator created: ✅ SUCCESS");
            
            // Try to translate
            $result = $translator->translate('Hello', 'ar');
            $this->line("   - Test translation: ✅ SUCCESS");
            $this->line("   - Result: $result");
        } catch (\Exception $e) {
            $this->error("   - ❌ ERROR: " . $e->getMessage());
        }

        $this->newLine();

        // Step 4: Check TranslateCommand source
        $this->line("4️⃣  Checking TranslateCommand.php source code:");
        $commandPath = base_path('vendor/youssef-mekkkawy/laravel-ai-translator/src/Console/Commands/TranslateCommand.php');
        
        if (file_exists($commandPath)) {
            $content = file_get_contents($commandPath);
            
            // Check for the bug
            if (str_contains($content, "\$config['translators']")) {
                $this->error("   - ❌ BUG FOUND! Still using \$config['translators']");
                $this->line("   - Line found: new TranslatorManager(\$config['translators'] ?? [])");
            } else {
                $this->line("   - ✅ Fixed! Using full \$config");
            }
        } else {
            $this->error("   - ❌ File not found");
        }

        $this->newLine();
        
        $this->line("═══════════════════════════════════════════════════════");
        $this->line("              DIAGNOSIS COMPLETE");
        $this->line("═══════════════════════════════════════════════════════");
        
        if (str_contains(file_get_contents($commandPath), "\$config['translators']")) {
            $this->newLine();
            $this->warn("⚠️  ACTION REQUIRED:");
            $this->line("1. Download the FIXED TranslateCommand.php");
            $this->line("2. Copy to: C:\\laragon\\www\\laravel-ai-translator\\src\\Console\\Commands\\TranslateCommand.php");
            $this->line("3. Run: composer dump-autoload");
            $this->line("4. Test: php artisan lang:translate --force");
        }
    }
}