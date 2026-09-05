<?php

namespace YoussefMekkkawy\LaravelAiTranslator\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardEnabled
{
    /**
     * Block access to the dashboard if it has been disabled in config.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('ai-translator.dashboard.enabled', true)) {
            abort(404);
        }

        return $next($request);
    }
}
