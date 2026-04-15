<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;

class UnlockTranslationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:unlock 
                            {language : Language code (e.g., ar, fr, es)}
                            {key : Translation key to unlock (e.g., auth.login)}
                            {--all : Unlock all keys matching pattern (e.g., auth.*)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Unlock a translation to allow auto-translation updates';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $language = $this->argument('language');
        $key = $this->argument('key');
        $all = $this->option('all');

        // Initialize lock manager
        $storage = new LockStorage();
        $lockManager = new LockManager($storage);

        // Check if pattern mode (--all flag)
        if ($all) {
            return $this->unlockPattern($lockManager, $language, $key);
        }

        // Single key mode
        return $this->unlockSingle($lockManager, $language, $key);
    }

    /**
     * Unlock a single translation key
     */
    protected function unlockSingle(LockManager $lockManager, string $language, string $key): int
    {
        // Check if locked
        if (!$lockManager->isLocked($language, $key)) {
            $this->warn("⚠️  Translation is not locked: {$language}/{$key}");
            return self::SUCCESS;
        }

        // Show lock info before unlocking
        $lock = $lockManager->getLock($language, $key);
        $this->info("📋 Current lock info:");
        $this->info("   Locked at: {$lock['locked_at']}");
        $this->info("   Locked by: {$lock['locked_by']}");
        if ($lock['reason']) {
            $this->info("   Reason: {$lock['reason']}");
        }
        if ($lock['value']) {
            $this->info("   Value: {$lock['value']}");
        }

        $this->newLine();

        // Confirm unlock
        if (!$this->confirm("Unlock this translation?", true)) {
            $this->info("❌ Unlock cancelled.");
            return self::SUCCESS;
        }

        // Unlock the key
        if ($lockManager->unlock($language, $key)) {
            $this->info("🔓 Unlocked: {$language}/{$key}");
            $this->newLine();
            $this->info("✅ Translation can now be auto-updated.");
            $this->comment("💡 It will be re-translated on the next 'lang:translate' run.");
            
            return self::SUCCESS;
        }

        $this->error("❌ Failed to unlock translation.");
        return self::FAILURE;
    }

    /**
     * Unlock multiple keys matching a pattern
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