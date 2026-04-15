<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\TranslationService;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\LanguageFileWriter;
use YoussefMekkkawy\LaravelAiTranslator\Services\Backup\BackupService;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\HashGenerator;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\ChangeTracker;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\MetadataManager;

class TranslateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:translate
                            {--force : Re-translate all keys, even if they exist}
                            {--dry-run : Preview what would be translated without making changes}
                            {--lang= : Translate to specific language only}
                            {--no-backup : Skip creating backups (not recommended)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically translate all translation keys to configured languages';

    /**
     * Translation service instance
     */
    protected TranslationService $translationService;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->displayHeader();

        // Initialize services
        $this->initializeServices();

        // Get options
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $specificLang = $this->option('lang');

        // Step 1: Show cost estimation
        $this->info("\n📊 Analyzing translation requirements...\n");
        
        try {
            $estimation = $this->estimateCost($specificLang);
            $this->displayEstimation($estimation);

            if ($dryRun) {
                $this->warn("\n🔍 DRY RUN MODE - No changes will be made");
                return self::SUCCESS;
            }

            // Ask for confirmation
            if (!$this->confirm("\n⚡ Start translation?", true)) {
                $this->warn("❌ Translation cancelled.");
                return self::SUCCESS;
            }

        } catch (\Exception $e) {
            $this->error("❌ Estimation failed: " . $e->getMessage());
            return self::FAILURE;
        }

        // Step 2: Run translation
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

    /**
     * Display command header
     */
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

    /**
     * Initialize all required services
     */
    protected function initializeServices(): void
    {
        $config = config('laravel-ai-translator');

        // Core services
        $scanner = new ViewScanner($config);
        $extractor = new KeyExtractor();
        $translatorManager = new TranslatorManager($config['translators'] ?? []);
        
        // Backup service (needed by LanguageFileWriter)
        $backupService = new BackupService($config['backup'] ?? []);
        $writer = new LanguageFileWriter($backupService, $config);

        // Tracking services (for change detection)
        $hashGenerator = new HashGenerator();
        $metadataPath = $config['change_tracking']['metadata_path'] ?? lang_path('.translations-meta.json');
        $metadataManager = new MetadataManager($metadataPath);
        $changeTracker = new ChangeTracker($hashGenerator, $metadataManager);

        // Initialize translation service with all dependencies
        $this->translationService = new TranslationService(
            $scanner,
            $extractor,
            $translatorManager,
            $writer,
            $changeTracker,
            $config
        );
    }

    /**
     * Estimate translation cost
     */
    protected function estimateCost(?string $specificLang): array
    {
        $config = config('laravel-ai-translator');
        $targetLanguages = $specificLang 
            ? [$specificLang] 
            : $config['target_languages'];
        
        $force = $this->option('force');

        return $this->translationService->estimateCost($targetLanguages, $force);
    }

    /**
     * Display cost estimation
     */
    protected function displayEstimation(array $estimation): void
    {
        $rows = [
            ['Total Keys', number_format($estimation['total_keys'])],
        ];

        // Show skipped keys if change tracking is enabled
        if (isset($estimation['skipped_keys']) && $estimation['skipped_keys'] > 0) {
            $rows[] = ['Skipped (Unchanged)', number_format($estimation['skipped_keys'])];
        }

        $rows[] = ['Total Characters', number_format($estimation['total_characters'])];
        $rows[] = ['Target Languages', implode(', ', $estimation['languages'])];
        $rows[] = ['Estimated Cost', '$' . number_format($estimation['estimated_cost'], 4)];
        $rows[] = ['Estimated Time', $estimation['estimated_time']];

        $this->table(['Metric', 'Value'], $rows);

        // Show helpful message if nothing to translate
        if ($estimation['total_keys'] === 0 || $estimation['total_characters'] === 0) {
            $this->newLine();
            $this->info("✨ No translations needed! All keys are up to date.");
            $this->info("💡 Use --force to re-translate everything anyway.");
        }
    }

    /**
     * Run the translation process
     */
    protected function runTranslation(bool $force, ?string $specificLang): array
    {
        $config = config('laravel-ai-translator');
        $targetLanguages = $specificLang 
            ? [$specificLang] 
            : $config['target_languages'];
        
        $dryRun = $this->option('dry-run');

        // Create progress bar
        $this->output->progressStart(100);

        $results = $this->translationService->translateAll($targetLanguages, $force, $dryRun);

        $this->output->progressFinish();

        return $results;
    }

    /**
     * Display translation results
     */
    protected function displayResults(array $results): void
    {
        $this->newLine(2);
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("              ✅  TRANSLATION COMPLETE!                ");
        $this->info("═══════════════════════════════════════════════════════");
        $this->newLine();

        // Summary statistics
        $rows = [
            ['Total Keys', number_format($results['total_keys'])],
        ];

        if (isset($results['skipped_unchanged']) && $results['skipped_unchanged'] > 0) {
            $rows[] = ['Skipped (Unchanged)', number_format($results['skipped_unchanged'])];
        }

        $rows[] = ['Languages Processed', $results['languages_processed']];
        $rows[] = ['Files Written', count($results['files_written'])];
        $rows[] = ['Duration', $results['duration'] . 's'];

        $this->table(['Metric', 'Value'], $rows);

        // Files written
        if (!empty($results['files_written'])) {
            $this->newLine();
            $this->info("📁 Files Created/Updated:");
            foreach ($results['files_written'] as $file) {
                $this->line("  ✓ " . str_replace(base_path(), '', $file));
            }
        }

        // Errors
        if (!empty($results['errors'])) {
            $this->newLine();
            $this->warn("⚠️  Errors:");
            foreach ($results['errors'] as $lang => $error) {
                $this->error("  ✗ {$lang}: {$error}");
            }
        }

        $this->newLine();
        
        if (count($results['files_written']) > 0) {
            $this->info("🎉 All translations saved with automatic backups!");
        }
        
        if (isset($results['skipped_unchanged']) && $results['skipped_unchanged'] > 0) {
            $this->info("💰 Change tracking saved API costs by skipping " . $results['skipped_unchanged'] . " unchanged keys!");
        }
        
        $this->info("💡 Use 'php artisan lang:scan' to see what was translated.");
        $this->newLine();
    }
}