<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\TranslationQueue;

class TranslateStatusController extends DashboardController
{
    /**
     * Return current translation queue status.
     * Polled by the browser every 3 seconds while a translation is running.
     */
    public function status()
    {
        $queue = new TranslationQueue;
        $status = $queue->getStatus();
        $jobs = $queue->getQueue();

        return $this->success([
            'running' => $status['running'] ?? false,
            'current_job' => $status['current_job'] ?? null,
            'queue_size' => count($jobs),
            'queue' => $jobs,
            'last_completed_at' => $status['last_completed_at'] ?? null,
            'last_result' => $status['last_result'] ?? null,
            'pid' => $status['pid'] ?? null,
        ]);
    }

    /**
     * Emergency reset — clears queue and status.
     * Used when something goes wrong and the queue gets stuck.
     */
    public function reset()
    {
        $queue = new TranslationQueue;
        $queue->reset();

        return $this->success([], 'Translation queue reset.');
    }
}
