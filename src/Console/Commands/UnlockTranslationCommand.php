<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;

class UnlockTranslationCommand extends Command
{
    protected $signature = 'lang:unlock
                            {language : Language code (e.g., ar, fr, es)}
                            {key : Translation key to unlock (e.g., auth.login)}
                            {--all : Unlock all keys matching pattern (e.g., auth.*)}';

    protected $description = 'Unlock a translation to allow auto-translation updates';

    public function handle(): int
    {
        $language = $this->argument('language');
        $key      = $this->argument('key');
        $all      = $this->option('all');

        $storage     = new LockStorage();
        $lockManager = new LockManager($storage);

        if ($all) {
            return $this->unlockPattern($lockManager, $language, $key);
        }

        return $this->unlockSingle($lockManager, $language, $key);
    }

    /**
     * Unlock a single translation key
     */
    protected function unlockSingle(LockManager $lockManager, string $language, string $key): int
    {
        if (!$lockManager->isLocked($language, $key)) {
            $this->warn("⚠️  Translation is not locked: {$language}/{$key}");
            return self::FAILURE;
        }

        $lockManager->unlock($language, $key);

        $this->info("🔓 Translation unlocked: {$language}/{$key}");
        $this->newLine();
        $this->info('✅ This translation can now be auto-updated.');
        $this->comment("💡 It will be re-translated on the next 'lang:translate' run.");

        return self::SUCCESS;
    }

    /**
     * Unlock all keys matching a pattern
     */
    protected function unlockPattern(LockManager $lockManager, string $language, string $pattern): int
    {
        $this->info("🔍 Searching for locked keys matching pattern: {$pattern}");

        $count = $lockManager->unlockPattern($language, $pattern);

        if ($count === 0) {
            $this->warn("⚠️  No locked keys found matching pattern: {$pattern}");
            return self::SUCCESS;
        }

        $this->info("🔓 Unlocked {$count} translation(s) matching '{$pattern}'");
        $this->newLine();
        $this->info("✅ {$count} translations can now be auto-updated.");
        $this->comment("💡 They will be re-translated on the next 'lang:translate' run.");

        return self::SUCCESS;
    }
}
