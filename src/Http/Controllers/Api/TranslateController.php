<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use Illuminate\Http\Request;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\TranslationQueue;

class TranslateController extends DashboardController
{
    /**
     * Queue a translation job and return immediately.
     *
     * The job runs in a background process (lang:work-queue) so:
     * - The web server thread is free the moment this returns
     * - The developer's site never blocks for visitors
     * - The dashboard stays navigable during translation
     * - Multiple jobs can be queued (e.g. adding several languages at once)
     */
    public function run(Request $request)
    {
        set_time_limit(30); // only needed for the queue+spawn, not the translation

        try {
            $queue = new TranslationQueue();

            // Build the job from the request
            $job = [
                'force'   => $request->boolean('force'),
                'dry_run' => $request->boolean('dry_run'),
                'lang'    => $request->input('lang') ?: null,
            ];

            // Add to the queue
            $queue->push($job);

            // Spawn the background worker if not already running.
            // If the worker is already running it will pick up the new job
            // automatically when it finishes the current one.
            if (!$queue->isRunning()) {
                $queue->spawnWorker();
            }

            return $this->success([
                'status'     => 'queued',
                'queue_size' => count($queue->getQueue()),
                'running'    => true,
            ], 'Translation queued and running in background.');

        } catch (\Throwable $e) {
            return $this->error('Failed to queue translation: ' . $e->getMessage());
        }
    }
}
