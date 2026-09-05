<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseCommand extends Command
{
    protected $signature = 'lang:diagnose';

    protected $description = 'Diagnose Laravel AI Translator configuration';

    public function handle(): int
    {
        $this->info('🔍 Laravel AI Translator - Configuration Diagnostic');
        $this->newLine();

        // Check config file
        $this->info('1️⃣  Config File Check:');
        $config = config('laravel-ai-translator');

        if (empty($config)) {
            $this->error('   ✗ Config file not loaded!');
            $this->comment('   Run: php artisan vendor:publish --tag=laravel-ai-translator-config');

            return self::FAILURE;
        }
        $this->info('   ✓ Config file loaded');

        // Check ENV variables directly
        $this->newLine();
        $this->info('2️⃣  Environment Variables (.env file):');
        $envDriver = env('AUTO_TRANSLATE_DRIVER');
        $envApiKey = env('DEEPL_API_KEY');
        $envLanguages = env('SUPPORTED_LANGUAGES');
        $envDefaultLang = env('DEFAULT_LANGUAGE');

        $this->line('   AUTO_TRANSLATE_DRIVER: '.($envDriver ?: '❌ NOT SET'));
        $this->line('   DEEPL_API_KEY: '.($envApiKey ? '✅ SET' : '❌ NOT SET'));
        $this->line('   SUPPORTED_LANGUAGES: '.($envLanguages ?: '❌ NOT SET'));
        $this->line('   DEFAULT_LANGUAGE: '.($envDefaultLang ?: '❌ NOT SET'));

        if (! $envDriver || ! $envApiKey || ! $envLanguages) {
            $this->newLine();
            $this->warn('⚠️  Missing .env configuration!');
            $this->newLine();
            $this->comment('Add these to your .env file:');
            $this->line('   AUTO_TRANSLATE_DRIVER=deepl');
            $this->line('   DEEPL_API_KEY="your-key:fx"');
            $this->line('   SUPPORTED_LANGUAGES=en,ar,fr,es');
            $this->line('   DEFAULT_LANGUAGE=en');
            $this->newLine();
            $this->comment('Then run: php artisan config:clear');

            return self::FAILURE;
        }

        // Check driver
        $this->newLine();
        $this->info('3️⃣  Driver Configuration:');
        $driver = $config['driver'] ?? env('AUTO_TRANSLATE_DRIVER', 'not set');
        $this->line("   Driver: {$driver}");

        // Check DeepL configuration
        if ($driver === 'deepl') {
            $this->newLine();
            $this->info('4️⃣  DeepL Configuration:');

            $deeplConfig = $config['providers']['deepl'] ?? [];
            $apiKey = $deeplConfig['api_key'] ?? null;

            if (empty($apiKey)) {
                $this->error('   ✗ DeepL API key NOT configured in config!');

                return self::FAILURE;
            }

            $maskedKey = substr($apiKey, 0, 8).'...'.substr($apiKey, -3);
            $this->info("   ✓ API Key: {$maskedKey}");
            $this->line('   Plan: '.($deeplConfig['plan'] ?? 'not set'));
        }

        // Check languages
        $this->newLine();
        $this->info('5️⃣  Language Configuration:');
        $languages = $config['languages'] ?? [];
        $this->line('   Target Languages: '.(empty($languages) ? '❌ NOT SET' : implode(', ', $languages)));
        $this->line('   Source Language: '.($config['default_language'] ?? '❌ NOT SET'));

        // Check paths
        $this->newLine();
        $this->info('6️⃣  Path Configuration:');
        $scanPaths = $config['scan_paths'] ?? [];
        foreach ($scanPaths as $path) {
            $exists = is_dir($path);
            $status = $exists ? '✓' : '✗';
            $this->line("   {$status} {$path}");
        }

        // Summary
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('              DIAGNOSTIC COMPLETE');
        $this->info('═══════════════════════════════════════');

        if (! empty($apiKey) && ! empty($languages)) {
            $this->newLine();
            $this->info('✅ Configuration looks good!');
            $this->comment('💡 Try: php artisan lang:translate --force');
        }

        return self::SUCCESS;
    }
}
