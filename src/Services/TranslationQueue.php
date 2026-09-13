<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Services;

use Illuminate\Support\Facades\File;

class TranslationQueue
{
    protected string $queueFile;
    protected string $statusFile;

    public function __construct()
    {
        $this->queueFile  = base_path('lang' . DIRECTORY_SEPARATOR . '.translation-queue.json');
        $this->statusFile = base_path('lang' . DIRECTORY_SEPARATOR . '.translation-status.json');
    }

    // ── Queue management ───────────────────────────────────────────────────

    /**
     * Add a job to the end of the queue.
     */
    public function push(array $job): void
    {
        $queue   = $this->getQueue();
        $queue[] = array_merge($job, ['queued_at' => now()->toIso8601String()]);
        $this->saveQueue($queue);
    }

    /**
     * Return all pending jobs without removing them.
     */
    public function getQueue(): array
    {
        if (!File::exists($this->queueFile)) {
            return [];
        }

        try {
            return json_decode(File::get($this->queueFile), true) ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Pop and return the first job from the queue.
     * Returns null if the queue is empty.
     */
    public function shift(): ?array
    {
        $queue = $this->getQueue();

        if (empty($queue)) {
            return null;
        }

        $job = array_shift($queue);
        $this->saveQueue($queue);

        return $job;
    }

    /**
     * Clear all pending jobs.
     */
    public function clear(): void
    {
        $this->saveQueue([]);
    }

    // ── Status management ──────────────────────────────────────────────────

    /**
     * Check if a worker process is currently running.
     * Also validates that the stored PID is actually alive.
     */
    public function isRunning(): bool
    {
        $status = $this->getStatus();

        if (!($status['running'] ?? false)) {
            return false;
        }

        // Verify the PID is still alive — handles crashes gracefully
        $pid = $status['pid'] ?? null;

        if ($pid && !$this->isPidAlive((int) $pid)) {
            // Process died unexpectedly — clean up so next job can start
            $this->setStatus([
                'running'     => false,
                'pid'         => null,
                'current_job' => null,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Return the full current status.
     */
    public function getStatus(): array
    {
        if (!File::exists($this->statusFile)) {
            return [
                'running'           => false,
                'pid'               => null,
                'current_job'       => null,
                'queue_size'        => 0,
                'last_completed_at' => null,
                'last_result'       => null,
                'updated_at'        => null,
            ];
        }

        try {
            return json_decode(File::get($this->statusFile), true) ?? ['running' => false];
        } catch (\Throwable) {
            return ['running' => false];
        }
    }

    /**
     * Merge updates into the current status file.
     */
    public function setStatus(array $updates): void
    {
        $current = $this->getStatus();
        $merged  = array_merge($current, $updates, [
            'updated_at' => now()->toIso8601String(),
        ]);

        File::ensureDirectoryExists(base_path('lang'));
        File::put(
            $this->statusFile,
            json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Hard reset — clears queue and resets status.
     * Used when something goes wrong.
     */
    public function reset(): void
    {
        $this->clear();
        $this->setStatus([
            'running'           => false,
            'pid'               => null,
            'current_job'       => null,
            'last_completed_at' => null,
            'last_result'       => null,
        ]);
    }

    // ── Worker spawning ────────────────────────────────────────────────────

    /**
     * Spawn the queue worker as a detached background process.
     *
     * Uses PHP_BINARY — the exact path of the currently running PHP executable.
     * This works on all platforms and setups without any configuration:
     * Windows, Linux, Mac, Docker, shared hosting, Valet, Herd, Sail.
     *
     * The worker process runs completely independently from the web server.
     * The web server thread is free immediately — the developer's site never blocks.
     */
    public function spawnWorker(): void
    {
        $php     = PHP_BINARY;
        $artisan = base_path('artisan');

        if (PHP_OS_FAMILY === 'Windows') {
            // Windows: popen with 'start /B' detaches the process
            pclose(popen(
                'start /B "" "' . $php . '" "' . $artisan . '" lang:work-queue',
                'r'
            ));
        } else {
            // Linux / macOS: trailing & detaches the process
            exec('"' . $php . '" "' . $artisan . '" lang:work-queue > /dev/null 2>&1 &');
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    protected function saveQueue(array $queue): void
    {
        File::ensureDirectoryExists(base_path('lang'));
        File::put(
            $this->queueFile,
            json_encode(array_values($queue), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Cross-platform check whether a PID is still running.
     */
    protected function isPidAlive(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            exec("tasklist /FI \"PID eq {$pid}\" 2>NUL", $output);
            foreach ($output as $line) {
                if (str_contains($line, (string) $pid)) {
                    return true;
                }
            }
            return false;
        }

        // Linux / macOS: /proc filesystem or POSIX signal
        if (file_exists("/proc/{$pid}")) {
            return true;
        }

        if (function_exists('posix_kill')) {
            return posix_kill($pid, 0);
        }

        // Fallback: shell kill -0
        exec("kill -0 {$pid} 2>/dev/null", $out, $code);
        return $code === 0;
    }
}
