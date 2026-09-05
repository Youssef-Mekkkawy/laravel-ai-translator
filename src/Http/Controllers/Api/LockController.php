<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockManager;
use YoussefMekkkawy\LaravelAiTranslator\Services\Lock\LockStorage;
use Illuminate\Http\Request;

class LockController extends DashboardController
{
    private function manager(): LockManager
    {
        return new LockManager(new LockStorage());
    }

    public function lock(Request $request)
    {
        $lang   = $request->input('lang');
        $key    = $request->input('key');
        $reason = $request->input('reason');

        if (!$lang || !$key) {
            return $this->error('Language and key are required.');
        }

        try {
            $this->manager()->lock($lang, $key, $reason);
            return $this->success([], "Key [{$lang}/{$key}] locked.");
        } catch (\Throwable $e) {
            return $this->error('Failed to lock: ' . $e->getMessage());
        }
    }

    public function unlock(Request $request)
    {
        $lang = $request->input('lang');
        $key  = $request->input('key');

        if (!$lang || !$key) {
            return $this->error('Language and key are required.');
        }

        try {
            $this->manager()->unlock($lang, $key);
            return $this->success([], "Key [{$lang}/{$key}] unlocked.");
        } catch (\Throwable $e) {
            return $this->error('Failed to unlock: ' . $e->getMessage());
        }
    }
}
