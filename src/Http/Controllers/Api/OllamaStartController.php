<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use YoussefMekkkawy\LaravelAiTranslator\Services\OllamaManager;

class OllamaStartController extends DashboardController
{
    /**
     * Start Ollama — fires and returns immediately.
     * Browser polls /status endpoint to check if ready.
     */
    public function start()
    {
        $manager = new OllamaManager();

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
            ], $hasModel ? 'Ollama is already running.' : "Ollama running. Model is downloading in background.");
        }

        // Fire and forget — do NOT wait
        $manager->start();

        return $this->success([
            'running'  => false,
            'started'  => true,
            'polling'  => true,
            'model'    => $manager->getModel(),
        ], 'Ollama is starting up. Checking status...');
    }

    /**
     * Check Ollama status — used by browser polling.
     */
    public function status()
    {
        $manager  = new OllamaManager();
        $running  = $manager->isRunning();
        $hasModel = $running && $manager->hasModel();
        $models   = $running ? $manager->listModels() : [];

        // If running but no model — start pulling
        if ($running && !$hasModel) {
            $manager->pullModelBackground();
        }

        return $this->success([
            'running'  => $running,
            'hasModel' => $hasModel,
            'pulling'  => $running && !$hasModel,
            'model'    => $manager->getModel(),
            'models'   => $models,
        ]);
    }
}
