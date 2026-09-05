<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers;

use YoussefMekkkawy\LaravelAiTranslator\Services\Backup\BackupService;
use Illuminate\Http\Request;

class BackupsController extends DashboardController
{
    private function backupService(): BackupService
    {
        $config = config('ai-translator.backup', []);
        return new BackupService($config);
    }

    public function index()
    {
        $service = $this->backupService();
        $backups = $service->isEnabled() ? $service->listBackups() : [];

        return $this->view('backups', [
            'backups'         => $backups,
            'backupEnabled'   => $service->isEnabled(),
            'keepCount'       => config('ai-translator.backup.keep', 5),
        ]);
    }

    public function create()
    {
        try {
            $path = $this->backupService()->backup();
            return $this->success(['path' => $path], 'Backup created successfully.');
        } catch (\Throwable $e) {
            return $this->error('Failed to create backup: ' . $e->getMessage());
        }
    }

    public function restore(Request $request)
    {
        $timestamp = $request->input('timestamp');

        if (!$timestamp) {
            return $this->error('Timestamp is required.');
        }

        try {
            $this->backupService()->restore($timestamp);
            return $this->success([], 'Backup restored successfully.');
        } catch (\Throwable $e) {
            return $this->error('Failed to restore: ' . $e->getMessage());
        }
    }
}
