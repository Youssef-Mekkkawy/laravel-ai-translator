<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands;

use Illuminate\Console\Command;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;

class LockTranslationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:lock 
                            {language : Language code (e.g., ar, fr, es)}
                            {key : Translation key to lock (e.g., auth.login)}
                            {--reason= : Reason for locking this translation}
                            {--all : Lock all keys matching pattern (e.g., auth.*)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lock a translation to prevent it from being overwritten by auto-translation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $language = $this->argument('language');
        $key = $this->argument('key');
        $reason = $this->option('reason');
        $all = $this->option('all');

        // Initialize lock manager
        $storage = new LockStorage();
        $lockManager = new LockManager($storage);

        // Check if pattern mode (--all flag)
        if ($all) {
            return $this->lockPattern($lockManager, $language, $key, $reason);
        }

        // Single key mode
        return $this->lockSingle($lockManager, $language, $key, $reason);
    }

    /**
     * Lock a single translation key
     */
    protected function lockSingle(LockManager $lockManager, string $language, string $key, ?string $reason): int
    {
        // Check if already locked
        if ($lockManager->isLocked($language, $key)) {
            $this->warn("⚠️  Translation already locked: {$language}/{$key}");
            
            $lock = $lockManager->getLock($language, $key);
            $this->info("   Locked at: {$lock['locked_at']}");
            $this->info("   Locked by: {$lock['locked_by']}");
            if ($lock['reason']) {
                $this->info("   Reason: {$lock['reason']}");
            }
            
            if (!$this->confirm('Do you want to update the lock?', false)) {
                return self::SUCCESS;
            }
        }

        // Lock the key
        if ($lockManager->lock($language, $key, $reason)) {
            $this->info("🔒 Locked: {$language}/{$key}");
            
            if ($reason) {
                $this->comment("   Reason: {$reason}");
            }
            
            $this->newLine();
            $this->info("✅ Translation is now protected from auto-updates.");
            $this->comment("💡 Use 'lang:unlock {$language} {$key}' to unlock it.");
            
            return self::SUCCESS;
        }

        $this->error("❌ Failed to lock translation.");
        return self::FAILURE;
    }

    /**
     * Lock multiple keys matching a pattern
     */
    protected function lockPattern(LockManager $lockManager, string $language, string $pattern, ?string $reason): int
    {
        $this->info("🔍 Searching for keys matching pattern: {$pattern}");
        
        $count = $lockManager->lockPattern($language, $pattern, $reason);
        
        if ($count === 0) {
            $this->warn("⚠️  No keys found matching pattern: {$pattern}");
            return self::SUCCESS;
        }

        $this->info("🔒 Locked {$count} translation(s) matching '{$pattern}'");
        
        if ($reason) {
            $this->comment("   Reason: {$reason}");
        }
        
        $this->newLine();
        $this->info("✅ {$count} translations are now protected from auto-updates.");
        $this->comment("💡 Use 'lang:unlock {$language} {$pattern} --all' to unlock them.");
        
        return self::SUCCESS;
    }
}