<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers;

use Illuminate\Support\Facades\File;

class HistoryController extends DashboardController
{
    public function index()
    {
        $config   = config('ai-translator', []);
        $metaFile = $config['storage']['metadata_file']
            ?? base_path('lang/.translations-meta.json');

        $runs = [];

        if (File::exists($metaFile)) {
            $meta = json_decode(File::get($metaFile), true) ?? [];
            $runs = $meta['runs'] ?? [];
            // Newest first
            usort($runs, fn ($a, $b) => strcmp($b['started_at'], $a['started_at']));
        }

        return $this->view('history', compact('runs'));
    }
}
