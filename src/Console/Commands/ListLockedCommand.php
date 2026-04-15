<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;

class ListLockedCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:locked 
                            {--lang= : Filter by language (e.g., ar, fr)}
                            {--verbose : Show detailed lock information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all locked translations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $language = $this->option('lang');
        $verbose = $this->option('verbose');

        // Initialize lock manager
        $storage = new LockStorage();
        $lockManager = new LockManager($storage);

        // Get all locks
        $locks = $lockManager->getAll($language);

        // Check if any locks exist
        if (empty($locks)) {
            $this->info("✨ No locked translations found.");
            $this->comment("💡 Use 'lang:lock' to protect translations from auto-updates.");
            return self::SUCCESS;
        }

        // Display header
        $this->line("\n╔══════════════════════════════════════════════════════════╗");
        $this->line("║                                                          ║");
        $this->line("║       🔒  Locked Translations                           ║");
        $this->line("║                                                          ║");
        $this->line("╚══════════════════════════════════════════════════════════╝\n");

        // Count total locks
        $totalLocks = 0;
        foreach ($locks as $langLocks) {
            $totalLocks += count($langLocks);
        }

        $this->info("📊 Total locked translations: {$totalLocks}\n");

        // Display locks by language
        foreach ($locks as $lang => $langLocks) {
            $this->displayLanguageLocks($lang, $langLocks, $verbose);
        }

        // Display helpful tips
        $this->newLine();
        $this->comment("💡 Tips:");
        $this->comment("   • Use --verbose for detailed lock information");
        $this->comment("   • Use --lang=ar to filter by language");
        $this->comment("   • Use 'lang:unlock' to remove locks");

        return self::SUCCESS;
    }

    /**
     * Display locks for a specific language
     */
    protected function displayLanguageLocks(string $language, array $locks, bool $verbose): void
    {
        $this->info("🌍 Language: {$language} ({$this->getLanguageName($language)})");
        $this->line(str_repeat('─', 60));

        if ($verbose) {
            // Detailed view
            foreach ($locks as $key => $lockInfo) {
                $this->line("\n  🔑 {$key}");
                $this->line("     Value: " . ($lockInfo['value'] ?? 'N/A'));
                $this->line("     Locked by: {$lockInfo['locked_by']}");
                $this->line("     Locked at: {$lockInfo['locked_at']}");
                
                if ($lockInfo['reason'] ?? null) {
                    $this->line("     Reason: {$lockInfo['reason']}");
                }
            }
        } else {
            // Compact table view
            $rows = [];
            foreach ($locks as $key => $lockInfo) {
                $rows[] = [
                    $key,
                    mb_substr($lockInfo['value'] ?? 'N/A', 0, 30) . (mb_strlen($lockInfo['value'] ?? '') > 30 ? '...' : ''),
                    $lockInfo['locked_by'] ?? 'Unknown',
                ];
            }

            $this->table(
                ['Key', 'Value', 'Locked By'],
                $rows
            );
        }

        $this->newLine();
    }

    /**
     * Get language name from code
     */
    protected function getLanguageName(string $code): string
    {
        $languages = [
            'ar' => 'Arabic',
            'fr' => 'French',
            'es' => 'Spanish',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'hi' => 'Hindi',
            'tr' => 'Turkish',
            'pl' => 'Polish',
            'nl' => 'Dutch',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'fi' => 'Finnish',
            'no' => 'Norwegian',
            'cs' => 'Czech',
            'el' => 'Greek',
            'he' => 'Hebrew',
            'th' => 'Thai',
            'vi' => 'Vietnamese',
        ];

        return $languages[$code] ?? $code;
    }
}