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
        $language  = $this->option('lang');
        $detailed  = $this->option('detail');

        $storage     = new LockStorage();
        $lockManager = new LockManager($storage);

        $locks = $lockManager->getAll($language);

        if (empty($locks)) {
            $this->info('✨ No locked translations found.');
            $this->comment("💡 Use 'lang:lock' to protect translations from auto-updates.");
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('🔒 Locked Translations');
        $this->line(str_repeat('─', 60));

        if ($detailed) {
            foreach ($locks as $key => $lockInfo) {
                $this->line("\n  🔑 {$key}");
                $this->line('     Value: '     . ($lockInfo['value']     ?? 'N/A'));
                $this->line('     Locked by: ' . ($lockInfo['locked_by'] ?? 'Unknown'));
                $this->line('     Locked at: ' . ($lockInfo['locked_at'] ?? 'Unknown'));

                if (!empty($lockInfo['reason'])) {
                    $this->line('     Reason: ' . $lockInfo['reason']);
                }
            }
        } else {
            $rows = [];
            foreach ($locks as $key => $lockInfo) {
                $value = $lockInfo['value'] ?? 'N/A';
                $rows[] = [
                    $key,
                    mb_strlen($value) > 30
                        ? mb_substr($value, 0, 30) . '...'
                        : $value,
                    $lockInfo['locked_by'] ?? 'Unknown',
                ];
            }

            $this->table(['Key', 'Value', 'Locked By'], $rows);
        }

        $this->newLine();
        return self::SUCCESS;
    }
}
