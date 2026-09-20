<?php

namespace YoussefMekkkawy\LaravelAiTranslator\View;

use Illuminate\View\View;

/**
 * Prepares everything the dashboard layout needs, so the Blade file stays markup-only.
 *
 * Register in the service provider's boot():
 *
 *     use Illuminate\Support\Facades\View;
 *     use YoussefMekkkawy\LaravelAiTranslator\View\DashboardLayoutComposer;
 *
 *     View::composer('ai-translator::layout', DashboardLayoutComposer::class);
 */
class DashboardLayoutComposer
{
    /** Interface languages that ship with a ready-made resources/lang/<code>/dashboard.php. */
    private const PRE_TRANSLATED = ['en', 'ar', 'fr', 'es', 'de', 'zh', 'ja', 'tr', 'ru', 'pt'];

    private const RTL_LANGS = ['ar', 'he', 'fa', 'ur'];

    private const PROVIDER_NAMES = [
        'ollama'  => 'Ollama',
        'claude'  => 'Claude',
        'chatgpt' => 'ChatGPT',
        'gemini'  => 'Gemini',
        'deepl'   => 'DeepL',
    ];

    public function compose(View $view): void
    {
        $request = request();

        $lang         = session('dashboard_lang', $request->cookie('dashboard_lang', 'en'));
        $isRtl        = in_array($lang, self::RTL_LANGS, true);
        $isUnusedPage = (bool) $request->query('__unused_keys');

        $source = config('ai-translator.default_language', 'en');
        $driver = config('ai-translator.driver', 'ollama');
        $base   = url(trim(config('ai-translator.dashboard.path', 'ai-translator'), '/'));

        $packagePath = app('ai-translator.package_path');
        $trans       = $this->translations($packagePath, $lang);
        $catalog     = $this->load($packagePath . '/resources/data/languages.php');

        // Target languages (everything except the source), passed to the view by the controller as $cfgLangs.
        $configuredLangs = array_values(array_map(
            fn ($code) => ['code' => $code, 'label' => strtoupper($code)],
            array_filter($view->getData()['cfgLangs'] ?? [], fn ($code) => $code !== $source)
        ));

        // Interface-language dropdown: the full catalog, flagged with "has a ready-made translation".
        $dashboardLangs = array_map(
            fn ($l) => [
                'code'          => $l['code'],
                'name'          => $l['name'],
                'native'        => $l['native'],
                'preTranslated' => in_array($l['code'], self::PRE_TRANSLATED, true),
            ],
            $catalog
        );

        $view->with([
            'dashLang'        => $lang,
            'isRtl'           => $isRtl,
            'isUnusedPage'    => $isUnusedPage,
            'trans'           => $trans,
            'configuredLangs' => $configuredLangs,

            // Serialised to window.AIT in the layout and consumed by dashboard.js.
            'ait' => [
                'rtl'             => $isRtl,
                'page'            => $isUnusedPage ? 'unused' : ($request->segment(2) ?: 'overview'),
                'base'            => $base,
                'currentLang'     => $lang,
                'sourceLang'      => $source,
                'lockLang'        => $configuredLangs[0]['code'] ?? 'ar',
                'provider'        => $driver,
                'providerDisplay' => $this->providerDisplay($driver),
                'configuredLangs' => $configuredLangs,
                'languageCatalog' => $catalog,
                'dashboardLangs'  => $dashboardLangs,
                'strings'         => $trans,
                'routes'          => [
                    'overview'  => route('ai-translator.overview'),
                    'languages' => route('ai-translator.languages'),
                    'locked'    => route('ai-translator.locked'),
                    'history'   => route('ai-translator.history'),
                    'backups'   => route('ai-translator.backups'),
                    'settings'  => route('ai-translator.settings'),
                    'unused'    => route('ai-translator.overview', ['__unused_keys' => 1]),
                ],
            ],
        ]);
    }

    /** Dashboard strings for $lang, falling back to English when that language has no file. */
    private function translations(string $packagePath, string $lang): array
    {
        $file = $packagePath . '/resources/lang/' . $lang . '/dashboard.php';

        if (! is_file($file)) {
            $file = $packagePath . '/resources/lang/en/dashboard.php';
        }

        return $this->load($file);
    }

    /** "Ollama · llama3" when the provider config names a model or plan, otherwise just "Ollama". */
    private function providerDisplay(string $driver): string
    {
        $config = config('ai-translator.providers.' . $driver, []);
        $model  = is_array($config) ? ($config['model'] ?? ($config['plan'] ?? '')) : '';
        $name   = self::PROVIDER_NAMES[$driver] ?? ucfirst($driver);

        return $model ? $name . ' · ' . $model : $name;
    }

    private function load(string $file): array
    {
        if (! is_file($file)) {
            return [];
        }

        $data = include $file;

        return is_array($data) ? $data : [];
    }
}
