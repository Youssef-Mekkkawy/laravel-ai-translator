<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\Backup\BackupService;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;
use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\KeyExtractor;
use YoussefMekkkawy\LaravelAiTranslator\Services\Scanner\ViewScanner;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\ChangeTracker;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\HashGenerator;
use YoussefMekkkawy\LaravelAiTranslator\Services\Tracking\MetadataManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\TranslationService;
use YoussefMekkkawy\LaravelAiTranslator\Services\Translators\TranslatorManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Writers\LanguageFileWriter;

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

        try {
            $this->initializeServices();
        } catch (\Throwable $e) {
            $this->error('Failed to initialise services: ' . $e->getMessage());
            return self::FAILURE;
        }

        $force        = $this->option('force');
        $dryRun       = $this->option('dry-run');
        $specificLang = $this->option('lang');

        $targetLanguages = $this->getTargetLanguages($specificLang);

        $this->info('📊 Analysing translation requirements...');
        $this->newLine();

        $totalChars    = 0;
        $estimatedCost = 0.0;

        try {
            $estimation    = $this->translationService->estimateCost($targetLanguages, $force);
            $totalChars    = $estimation['total_characters'] ?? $estimation['characters'] ?? 0;
            $estimatedCost = $estimation['estimated_cost']  ?? $estimation['cost']       ?? 0.0;
        } catch (\Throwable $e) {
            // No translator configured — fine for dry-run
        }

        $this->line("  Total Characters : <fg=cyan>{$totalChars}</>");
        $this->line('  Estimated Cost   : <fg=cyan>$' . number_format($estimatedCost, 4) . '</>');
        $this->newLine();

        if (!empty($targetLanguages)) {
            $this->line('  Target languages : ' . implode(', ', $targetLanguages));
            $this->newLine();
        }

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
            return self::SUCCESS;
        }

        if (!$this->confirm('Start translation?', true)) {
            $this->info('Translation cancelled.');
            $this->newLine();
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('🚀 Starting translation...');
        $this->newLine();

        try {
            $results = $this->translationService->translateAll($targetLanguages, $force, false);

            try {
                $metaFile = config(
                    'ai-translator.storage.metadata_file',
                    base_path('lang/.translations-meta.json')
                );

                $changes = [];
                foreach ($targetLanguages as $lang) {
                    $langPath = lang_path($lang);
                    if (!is_dir($langPath)) continue;
                    foreach (glob($langPath . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                        $group = pathinfo($file, PATHINFO_FILENAME);
                        $data  = @include $file;
                        if (!is_array($data)) continue;
                        foreach ($data as $key => $value) {
                            $changes[] = [
                                'lang'  => strtoupper($lang),
                                'key'   => $group . '.' . $key,
                                'value' => is_string($value) ? $value : '',
                                'tag'   => 'new',
                            ];
                        }
                    }
                }
                $changes = array_slice($changes, 0, 50);

                $runtime  = new RuntimeConfig();
                $provider = $runtime->get('driver', config('ai-translator.driver', 'ollama'));
                $model    = $runtime->get('ollama_model',
                    config('ai-translator.providers.' . $provider . '.model', ''));

                $meta = new MetadataManager($metaFile);
                $meta->recordRun([
                    'started_at'      => date('Y-m-d\TH:i:s', (int) LARAVEL_START),
                    'status'          => empty($results['errors']) ? 'success' : (empty($results['files_written']) ? 'failed' : 'partial'),
                    'keys_translated' => count($results['files_written'] ?? []),
                    'total_keys'      => $results['total_keys']       ?? 0,
                    'skipped'         => $results['skipped_unchanged'] ?? 0,
                    'locked'          => $results['locked_keys']       ?? 0,
                    'files_written'   => array_map(
                        fn ($f) => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $f),
                        $results['files_written'] ?? []
                    ),
                    'changes'         => $changes,
                    'languages'       => array_map('strtoupper', $targetLanguages),
                    'duration_ms'     => (int) (($results['duration'] ?? 0) * 1000),
                    'errors'          => $results['errors'] ?? [],
                    'provider'        => $provider,
                    'model'           => $model,
                    'cost'            => 0.0,
                ]);
            } catch (\Throwable $e) {
                // Never let history recording break the translation
            }

        } catch (\Throwable $e) {
            $this->error('Translation failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('═══════════════════════════════════════');
        $this->info('         ✅ TRANSLATION COMPLETE         ');
        $this->info('═══════════════════════════════════════');
        $this->newLine();

        $this->line('  Total keys       : ' . ($results['total_keys']         ?? 0));
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

    protected function initializeServices(): void
    {
        $config   = config('ai-translator', []);
        $noBackup = $this->option('no-backup');

        $runtime   = new RuntimeConfig();
        $scanPaths = $runtime->get('scan_paths', $config['scan_paths'] ?? [resource_path('views')]);
        $scanExts  = $runtime->get('scan_extensions', ['blade.php']);
        $scanPaths = array_filter((array) $scanPaths, fn ($p) => is_dir($p));

        $scanner   = new ViewScanner(array_values($scanPaths), null, $scanExts);
        $extractor = new KeyExtractor();

        $backupConfig = array_merge(
            $config['backup'] ?? [],
            ['lang_path' => lang_path()]
        );

        if ($noBackup) {
            $backupConfig['enabled'] = false;
        }

        $backupService = new BackupService($backupConfig);
        $writer        = new LanguageFileWriter($backupService, ['lang_path' => lang_path()]);

        $metaFile      = $config['storage']['metadata_file'] ?? base_path('lang/.translations-meta.json');
        $hashGenerator = new HashGenerator();
        $metaManager   = new MetadataManager($metaFile);
        $changeTracker = new ChangeTracker($hashGenerator, $metaManager);

        $lockStorage = new LockStorage();
        $lockManager = new LockManager($lockStorage);

        $translatorManager = new TranslatorManager($config);

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
     *
     * FIX: reads from RuntimeConfig so languages added via the dashboard
     * ("Add Language" button) are included in the translation batch.
     * Previously read only from config('ai-translator.languages') — the static
     * config file — so dashboard-added languages were stored in RuntimeConfig
     * but never picked up here, meaning they were never translated.
     */
    protected function getTargetLanguages(?string $specificLang): array
    {
        $config     = config('ai-translator', []);
        $sourceLang = $config['default_language'] ?? 'en';

        $runtime = new RuntimeConfig();
        $all     = $runtime->getSupportedLanguages();

        // Fall back to static config on fresh installs where RuntimeConfig is empty
        if (empty($all)) {
            $all = $config['languages'] ?? ['en', 'ar'];
        }

        if ($specificLang !== null) {
            return [$specificLang];
        }

        return array_values(array_filter($all, fn ($lang) => $lang !== $sourceLang));
    }
}
