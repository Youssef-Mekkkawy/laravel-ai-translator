<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api;

use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class TranslateController extends DashboardController
{
    public function run(Request $request)
    {
        try {
            $options = [];

            if ($request->boolean('dry_run')) {
                $options['--dry-run'] = true;
            }
            if ($request->boolean('force')) {
                $options['--force'] = true;
            }
            if ($lang = $request->input('lang')) {
                $options['--lang'] = $lang;
            }

            $exitCode = Artisan::call('lang:translate', $options);
            $output   = Artisan::output();

            return $exitCode === 0
                ? $this->success(['output' => $output], 'Translation complete.')
                : $this->error('Translation failed: ' . $output);

        } catch (\Throwable $e) {
            return $this->error('Translation error: ' . $e->getMessage());
        }
    }
}
