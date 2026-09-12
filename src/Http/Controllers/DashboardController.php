<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use YoussefMekkkawy\LaravelAiTranslator\Services\RuntimeConfig;

abstract class DashboardController extends Controller
{
    protected function config(?string $key = null, mixed $default = null): mixed
    {
        $base = 'ai-translator';
        return $key ? config("{$base}.{$key}", $default) : config($base, $default);
    }

    protected function view(string $view, array $data = []): View
    {
        return view("ai-translator::{$view}", array_merge($this->sharedData(), $data));
    }

    protected function success(array $data = [], string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    protected function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    protected function sharedData(): array
    {
        $dashLang = request()->cookie('dashboard_lang', 'en');
        $pkgPath  = app('ai-translator.package_path');
        $langFile = $pkgPath . '/resources/lang/' . $dashLang . '/dashboard.php';
        $enFile   = $pkgPath . '/resources/lang/en/dashboard.php';

        $trans = [];
        if (file_exists($langFile)) {
            $trans = include $langFile;
        } elseif (file_exists($enFile)) {
            $trans = include $enFile;
        }

        // FIX: read active provider from RuntimeConfig so the sidebar reflects
        // what the user saved via the dashboard, not the stale static config.
        $runtime = new RuntimeConfig();

        return [
            'activeProvider' => $runtime->get('driver', $this->config('driver', 'ollama')),
            'sourceLang'     => $this->config('default_language', 'en'),
            'dashboardPath'  => $this->config('dashboard.path', 'ai-translator'),
            'version'        => $this->getPackageVersion(),
            '_trans'         => $trans,
            '_dashLang'      => $dashLang,
            '_isRtl'         => in_array($dashLang, ['ar', 'he', 'fa', 'ur']),
        ];
    }

    protected function getPackageVersion(): string
    {
        $composerJson = app('ai-translator.package_path') . '/composer.json';
        if (file_exists($composerJson)) {
            $composer = json_decode(file_get_contents($composerJson), true);
            return $composer['version'] ?? '1.0.0';
        }
        return '1.0.0';
    }
}
