<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use YoussefMekkkawy\LaravelAiTranslator\Services\TranslationService;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\LanguageFileWriter;
use YoussefMekkkawy\LaravelAiTranslator\Services\Backup\BackupService;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\HashGenerator;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\ChangeTracker;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\MetadataManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;

class TranslateCommand extends Command
{
    protected $signature = 'lang:translate
                            {--force : Re-translate all keys, even if they already exist}
                            {--dry-run : Preview what would be translated without making changes}
                            {--lang= : Translate to a specific language only}
                            {--no-backup : Skip creating backups (not recommended)}';

    protected $description = 'Automatically translate all translation keys to configured languages';

    protected TranslationService $translationService;

    public function handle(): int
    {
        $this->info("\n🌍 Laravel AI Auto-Translator\n");

        // Build service stack
        try {
            $this->initializeServices();
        } catch (\Throwable $e) {
            $this->error('Failed to initialise services: ' . $e->getMessage());
            return self::FAILURE;
        }

        $force        = $this->option('force');
        $dryRun       = $this->option('dry-run');
        $specificLang = $this->option('lang');

        // Determine target languages
        $targetLanguages = $this->getTargetLanguages($specificLang);

        // ── Cost estimation ──────────────────────────────────────────────
        $this->info('📊 Analysing translation requirements...');
        $this->newLine();

        $totalChars = 0;
        $estimatedCost = 0.0;

        try {
            $estimation    = $this->translationService->estimateCost($targetLanguages, $force);
            $totalChars    = $estimation['total_characters'] ?? $estimation['characters'] ?? 0;
            $estimatedCost = $estimation['estimated_cost']  ?? $estimation['cost']       ?? 0.0;
        } catch (\Throwable $e) {
            // No translator configured — that's fine for dry-run / skip-all scenarios
        }

        $this->line("  Total Characters : <fg=cyan>{$totalChars}</>");
        $this->line('  Estimated Cost   : <fg=cyan>$' . number_format($estimatedCost, 4) . '</>');
        $this->newLine();

        // Target languages summary
        if (!empty($targetLanguages)) {
            $this->line('  Target languages : ' . implode(', ', $targetLanguages));
            $this->newLine();
        }

        // ── Dry-run exit ─────────────────────────────────────────────────
        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
            return self::SUCCESS;
        }

        // ── Confirmation ─────────────────────────────────────────────────
        if (!$this->confirm('Start translation?', true)) {
            $this->info('Translation cancelled.');
            $this->newLine();
            return self::SUCCESS;
        }

        // ── Run translation ──────────────────────────────────────────────
        $this->newLine();
        $this->info('🚀 Starting translation...');
        $this->newLine();

        try {
            $results = $this->translationService->translateAll($targetLanguages, $force, false);
        } catch (\Throwable $e) {
            $this->error('Translation failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        // ── Results display ──────────────────────────────────────────────
        $this->info('═══════════════════════════════════════');
        $this->info('         ✅ TRANSLATION COMPLETE         ');
        $this->info('═══════════════════════════════════════');
        $this->newLine();

        $this->line('  Total keys       : ' . ($results['total_keys']          ?? 0));
        $this->line('  Languages done   : ' . ($results['languages_processed'] ?? 0));
        $this->line('  Skipped (exist)  : ' . ($results['skipped_unchanged']   ?? 0));
        $this->line('  Locked (skipped) : ' . ($results['locked_keys']         ?? 0));

        if (!empty($results['files_written'])) {
            $this->newLine();
            $this->info('📁 Files written:');
            foreach ($results['files_written'] as $file) {
                $this->line('  ✔ ' . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file));
            }
        }

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->warn('⚠️  Errors:');
            foreach ($results['errors'] as $lang => $error) {
                $this->error("  ✗ {$lang}: {$error}");
            }
        }

        $this->newLine();

        if (($results['skipped_unchanged'] ?? 0) > 0) {
            $this->info('💰 Change tracking saved API costs by skipping ' . $results['skipped_unchanged'] . ' unchanged key(s)!');
        }

        $this->info("💡 Run 'php artisan lang:scan' to review your translations.");
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Build all service objects the translation pipeline needs.
     */
    protected function initializeServices(): void
    {
        $config     = config('ai-translator', []);
        $noBackup   = $this->option('no-backup');

        // ── Scanner ───────────────────────────────────────────────────────
        $scanPaths = $config['scan_paths'] ?? [resource_path('views')];
        $scanner   = new ViewScanner($scanPaths);
        $extractor = new KeyExtractor();

        // ── Backup / Writer ───────────────────────────────────────────────
        $backupConfig = array_merge(
            $config['backup'] ?? [],
            ['lang_path' => lang_path()]
        );

        if ($noBackup) {
            $backupConfig['enabled'] = false;
        }

        $backupService = new BackupService($backupConfig);
        $writer        = new LanguageFileWriter($backupService, ['lang_path' => lang_path()]);

        // ── Change tracking ───────────────────────────────────────────────
        $metaFile      = $config['storage']['metadata_file'] ?? base_path('lang/.translations-meta.json');
        $hashGenerator = new HashGenerator();
        $metaManager   = new MetadataManager($metaFile);
        $changeTracker = new ChangeTracker($hashGenerator, $metaManager);

        // ── Lock manager ──────────────────────────────────────────────────
        $lockStorage = new LockStorage();
        $lockManager = new LockManager($lockStorage);

        // ── Translator manager ────────────────────────────────────────────
        $translatorManager = new TranslatorManager($config);

        // ── Translation service ───────────────────────────────────────────
        $this->translationService = new TranslationService(
            $scanner,
            $extractor,
            $translatorManager,
            $writer,
            $changeTracker,
            $lockManager,
            $config
        );
    }

    /**
     * Resolve the list of languages to translate into.
     */
    protected function getTargetLanguages(?string $specificLang): array
    {
        $config     = config('ai-translator', []);
        $sourceLang = $config['default_language'] ?? 'en';
        $all        = $config['languages']         ?? ['en', 'ar'];

        if ($specificLang !== null) {
            return [$specificLang];
        }

        return array_values(array_filter($all, fn ($lang) => $lang !== $sourceLang));
    }
}
