<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\OllamaManager;

class OllamaStartController extends DashboardController
{
    /**
     * Start Ollama — fires and returns immediately.
     * Browser polls /status endpoint to check if ready.
     * start() is the ONLY place that triggers a model pull.
     */
    public function start()
    {
        $manager = new OllamaManager;

        if ($manager->isRunning()) {
            $hasModel = $manager->hasModel();

            if (!$hasModel) {
                $manager->pullModelBackground();
            }

            return $this->success([
                'running'  => true,
                'started'  => false,
                'hasModel' => $hasModel,
                'pulling'  => !$hasModel,
                'model'    => $manager->getModel(),
            ], $hasModel ? 'Ollama is already running.' : 'Ollama running. Model is downloading in background.');
        }

        $manager->start();

        return $this->success([
            'running' => false,
            'started' => true,
            'polling' => true,
            'model'   => $manager->getModel(),
        ], 'Ollama is starting up. Checking status...');
    }

    /**
     * Check Ollama status — used by browser polling.
     *
     * FIX: removed pullModelBackground() from here. status() is a read-only
     * polling endpoint and should not trigger side effects. start() already
     * calls pullModelBackground() when it detects a missing model, so calling
     * it again on every poll was firing multiple background processes.
     */
    public function status()
    {
        $manager  = new OllamaManager;
        $running  = $manager->isRunning();
        $hasModel = $running && $manager->hasModel();
        $models   = $running ? $manager->listModels() : [];

        return $this->success([
            'running'  => $running,
            'hasModel' => $hasModel,
            'pulling'  => $running && !$hasModel, // still pulling if running but no model yet
            'model'    => $manager->getModel(),
            'models'   => $models,
        ]);
    }
}
