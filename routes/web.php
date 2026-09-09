<?php

use Illuminate\Support\Facades\Route;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api\ApiSettingsController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api\DashboardLangController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api\LanguageApiController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api\LockController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api\OllamaController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api\OllamaStartController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api\ScanController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\Api\TranslateController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\BackupsController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\HistoryController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\LanguagesController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\LockedKeysController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\OverviewController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Controllers\SettingsController;
use YoussefMekkkawy\LaravelAiTranslator\Http\Middleware\DashboardEnabled;

Route::prefix(config('ai-translator.dashboard.path', 'ai-translator'))
    ->name('ai-translator.')
    ->middleware(['web', DashboardEnabled::class])
    ->group(function () {

        // ── lang ──────────────────────────────────────────────────
        Route::post('/api/dashboard-lang', [DashboardLangController::class, 'generate']);
        Route::get('/set-lang/{locale}', [DashboardLangController::class, 'setLang'])->name('ai-translator.set-lang');
        Route::post('/api/languages/add', [LanguageApiController::class, 'add']);
        Route::get('/api/stats', [LanguageApiController::class, 'stats']);
        Route::post('/api/languages/toggle', [LanguageApiController::class, 'toggle']);
        Route::post('/api/languages/remove', [LanguageApiController::class, 'remove']);

        // ── Pages ──────────────────────────────────────────────────
        Route::get('/', [OverviewController::class,    'index'])->name('overview');
        Route::get('/languages', [LanguagesController::class,   'index'])->name('languages');
        Route::get('/locked', [LockedKeysController::class,  'index'])->name('locked');
        Route::get('/history', [HistoryController::class,     'index'])->name('history');
        Route::get('/settings', [SettingsController::class,    'index'])->name('settings');
        Route::get('/backups', [BackupsController::class,     'index'])->name('backups');

        // ── AJAX API ───────────────────────────────────────────────
        Route::post('/api/scan', [ScanController::class,        'run']);
        Route::post('/api/translate', [TranslateController::class,   'run']);
        Route::post('/api/lock', [LockController::class,        'lock']);
        Route::post('/api/unlock', [LockController::class,        'unlock']);
        Route::post('/api/settings', [ApiSettingsController::class, 'save']);
        Route::post('/api/backups/create', [BackupsController::class,     'create']);
        Route::post('/api/backups/restore', [BackupsController::class,     'restore']);

        // ── Ollama API ───────────────────────────────────────────────────

        Route::post('/api/ollama/start', [OllamaStartController::class, 'start']);
        Route::get('/api/ollama/status', [OllamaStartController::class, 'status']);
        Route::get('/api/ollama-models', [OllamaController::class, 'models']);
    });
