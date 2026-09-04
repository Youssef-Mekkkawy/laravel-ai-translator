<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;

class ListLockedCommand extends Command
{
    protected $signature = 'lang:locked
                            {--lang= : Filter by language (e.g., ar, fr)}
                            {--detail : Show detailed lock information}';

    protected $description = 'List all locked translations';

    public function handle(): int
    {
        $language = $this->option('lang');
        $detailed = $this->option('detail');

        $storage     = new LockStorage();
        $lockManager = new LockManager($storage);

        $rawLocks = $lockManager->getAll($language);

        // When $language is null, getAll() returns ['ar' => ['key' => info], ...]
        // When $language is set,  getAll() returns ['key' => info, ...]
        // Normalise to a flat list: [['lang' => 'ar', 'key' => 'auth.login', 'info' => [...]], ...]
        $flatLocks = [];

        if ($language !== null) {
            // Already filtered to one language
            foreach ($rawLocks as $key => $info) {
                $flatLocks[] = ['lang' => $language, 'key' => $key, 'info' => $info];
            }
        } else {
            // All languages
            foreach ($rawLocks as $lang => $langLocks) {
                if (!is_array($langLocks)) {
                    continue;
                }
                foreach ($langLocks as $key => $info) {
                    $flatLocks[] = ['lang' => $lang, 'key' => $key, 'info' => $info];
                }
            }
        }

        if (empty($flatLocks)) {
            $this->info('✨ No locked translations found.');
            $this->comment("💡 Use 'lang:lock' to protect translations from auto-updates.");
            return self::SUCCESS;
        }

        // Show header with language info when filtered
        $this->newLine();
        if ($language !== null) {
            $this->info("🔒 Locked Translations — Language: {$language}");
        } else {
            $this->info('🔒 Locked Translations — All Languages');
        }
        $this->line(str_repeat('─', 60));

        if ($detailed) {
            foreach ($flatLocks as $item) {
                $this->line("\n  [{$item['lang']}] 🔑 {$item['key']}");
                $this->line('     Value: '     . ($item['info']['value']     ?? 'N/A'));
                $this->line('     Locked by: ' . ($item['info']['locked_by'] ?? 'Unknown'));
                $this->line('     Locked at: ' . ($item['info']['locked_at'] ?? 'Unknown'));
                if (!empty($item['info']['reason'])) {
                    $this->line('     Reason: ' . $item['info']['reason']);
                }
            }
        } else {
            $rows = [];
            foreach ($flatLocks as $item) {
                $value = $item['info']['value'] ?? 'N/A';
                $rows[] = [
                    $item['lang'],
                    $item['key'],
                    mb_strlen($value) > 30 ? mb_substr($value, 0, 30) . '...' : $value,
                    $item['info']['locked_by'] ?? 'Unknown',
                ];
            }
            $this->table(['Language', 'Key', 'Value', 'Locked By'], $rows);
        }

        $this->newLine();
        return self::SUCCESS;
    }
}
