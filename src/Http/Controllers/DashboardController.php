<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\JsonResponse;

abstract class DashboardController extends Controller
{
    /**
     * Package config shortcut.
     */
    protected function config(?string $key = null, mixed $default = null): mixed
    {
        $base = 'ai-translator';
        return $key ? config("{$base}.{$key}", $default) : config($base, $default);
    }

    /**
     * Render a dashboard Blade view.
     * Views live in resources/views/ and are namespaced as ai-translator::*.
     */
    protected function view(string $view, array $data = []): \Illuminate\View\View
    {
        return view("ai-translator::{$view}", array_merge($this->sharedData(), $data));
    }

    /**
     * Return a success JSON response.
     */
    protected function success(array $data = [], string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ]);
    }

    /**
     * Return an error JSON response.
     */
    protected function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }

    /**
     * Data shared across all dashboard pages.
     */
    protected function sharedData(): array
    {
        return [
            'activeProvider' => $this->config('driver', 'ollama'),
            'sourceLang'     => $this->config('default_language', 'en'),
            'dashboardPath'  => $this->config('dashboard.path', 'ai-translator'),
            'version'        => '1.0.0',
        ];
    }
}
