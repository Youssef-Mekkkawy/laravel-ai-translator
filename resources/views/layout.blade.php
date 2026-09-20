@php
  $_dashLang = session('dashboard_lang', request()->cookie('dashboard_lang', 'en'));
  $_preTranslated = ['en', 'ar', 'fr', 'es', 'de', 'zh', 'ja', 'tr', 'ru', 'pt'];
  $_pkgPath = app('ai-translator.package_path');
  $_langFile = $_pkgPath . '/resources/lang/' . $_dashLang . '/dashboard.php';
  $_enFile = $_pkgPath . '/resources/lang/en/dashboard.php';
  $_trans = file_exists($_langFile) ? include $_langFile : (file_exists($_enFile) ? include $_enFile : []);
  $_rtlLangs = ['ar', 'he', 'fa', 'ur'];
  $_isRtl = in_array($_dashLang, $_rtlLangs, true);

  $cfgSource = config('ai-translator.default_language', 'en');
  $cfgDriver = config('ai-translator.driver', 'ollama');
  $cfgLangsJs = array_values(array_map(
    fn($l) => ['code' => $l, 'label' => strtoupper($l)],
    array_filter($cfgLangs ?? [], fn($l) => $l !== $cfgSource)
  ));

  $_allLangs = [
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

  // Reuse the package's canonical full ISO 639-1 catalog for the add-language modal.
  $_languageCatalogFile = $_pkgPath . '/resources/data/languages.php';
  $_languageCatalog = file_exists($_languageCatalogFile) ? include $_languageCatalogFile : [];

  // Canonical language list for the dashboard interface selector.
  $_dashboardLangs = array_map(
    fn($lang) => [
      'code' => $lang['code'],
      'name' => $lang['name'],
      'native' => $lang['native'],
      'preTranslated' => in_array($lang['code'], $_preTranslated, true),
    ],
    $_languageCatalog
  );

  $_providerConfig = config('ai-translator.providers.' . $cfgDriver, []);
  $_providerModel = is_array($_providerConfig)
      ? ($_providerConfig['model'] ?? ($_providerConfig['plan'] ?? ''))
      : '';
  $_providerNames = [
    'ollama' => 'Ollama',
    'claude' => 'Claude',
    'chatgpt' => 'ChatGPT',
    'gemini' => 'Gemini',
    'deepl' => 'DeepL',
  ];
  $_providerName = $_providerNames[$cfgDriver] ?? ucfirst($cfgDriver);
  $_providerDisplay = $_providerModel ? $_providerName . ' · ' . $_providerModel : $_providerName;
@endphp

<!DOCTYPE html>
<html lang="{{ $_dashLang }}" dir="{{ $_isRtl ? 'rtl' : 'ltr' }}" class="dark" style="color-scheme:dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>AI Translator — @if(request()->query('__unused_keys'))Unused keys @else @yield('title', 'Dashboard') @endif</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&family=Cairo:wght@500;600;700&display=swap" rel="stylesheet">

  <script>
    try {
      const theme = localStorage.getItem('lat-theme');
      if (theme === 'light') {
        document.documentElement.classList.remove('dark');
        document.documentElement.style.colorScheme = 'light';
      }
    } catch (e) {}
  </script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <link rel="stylesheet" href="{{ url('ai-translator/assets/dashboard.css') }}?v={{ $version }}">

</head>

<body>
  <div
    id="app-shell"
    x-data="dashboard()"
    x-cloak
    :dir="ar ? 'rtl' : 'ltr'"
    @open-lock-modal.window="lockOpen = true"
    @open-add-language.window="addOpen = true"
    @open-restore.window="restoreOpen = true; restoreTarget = $event.detail.timestamp"
  >
    <div class="shell">
      <div class="layout" x-data="{ mobileOpen: false }">
        <div class="sidebar-backdrop" :class="mobileOpen ? 'is-visible' : ''" @click="mobileOpen = false"></div>

        <aside class="sidebar" data-sidebar :class="{ 'is-collapsed': !expanded, 'is-mobile-open': mobileOpen }">
          <div class="sidebar-panel glass">
            <div class="brand-row">
              <div class="brand-icon"><span>AI</span></div>
              <div class="brand-text">
                <p>Laravel AI Translator</p>
                <p>{{ $version }} · open source</p>
              </div>
            </div>

            <nav class="nav" aria-label="Main navigation">
              <button type="button" class="nav-link" :class="page === 'overview' ? 'is-active' : ''" @click="go('overview'); mobileOpen = false">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <rect width="7" height="7" x="3" y="3" rx="1"></rect><rect width="7" height="7" x="14" y="3" rx="1"></rect><rect width="7" height="7" x="14" y="14" rx="1"></rect><rect width="7" height="7" x="3" y="14" rx="1"></rect>
                </svg>
                <span x-show="expanded" x-text="t.overview"></span>
              </button>

              <button type="button" class="nav-link" :class="page === 'languages' ? 'is-active' : ''" @click="go('languages'); mobileOpen = false">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="m5 8 6 6"></path><path d="m4 14 6-6 2-3"></path><path d="M2 5h12"></path><path d="M7 2h1"></path><path d="m22 22-5-10-5 10"></path><path d="M14 18h6"></path>
                </svg>
                <span x-show="expanded" x-text="t.languages"></span>
              </button>

              <button type="button" class="nav-link" :class="page === 'locked' ? 'is-active' : ''" @click="go('locked'); mobileOpen = false">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <rect width="18" height="11" x="3" y="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                <span x-show="expanded" x-text="t.locked"></span>
              </button>

              <button type="button" class="nav-link" :class="page === 'history' ? 'is-active' : ''" @click="go('history'); mobileOpen = false">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M3 12a9 9 0 1 0 3-6.7"></path><path d="M3 4v5h5"></path><path d="M12 7v5l4 2"></path>
                </svg>
                <span x-show="expanded" x-text="t.history"></span>
              </button>

              <button type="button" class="nav-link" :class="page === 'unused' ? 'is-active' : ''" @click="go('unused'); mobileOpen = false">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path>
                </svg>
                <span x-show="expanded">Unused keys</span>
              </button>

              <button type="button" class="nav-link" :class="page === 'backups' ? 'is-active' : ''" @click="go('backups'); mobileOpen = false">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <rect width="20" height="5" x="2" y="3" rx="1"></rect><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path><path d="M10 12h4"></path>
                </svg>
                <span x-show="expanded" x-text="t.backups"></span>
              </button>

              <button type="button" class="nav-link" :class="page === 'settings' ? 'is-active' : ''" @click="go('settings'); mobileOpen = false">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <span x-show="expanded" x-text="t.settings"></span>
              </button>
            </nav>

            <button type="button" class="collapse-btn" @click="toggleSidebar()" aria-label="Collapse sidebar">
              <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="M9 3v18"></path><path d="m16 15-3-3 3-3"></path>
              </svg>
              <span x-show="expanded" x-text="t.collapse"></span>
            </button>
          </div>

          <div class="provider-card glass" x-show="expanded">
            <span class="status-dot"></span>
            <div class="provider-text">
              <p x-text="t.activeProvider || 'Active provider'"></p>
              <p x-text="providerLabel"></p>
            </div>
          </div>

          <div class="support-card glass">
            <p class="support-label" x-show="expanded">Support the project</p>
            <a class="support-link support-link--coffee" href="https://github.com/sponsors/Youssef-Mekkkawy" target="_blank" rel="noreferrer">
              <span aria-hidden="true">☕</span>
              <span x-show="expanded">Buy me a coffee</span>
            </a>
            <a class="support-link support-link--github" href="https://github.com/Youssef-Mekkkawy/laravel-ai-translator" target="_blank" rel="noreferrer">
              <svg class="icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-3.16 19.49c.5.09.68-.22.68-.48v-1.7c-2.78.6-3.37-1.34-3.37-1.34-.45-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.6.07-.6 1 .07 1.53 1.03 1.53 1.03.9 1.53 2.36 1.09 2.94.83.09-.65.35-1.09.63-1.34-2.22-.25-4.55-1.11-4.55-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.65 0 0 .84-.27 2.75 1.02a9.5 9.5 0 0 1 5 0c1.91-1.29 2.75-1.02 2.75-1.02.55 1.38.2 2.4.1 2.65.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.68-4.57 4.93.36.31.68.92.68 1.85v2.74c0 .27.18.58.69.48A10 10 0 0 0 12 2z"></path></svg>
              <span x-show="expanded">Star on GitHub</span>
            </a>
          </div>
        </aside>

        <main class="main">
          <header class="topbar glass">
            <div class="topbar-left">
              <button type="button" class="menu-btn" aria-label="Menu" @click="mobileOpen = true">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true"><path d="M4 5h16"></path><path d="M4 12h16"></path><path d="M4 19h16"></path></svg>
              </button>
              <h1 class="page-title" x-text="pageTitle"></h1>
              <span class="sync-badge"><span class="status-dot"></span>Synced 2 min</span>
            </div>

            <div class="topbar-right" x-data="langSelector()">
<div class="language-control" x-data="langSelector()" @click.outside="open = false">
    <button type="button" 
            class="pill-btn" 
            @click.stop="toggleLanguage($event)" 
            :aria-expanded="open ? 'true' : 'false'" 
            aria-label="Interface language" 
            title="Interface language">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true">
            <circle cx="12" cy="12" r="10"></circle>
            <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
            <path d="M2 12h20"></path>
        </svg>
        <span x-text="currentCode.toUpperCase()">EN</span>
    </button>

    <div class="lang-menu glass" 
         :class="open ? 'is-open' : ''" 
         :style="menuStyle" 
         x-cloak 
         role="menu">
        <template x-for="lang in allLangs" :key="lang.code">
            <button type="button" 
                    :class="lang.code === currentCode ? 'is-active' : ''" 
                    @click="selectLang(lang)">
                <span x-text="lang.native"></span>
                <span style="opacity:.5;font-size:.7rem" x-text="' (' + lang.code.toUpperCase() + ')' + (lang.preTranslated ? '' : ' ✦ AI')"></span>
            </button>
        </template>
    </div>
</div>
              <button type="button" class="icon-circle-btn" data-theme-toggle aria-label="Switch theme" title="Switch theme">
                <svg class="icon icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></svg>
                <svg class="icon icon--moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path></svg>
              </button>

              <span class="avatar"><span>YM</span><span class="avatar-status"></span></span>
            </div>
          </header>

          <div class="content" id="page-content">
            @if(request()->query('__unused_keys'))
              <section class="dashboard-page" aria-labelledby="unused-page-title">
                <section class="page-intro glass--strong">
                  <div class="page-hero-row">
                    <div>
                      <p id="unused-page-title" class="page-intro-title">Unused keys</p>
                      <p class="page-intro-sub">Translation keys that are not currently referenced in your codebase.</p>
                    </div>
                  </div>
                </section>

                <section class="coming-soon-card glass">
                  <div class="coming-soon-icon">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></svg>
                  </div>
                  <p class="coming-soon-title">Unused Keys</p>
                  <p class="coming-soon-text">This dashboard screen is reserved for unused-key cleanup. The existing cleanup API is intentionally unchanged and is not connected to this page yet.</p>
                  <span class="coming-soon-badge"><span class="status-dot" style="width:.35rem;height:.35rem;box-shadow:none"></span>Coming soon</span>
                </section>
              </section>
            @else
              @yield('content')
            @endif
          </div>
        </main>
      </div>
    </div>

    {{-- Restore backup modal --}}
    <div class="modal-backdrop" x-show="restoreOpen" x-cloak @click.self="restoreOpen = false">
      <div class="modal-box">
        <div style="display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid color-mix(in oklab,var(--panel) 55%,transparent)">
          <div style="font-size:14px;font-weight:600;flex:1">{{ $_trans['restore_title'] ?? 'Restore Backup' }}</div>
          <button type="button" @click="restoreOpen = false" style="width:28px;height:28px;display:grid;place-items:center;border-radius:8px;background:color-mix(in oklab,var(--ink) 5%,transparent)">×</button>
        </div>
        <div style="padding:18px 20px">
          <p style="font-size:13px;color:color-mix(in oklab,var(--ink) 60%,transparent);margin-bottom:14px">{{ $_trans['restore_warn'] ?? 'This will replace all current language files.' }}</p>
          <div style="padding:12px 14px;border-radius:10px;background:color-mix(in oklab,var(--panel) 48%,transparent);border:1px solid color-mix(in oklab,var(--panel) 60%,transparent);margin-bottom:18px">
            <div style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;direction:ltr" x-text="restoreTarget"></div>
          </div>
          <div style="display:flex;gap:10px;justify-content:flex-end">
            <button type="button" class="btn btn--ghost" @click="restoreOpen = false">{{ $_trans['cancel'] ?? 'Cancel' }}</button>
            <button type="button" class="btn" style="background:var(--sun);color:#2a1d02" @click="confirmRestore()">{{ $_trans['restore_confirm'] ?? 'Yes, restore' }}</button>
          </div>
        </div>
      </div>
    </div>

    {{-- Lock key modal --}}
    <div class="modal-backdrop" x-show="lockOpen" x-cloak @click.self="lockOpen = false">
      <div class="modal-box">
        <div style="display:flex;align-items:center;padding:18px 20px;border-bottom:1px solid color-mix(in oklab,var(--panel) 55%,transparent)">
          <div style="font-size:14px;font-weight:600;flex:1">{{ $_trans['lock_key_title'] ?? 'Lock a Key' }}</div>
          <button type="button" @click="lockOpen = false" style="width:28px;height:28px;display:grid;place-items:center;border-radius:8px;background:color-mix(in oklab,var(--ink) 5%,transparent)">×</button>
        </div>
        <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <label>
              <span style="display:block;font-size:12px;color:color-mix(in oklab,var(--ink) 58%,transparent);margin-bottom:7px">{{ $_trans['language'] ?? 'Language' }}</span>
              <select x-model="lockLang" style="width:100%;height:38px;padding:0 12px;border-radius:9px;border:1px solid var(--border);background:color-mix(in oklab,var(--panel) 65%,transparent);color:var(--ink);outline:none">
                @foreach(array_filter($cfgLangs ?? [], fn($l) => $l !== $cfgSource) as $l)
                  <option value="{{ $l }}">{{ strtoupper($l) }}</option>
                @endforeach
              </select>
            </label>
            <label>
              <span style="display:block;font-size:12px;color:color-mix(in oklab,var(--ink) 58%,transparent);margin-bottom:7px">{{ $_trans['key'] ?? 'Key' }}</span>
              <input x-model="lockKey" placeholder="auth.login" style="width:100%;height:38px;padding:0 12px;border-radius:9px;border:1px solid var(--border);background:color-mix(in oklab,var(--panel) 65%,transparent);color:var(--ink);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;outline:none;direction:ltr">
            </label>
          </div>
          <label>
            <span style="display:block;font-size:12px;color:color-mix(in oklab,var(--ink) 58%,transparent);margin-bottom:7px">{{ $_trans['reason_optional'] ?? 'Reason (optional)' }}</span>
            <input x-model="lockReason" placeholder="{{ $_trans['reason_placeholder'] ?? 'e.g. Client preferred term' }}" style="width:100%;height:38px;padding:0 12px;border-radius:9px;border:1px solid var(--border);background:color-mix(in oklab,var(--panel) 65%,transparent);color:var(--ink);font-size:13px;outline:none">
          </label>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;padding:0 20px 18px">
          <button type="button" class="btn btn--ghost" @click="lockOpen = false">{{ $_trans['cancel'] ?? 'Cancel' }}</button>
          <button type="button" class="btn btn--primary" @click="confirmLock()" :disabled="!lockKey.trim()">{{ $_trans['lock_key_confirm'] ?? 'Lock Key' }}</button>
        </div>
      </div>
    </div>

    {{-- Add language modal --}}
    <div class="modal-backdrop" x-show="addOpen" x-cloak @click.self="addOpen = false">
      <div class="modal-box">
        <div style="display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid color-mix(in oklab,var(--panel) 55%,transparent)">
          <div style="font-size:14px;font-weight:600;flex:1">{{ $_trans['add_language'] ?? 'Add language' }}</div>
          <button type="button" @click="addOpen = false" style="width:28px;height:28px;display:grid;place-items:center;border-radius:8px;background:color-mix(in oklab,var(--ink) 5%,transparent)">×</button>
        </div>
        <div style="padding:16px 20px 20px">
          <input x-model="addQuery" placeholder="{{ $_trans['search_language'] ?? 'Search languages...' }}" style="width:100%;height:40px;padding:0 12px;border-radius:9px;border:1px solid var(--border);background:color-mix(in oklab,var(--panel) 65%,transparent);color:var(--ink);font-size:13px;outline:none;margin-bottom:10px">
          <div style="display:flex;flex-direction:column;gap:6px;max-height:260px;overflow-y:auto">
            <template x-for="opt in filteredAddOptions" :key="opt.code">
              <button type="button" @click="pickLanguage(opt)" style="display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;border-radius:9px;border:1px solid color-mix(in oklab,var(--panel) 60%,transparent);background:color-mix(in oklab,var(--panel) 55%,transparent);color:var(--ink);text-align:start">
                <span style="width:32px;height:22px;flex:none;border-radius:5px;background:color-mix(in oklab,var(--panel) 70%,transparent);border:1px solid color-mix(in oklab,var(--panel) 65%,transparent);display:grid;place-items:center;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:10px" x-text="opt.code"></span>
                <span style="font-size:13px;font-weight:500" x-text="opt.name"></span>
                <span style="margin-inline-start:auto;font-size:12px;opacity:.6" x-text="opt.native"></span>
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>

    {{-- Toasts --}}
    <div style="position:fixed;bottom:24px;inset-inline-end:24px;z-index:80;display:flex;flex-direction:column;gap:10px;pointer-events:none">
      <template x-for="toast in toasts" :key="toast.id">
        <div style="display:flex;align-items:center;gap:12px;min-width:260px;max-width:360px;padding:13px 16px;border-radius:12px;background:color-mix(in oklab,var(--background) 94%, var(--panel));border:1px solid color-mix(in oklab,var(--panel) 65%,transparent);box-shadow:0 16px 40px rgba(0,0,0,.35);animation:tin .22s ease">
          <span :style="'width:8px;height:8px;flex:none;border-radius:50%;background:' + (toast.kind === 'err' ? 'var(--coral)' : 'var(--mint)')"></span>
          <div>
            <div style="font-size:13px;font-weight:600" x-text="toast.title"></div>
            <div style="font-size:12px;opacity:.6;margin-top:2px" x-text="toast.body" x-show="toast.body"></div>
          </div>
        </div>
      </template>
    </div>
  </div>

  @include('ai-translator::partials.scripts')
</body>
</html>