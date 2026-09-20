<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Console\Commands\Queue;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use YoussefMekkkawy\LaravelAiTranslator\Services\TranslationQueue;

class WorkQueueCommand extends Command
{
    protected $signature = 'lang:work-queue';

    protected $description = 'Process the translation queue in the background (spawned automatically — do not run manually)';

    public function handle(): int
    {
        $queue = new TranslationQueue;

        // Write our PID to status — lets the web process verify we're alive
        $queue->setStatus([
            'running' => true,
            'pid' => getmypid(),
        ]);

        // Register shutdown handler — cleans up status even on crash or kill signal
        register_shutdown_function(function () use ($queue) {
            $queue->setStatus([
                'running' => false,
                'pid' => null,
                'current_job' => null,
                'last_completed_at' => now()->toIso8601String(),
            ]);
        });

        // Process every job in the queue, one at a time
        while (true) {
            $job = $queue->shift();

            // Queue is empty — we're done
            if ($job === null) {
                break;
            }

            // Update status so the dashboard knows what's running
            $queue->setStatus([
                'running' => true,
                'current_job' => $job,
                'queue_size' => count($queue->getQueue()),
            ]);

            // Build artisan options from the job
            $options = ['--no-interaction' => true];

            if (! empty($job['lang'])) {
                $options['--lang'] = $job['lang'];
            }
            if (! empty($job['force'])) {
                $options['--force'] = true;
            }
            if (! empty($job['dry_run'])) {
                $options['--dry-run'] = true;
            }

            // Run the translation — this is what takes time, but it's in
            // a background process so the web server is completely free
            $exitCode = Artisan::call('lang:translate', $options);
            $output = Artisan::output();

            // Record what happened so the browser can show the result
            $queue->setStatus([
                'last_result' => [
                    'job' => $job,
                    'success' => $exitCode === 0,
                    'output' => mb_substr($output, 0, 1000),
                    'finished' => now()->toIso8601String(),
                ],
                'last_completed_at' => now()->toIso8601String(),
            ]);
        }

        // All jobs done — mark as stopped
        $queue->setStatus([
            'running' => false,
            'pid' => null,
            'current_job' => null,
        ]);

        return self::SUCCESS;
    }
}
