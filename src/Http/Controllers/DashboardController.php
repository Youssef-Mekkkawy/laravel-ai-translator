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

        return $key
            ? config("{$base}.{$key}", $default)
            : config($base, $default);
    }

    protected function view(string $view, array $data = []): View
    {
        return view(
            "ai-translator::{$view}",
            array_merge($this->sharedData(), $data)
        );
    }

    protected function success(array $data = [], string $message = 'OK'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
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
        /*
         * All PHP data required by layout.blade.php is prepared here.
         * The existing Blade variable names are intentionally preserved
         * so the JavaScript/template code does not need unnecessary changes.
         */

        // Dashboard UI language.
        $dashLang = session(
            'dashboard_lang',
            request()->cookie('dashboard_lang', 'en')
        );

        // Dashboard languages that already have built-in translations.
        $preTranslated = [
            'en',
            'ar',
            'fr',
            'es',
            'de',
            'zh',
            'ja',
            'tr',
            'ru',
            'pt',
        ];

        // Package paths.
        $pkgPath = app('ai-translator.package_path');

        $langFile = $pkgPath
            .'/resources/lang/'
            .$dashLang
            .'/dashboard.php';

        $enFile = $pkgPath.'/resources/lang/en/dashboard.php';

        // Dashboard translations.
        $trans = file_exists($langFile)
            ? include $langFile
            : (file_exists($enFile) ? include $enFile : []);

        // RTL dashboard languages.
        $rtlLangs = [
            'ar',
            'he',
            'fa',
            'ur',
        ];

        $isRtl = in_array($dashLang, $rtlLangs, true);

        // Main translator configuration.
        $cfgSource = config(
            'ai-translator.default_language',
            'en'
        );

        $cfgDriver = config(
            'ai-translator.driver',
            'ollama'
        );

        // Configured target languages.
        $runtime = new RuntimeConfig;
        $cfgLangs = $runtime->getSupportedLanguages();

        $cfgLangsJs = array_values(
            array_map(
                fn ($l) => [
                    'code' => $l,
                    'label' => strtoupper($l),
                ],
                array_filter(
                    $cfgLangs ?? [],
                    fn ($l) => $l !== $cfgSource
                )
            )
        );

        /*
         * Dashboard language selector.
         *
         * Kept exactly as the current layout expects:
         * code => [English name, native name]
         */
        $allLangs = [
            'en' => ['English', 'English'],
            'ar' => ['Arabic', 'العربية'],
            'fr' => ['French', 'Français'],
            'es' => ['Spanish', 'Español'],
            'de' => ['German', 'Deutsch'],
            'zh' => ['Chinese', '中文'],
            'ja' => ['Japanese', '日本語'],
            'tr' => ['Turkish', 'Türkçe'],
            'ru' => ['Russian', 'Русский'],
            'pt' => ['Portuguese', 'Português'],
            'ko' => ['Korean', '한국어'],
            'it' => ['Italian', 'Italiano'],
            'nl' => ['Dutch', 'Nederlands'],
            'pl' => ['Polish', 'Polski'],
            'hi' => ['Hindi', 'हिन्दी'],
            'sv' => ['Swedish', 'Svenska'],
            'vi' => ['Vietnamese', 'Tiếng Việt'],
            'id' => ['Indonesian', 'Bahasa Indonesia'],
        ];

        /*
         * Canonical full ISO 639-1 catalog used by the Add Language modal
         * and dashboard language selector.
         */
        $languageCatalogFile =
            $pkgPath.'/resources/data/languages.php';

        $languageCatalog = file_exists($languageCatalogFile)
            ? include $languageCatalogFile
            : [];

        $dashboardLangs = array_map(
            fn ($lang) => [
                'code' => $lang['code'],
                'name' => $lang['name'],
                'native' => $lang['native'],
                'preTranslated' => in_array(
                    $lang['code'],
                    $preTranslated,
                    true
                ),
            ],
            $languageCatalog
        );

        /*
         * Active provider information.
         */
        $providerConfig = config(
            'ai-translator.providers.'.$cfgDriver,
            []
        );

        $providerModel = is_array($providerConfig)
            ? (
                $providerConfig['model']
                ?? ($providerConfig['plan'] ?? '')
            )
            : '';

        $providerNames = [
            'ollama' => 'Ollama',
            'claude' => 'Claude',
            'chatgpt' => 'ChatGPT',
            'gemini' => 'Gemini',
            'deepl' => 'DeepL',
        ];

        $providerName =
            $providerNames[$cfgDriver]
            ?? ucfirst($cfgDriver);

        $providerDisplay = $providerModel
            ? $providerName.' · '.$providerModel
            : $providerName;

        return [
            // Existing shared dashboard data.
            'activeProvider' => $runtime->get(
                'driver',
                $cfgDriver
            ),
            'sourceLang' => $cfgSource,
            'dashboardPath' => config(
                'ai-translator.dashboard.path',
                'ai-translator'
            ),
            'version' => $this->getPackageVersion(),
            'logoBase64' => $this->getLogoBase64(),

            // Variables used by the existing layout.
            '_trans' => $trans,
            '_dashLang' => $dashLang,
            '_isRtl' => $isRtl,
            'cfgLangs' => $cfgLangs,

            // Variables previously created in layout.blade.php.
            '_preTranslated' => $preTranslated,
            '_pkgPath' => $pkgPath,
            '_langFile' => $langFile,
            '_enFile' => $enFile,
            '_rtlLangs' => $rtlLangs,

            'cfgSource' => $cfgSource,
            'cfgDriver' => $cfgDriver,
            'cfgLangsJs' => $cfgLangsJs,

            '_allLangs' => $allLangs,

            '_languageCatalogFile' => $languageCatalogFile,
            '_languageCatalog' => $languageCatalog,
            '_dashboardLangs' => $dashboardLangs,

            '_providerConfig' => $providerConfig,
            '_providerModel' => $providerModel,
            '_providerNames' => $providerNames,
            '_providerName' => $providerName,
            '_providerDisplay' => $providerDisplay,
        ];
    }

    protected function getPackageVersion(): string
    {
        $composerJson =
            app('ai-translator.package_path')
            .'/composer.json';

        if (file_exists($composerJson)) {
            $composer = json_decode(
                file_get_contents($composerJson),
                true
            );

            return $composer['version'] ?? '1.0.0';
        }

        return '1.0.0';
    }

    protected function getLogoBase64(): string
    {
        $path =
            app('ai-translator.package_path')
            .'/resources/images/logo-icon.png';

        if (file_exists($path)) {
            return 'data:image/png;base64,'
                .base64_encode(file_get_contents($path));
        }

        return '';
    }
}
