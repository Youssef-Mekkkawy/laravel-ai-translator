<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\TranslationService;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\LanguageFileWriter;
use YoussefMekkkawy\LaravelAiTranslator\Services\Backup\BackupService;

class TranslateCommand extends Command
{
    protected $signature = 'lang:translate
                            {--force : Re-translate all keys, even if they exist}
                            {--dry-run : Preview what would be translated without making changes}
                            {--lang= : Translate to specific language only}
                            {--no-backup : Skip creating backups (not recommended)}';

    protected $description = 'Automatically translate all translation keys to configured languages';

    protected TranslationService $translationService;

    public function handle(): int
    {
        $this->displayHeader();
        $this->initializeServices();

        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $specificLang = $this->option('lang');

        $this->info("\n📊 Analyzing translation requirements...\n");
        
        try {
            $estimation = $this->estimateCost($specificLang);
            $this->displayEstimation($estimation);

            if ($dryRun) {
                $this->warn("\n🔍 DRY RUN MODE - No changes will be made");
                return self::SUCCESS;
            }

            if (!$this->confirm("\n⚡ Start translation?", true)) {
                $this->warn("❌ Translation cancelled.");
                return self::SUCCESS;
            }

        } catch (\Exception $e) {
            $this->error("❌ Estimation failed: " . $e->getMessage());
            $this->error("Details: " . $e->getTraceAsString());
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("🚀 Starting translation process...\n");

        try {
            $results = $this->runTranslation($force, $specificLang);
            $this->displayResults($results);

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("\n❌ Translation failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    protected function displayHeader(): void
    {
        $this->line("
╔══════════════════════════════════════════════════════════╗
║                                                          ║
║       🌍  Laravel AI Auto-Translator  🤖                ║
║                                                          ║
║       Automatically translate your Laravel app          ║
║       using AI-powered translation engines               ║
║                                                          ║
╚══════════════════════════════════════════════════════════╝
        ");
    }

    protected function initializeServices(): void
    {
        $config = config('laravel-ai-translator');

        // Extract scan paths
        $scanPaths = $config['scan_paths'] ?? [resource_path('views')];
        $excludeFiles = $config['exclude_files'] ?? [];

        // Create services
        $scanner = new ViewScanner($scanPaths, $excludeFiles);
        $extractor = new KeyExtractor();
        
        // FIXED: Pass full config (not $config['translators'])
        // TranslatorManager needs full config to access $config['providers']
        $translatorManager = new TranslatorManager($config);
        
        $backupService = new BackupService($config['backup'] ?? []);
        $writer = new LanguageFileWriter($backupService, $config);

        $this->translationService = new TranslationService(
            $scanner,
            $extractor,
            $translatorManager,
            $writer,
            $config
        );
    }

    protected function estimateCost(?string $specificLang): array
    {
        $options = [];

        if ($specificLang) {
            $options['target_langs'] = [$specificLang];
        }

        return $this->translationService->estimateCost($options);
    }

    protected function displayEstimation(array $estimation): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Keys', number_format($estimation['total_keys'])],
                ['Total Characters', number_format($estimation['total_characters'])],
                ['Target Languages', implode(', ', $estimation['languages'])],
                ['Estimated Cost', '$' . number_format($estimation['estimated_cost'], 4)],
                ['Estimated Time', $estimation['estimated_time']],
            ]
        );
    }

    protected function runTranslation(bool $force, ?string $specificLang): array
    {
        $options = [
            'force' => $force,
        ];

        if ($specificLang) {
            $options['target_langs'] = [$specificLang];
        }

        $this->output->progressStart(100);

        $results = $this->translationService->translateAll($options);

        $this->output->progressFinish();

        return $results;
    }

    protected function displayResults(array $results): void
    {
        $this->newLine(2);
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("              ✅  TRANSLATION COMPLETE!                ");
        $this->info("═══════════════════════════════════════════════════════");
        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Keys', number_format($results['total_keys'])],
                ['Languages Processed', count($results['languages'])],
                ['Files Written', count($results['files_written'])],
                ['Duration', $results['duration'] . 's'],
            ]
        );

        if (!empty($results['languages'])) {
            $this->newLine();
            $this->info("📊 Translation Breakdown:");
            $this->newLine();

            $rows = [];
            foreach ($results['languages'] as $lang => $data) {
                $rows[] = [
                    strtoupper($lang),
                    $data['translated'] ?? 0,
                    $data['skipped'] ?? 0,
                    count($data['files'] ?? []),
                ];
            }

            $this->table(
                ['Language', 'Translated', 'Skipped', 'Files'],
                $rows
            );
        }

        if (!empty($results['files_written'])) {
            $this->newLine();
            $this->info("📁 Files Created/Updated:");
            foreach ($results['files_written'] as $file) {
                $this->line("  ✓ " . str_replace(base_path(), '', $file));
            }
        }

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->warn("⚠️  Errors:");
            foreach ($results['errors'] as $lang => $error) {
                $this->error("  ✗ {$lang}: {$error}");
            }
        }

        $this->newLine();
        $this->info("🎉 All translations saved with automatic backups!");
        $this->info("💡 Use 'php artisan lang:scan' to see what was translated.");
        $this->newLine();
    }
}