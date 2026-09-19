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

  <style>
    :root {
      --radius: 1rem;
      --radius-lg: 1rem;
      --radius-2xl: calc(var(--radius) + 8px);
      --radius-3xl: calc(var(--radius) + 12px);
      --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
      --font-display: "Space Grotesk", "Inter", ui-sans-serif, sans-serif;
      --font-arabic: "Cairo", "Inter", ui-sans-serif, sans-serif;

      --brand: oklch(.628 .191 285.5);
      --brand-foreground: oklch(.99 .005 285);
      --coral: oklch(.762 .126 12.5);
      --coral-strong: oklch(.52 .16 12.5);
      --mint: oklch(.786 .116 163);
      --mint-strong: oklch(.53 .11 163);
      --sky: oklch(.784 .107 240);
      --sky-strong: oklch(.53 .13 245);
      --sun: oklch(.841 .109 75);
      --sun-strong: oklch(.56 .12 68);
      --ink: oklch(.351 .058 288);
      --panel: oklch(1 0 0);
      --glass: 62%;
      --glass-strong: 82%;
      --glass-border: 65%;
      --bg-1: oklch(.955 .03 300);
      --bg-2: oklch(.965 .024 245);
      --bg-3: oklch(.96 .028 10);
      --background: oklch(.968 .014 300);
      --border: oklch(.9 .02 295);
    }

    html.dark {
      --brand: oklch(.712 .152 288);
      --brand-foreground: oklch(.16 .03 288);
      --coral: oklch(.72 .13 12.5);
      --coral-strong: oklch(.84 .1 14);
      --mint: oklch(.75 .12 163);
      --mint-strong: oklch(.87 .11 165);
      --sky: oklch(.73 .11 240);
      --sky-strong: oklch(.85 .09 240);
      --sun: oklch(.8 .11 75);
      --sun-strong: oklch(.89 .1 82);
      --ink: oklch(.93 .015 290);
      --panel: oklch(.42 .032 290);
      --glass: 55%;
      --glass-strong: 72%;
      --glass-border: 34%;
      --bg-1: oklch(.21 .04 300);
      --bg-2: oklch(.185 .035 265);
      --bg-3: oklch(.205 .038 340);
      --background: oklch(.19 .028 290);
      --border: oklch(.38 .03 290);
    }

    *, *::before, *::after { box-sizing: border-box; }
    html, body { min-height: 100%; }
    body {
      margin: 0;
      min-height: 100vh;
      font-family: var(--font-sans);
      color: var(--ink);
      background: var(--background);
      background-image: linear-gradient(135deg, var(--bg-1), var(--bg-2) 45%, var(--bg-3));
      background-attachment: fixed;
      -webkit-font-smoothing: antialiased;
    }
    [dir="rtl"] body { font-family: var(--font-arabic); }
    [dir="rtl"] .tabular-nums { direction: ltr; unicode-bidi: isolate; }
    a { color: inherit; text-decoration: none; }
    button, input, select, textarea { font: inherit; }
    button { color: inherit; background: none; border: 0; cursor: pointer; }
    svg { display: block; }
    p, h1, h2, h3 { margin: 0; }
    [x-cloak], [hidden] { display: none !important; }
    :focus-visible { outline: 2px solid var(--brand); outline-offset: 2px; }

    .glass {
      background-color: color-mix(in oklab, var(--panel) var(--glass), transparent);
      border: 1px solid color-mix(in oklab, var(--panel) var(--glass-border), transparent);
      box-shadow: 0 10px 40px -15px color-mix(in oklab, var(--brand) 38%, transparent);
      backdrop-filter: blur(28px) saturate(140%);
      -webkit-backdrop-filter: blur(28px) saturate(140%);
      border-radius: var(--radius-3xl);
    }
    .glass--strong {
      background-color: color-mix(in oklab, var(--panel) var(--glass-strong), transparent);
      border: 1px solid color-mix(in oklab, var(--panel) calc(var(--glass-border) + 7%), transparent);
      box-shadow: 0 20px 50px -18px color-mix(in oklab, var(--brand) 45%, transparent);
      backdrop-filter: blur(28px) saturate(140%);
      -webkit-backdrop-filter: blur(28px) saturate(140%);
      border-radius: var(--radius-3xl);
    }

    .icon { width: 1rem; height: 1rem; flex-shrink: 0; }
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      padding: .5rem .875rem;
      border-radius: var(--radius-2xl);
      font-size: .75rem;
      font-weight: 600;
      white-space: nowrap;
      box-shadow: 0 1px 2px rgba(0,0,0,.05);
      transition: background-color .15s, opacity .15s, color .15s;
    }
    .btn:disabled { opacity: .6; cursor: not-allowed; }
    .btn--ghost { background: color-mix(in oklab, var(--panel) 70%, transparent); color: color-mix(in oklab, var(--ink) 75%, transparent); }
    .btn--ghost:hover { background: var(--panel); }
    .btn--primary { background: var(--brand); color: var(--brand-foreground); }
    .btn--primary:hover { opacity: .9; }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: .375rem;
      padding: .25rem .5rem;
      border-radius: 999px;
      font-size: .6875rem;
      font-weight: 700;
      white-space: nowrap;
    }
    .badge--missing { background: color-mix(in oklab, var(--sun) 25%, transparent); color: var(--sun-strong); }
    .badge--sync { background: color-mix(in oklab, var(--brand) 15%, transparent); color: var(--brand); }
    .badge--scan, .badge--automatic { background: color-mix(in oklab, var(--sky) 25%, transparent); color: var(--sky-strong); }
    .badge--dry-run { background: color-mix(in oklab, var(--ink) 10%, transparent); color: color-mix(in oklab, var(--ink) 60%, transparent); }
    .badge--manual { background: color-mix(in oklab, var(--brand) 15%, transparent); color: var(--brand); }
    .badge--restore { background: color-mix(in oklab, var(--coral) 25%, transparent); color: var(--coral-strong); }

    .progress-track { height: .5rem; border-radius: 999px; overflow: hidden; background: color-mix(in oklab, var(--ink) 10%, transparent); }
    .progress-fill { height: 100%; border-radius: 999px; transition: width .5s; }
    .progress-fill--brand { background: linear-gradient(to right, var(--brand), var(--sky)); }
    .progress-fill--mint { background: var(--mint); }
    .progress-fill--sun { background: var(--sun); }
    .progress-fill--coral { background: var(--coral); }

    .shell { width: min(100%, 1440px); margin: 0 auto; padding: 1rem; }
    @media (min-width: 1024px) { .shell { padding: 1.5rem; } }
    .layout { display: flex; gap: 1.25rem; align-items: flex-start; }

    .sidebar {
      display: none;
      flex-direction: column;
      gap: .75rem;
      width: 15rem;
      flex-shrink: 0;
      transition: width .2s ease;
    }
    @media (min-width: 1024px) { .sidebar { display: flex; } }
    .sidebar.is-mobile-open {
      display: flex;
      position: fixed;
      inset: .75rem;
      z-index: 40;
      width: min(15rem, calc(100vw - 1.5rem));
    }
    .sidebar.is-collapsed { width: 4.75rem; }
    .sidebar.is-collapsed .brand-text,
    .sidebar.is-collapsed .nav-link span:last-child,
    .sidebar.is-collapsed .collapse-btn span,
    .sidebar.is-collapsed .provider-text,
    .sidebar.is-collapsed .support-card { display: none; }
    .sidebar.is-collapsed .nav-link { justify-content: center; }

    .sidebar-panel { padding: 1.25rem; }
    .brand-row { display: flex; align-items: center; gap: .625rem; }
    .brand-icon {
      display: grid;
      place-items: center;
      width: 2.25rem;
      height: 2.25rem;
      flex-shrink: 0;
      border-radius: var(--radius-2xl);
      background: linear-gradient(to bottom right, var(--brand), var(--sky));
      color: var(--brand-foreground);
      box-shadow: 0 4px 10px rgba(0,0,0,.15);
    }
    .brand-icon span { font-family: var(--font-display); font-size: .8125rem; font-weight: 700; }
    .brand-text p:first-child { font-family: var(--font-display); font-size: .8125rem; font-weight: 700; line-height: 1.2; }
    .brand-text p:last-child { margin-top: .125rem; font-size: .6875rem; color: color-mix(in oklab, var(--ink) 50%, transparent); }

    .nav { display: flex; flex-direction: column; gap: .25rem; margin-top: 1.25rem; font-size: .875rem; }
    .nav-link {
      display: flex;
      align-items: center;
      gap: .75rem;
      width: 100%;
      padding: .625rem .75rem;
      border-radius: var(--radius-2xl);
      color: color-mix(in oklab, var(--ink) 70%, transparent);
      text-align: start;
      transition: background-color .15s, color .15s;
    }
    .nav-link:hover { background: color-mix(in oklab, var(--ink) 5%, transparent); }
    .nav-link.is-active { background: color-mix(in oklab, var(--brand) 15%, transparent); color: var(--brand); font-weight: 600; }

    .collapse-btn {
      display: flex;
      align-items: center;
      gap: .75rem;
      width: 100%;
      margin-top: 1rem;
      padding: .5rem .75rem;
      border-radius: var(--radius-2xl);
      font-size: .75rem;
      font-weight: 600;
      color: color-mix(in oklab, var(--ink) 50%, transparent);
    }
    .collapse-btn:hover { background: color-mix(in oklab, var(--ink) 5%, transparent); color: var(--ink); }
    .sidebar.is-collapsed .collapse-btn .icon { transform: rotate(180deg); }

    .provider-card { display: flex; align-items: center; gap: .625rem; padding: 1rem; }
    .status-dot { width: .625rem; height: .625rem; flex-shrink: 0; border-radius: 999px; background: var(--mint); box-shadow: 0 0 10px var(--mint); }
    .provider-text p:first-child { font-size: .75rem; font-weight: 600; }
    .provider-text p:last-child { font-size: .75rem; color: color-mix(in oklab, var(--ink) 60%, transparent); }

    .support-card { display: flex; flex-direction: column; gap: .75rem; padding: 1rem; }
    .support-label { font-size: .6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; color: color-mix(in oklab, var(--ink) 40%, transparent); }
    .support-link { display: flex; align-items: center; gap: .625rem; padding: .625rem .75rem; border-radius: var(--radius-2xl); font-size: .75rem; font-weight: 600; }
    .support-link--coffee { background: color-mix(in oklab, var(--sun) 15%, transparent); color: var(--sun); }
    .support-link--coffee:hover { background: color-mix(in oklab, var(--sun) 25%, transparent); }
    .support-link--github { background: color-mix(in oklab, var(--ink) 10%, transparent); color: color-mix(in oklab, var(--ink) 80%, transparent); }
    .support-link--github:hover { background: color-mix(in oklab, var(--ink) 15%, transparent); color: var(--ink); }

    .sidebar-backdrop { display: none; }
    .sidebar-backdrop.is-visible { display: block; position: fixed; inset: 0; z-index: 30; background: rgba(0,0,0,.4); }

    .main { min-width: 0; flex: 1; display: flex; flex-direction: column; gap: 1rem; }
    .topbar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .75rem 1.25rem; position: relative; z-index: 10; }
    .topbar-left { display: flex; align-items: center; gap: .75rem; min-width: 0; }
    .menu-btn { display: grid; place-items: center; width: 2.25rem; height: 2.25rem; border-radius: var(--radius-2xl); background: color-mix(in oklab, var(--panel) 70%, transparent); color: color-mix(in oklab, var(--ink) 70%, transparent); }
    @media (min-width: 1024px) { .menu-btn { display: none; } }
    .page-title { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: var(--font-display); font-size: 1.125rem; font-weight: 700; }
    .sync-badge { display: none; align-items: center; gap: .375rem; padding: .25rem .625rem; border-radius: 999px; font-size: .6875rem; font-weight: 600; background: color-mix(in oklab, var(--mint) 20%, transparent); color: var(--mint-strong); }
    .sync-badge .status-dot { width: .375rem; height: .375rem; box-shadow: none; }
    @media (min-width: 640px) { .sync-badge { display: inline-flex; } }
    .topbar-right { display: flex; align-items: center; gap: .5rem; position: relative; }
    .pill-btn, .icon-circle-btn { display: flex; align-items: center; justify-content: center; border: 1px solid color-mix(in oklab, var(--panel) 70%, transparent); background: color-mix(in oklab, var(--panel) 70%, transparent); color: color-mix(in oklab, var(--ink) 70%, transparent); box-shadow: 0 1px 2px rgba(0,0,0,.05); }
    .pill-btn { gap: .375rem; height: 2.25rem; padding: 0 .75rem; border-radius: 999px; }
    .icon-circle-btn { width: 2.25rem; height: 2.25rem; border-radius: 999px; }
    .pill-btn:hover, .icon-circle-btn:hover { color: var(--ink); }
    .icon-circle-btn .icon--moon { display: none; }
    html.dark .icon-circle-btn .icon--sun { display: none; }
    html.dark .icon-circle-btn .icon--moon { display: block; }
    .avatar { position: relative; display: grid; place-items: center; width: 2.25rem; height: 2.25rem; flex-shrink: 0; border-radius: 999px; background: linear-gradient(to bottom right, var(--coral), var(--sun)); color: var(--brand-foreground); box-shadow: 0 4px 10px rgba(0,0,0,.15); }
    .avatar > span:first-child { font-size: .75rem; font-weight: 700; }
    .avatar-status { position: absolute; right: -1px; bottom: -1px; width: .625rem; height: .625rem; border-radius: 999px; background: var(--mint); box-shadow: 0 0 0 2px var(--panel); }
    .language-control { position: relative; }
    .lang-menu { position: absolute; top: calc(100% + .5rem); right: 0; z-index: 200; min-width: 12rem; max-height: 22rem; overflow-y: auto; padding: .375rem; display: none; flex-direction: column; gap: .125rem; }
    .lang-menu.is-open { display: flex; }
 
    
    .lang-menu button { width: 100%; text-align: start; padding: .5rem .625rem; border-radius: var(--radius-lg); font-size: .8125rem; font-weight: 500; }
    .lang-menu button:hover { background: color-mix(in oklab, var(--ink) 6%, transparent); }
    .lang-menu button.is-active { color: var(--brand); font-weight: 700; }

    .content { min-width: 0; }
    .dashboard-page { display: flex; flex-direction: column; gap: 1rem; }
    .page-intro { padding: 1.25rem; }
    .page-intro-title { font-family: var(--font-display); font-size: 1rem; font-weight: 700; }
    .page-intro-sub { margin-top: .25rem; font-size: .75rem; color: color-mix(in oklab, var(--ink) 60%, transparent); }
    .page-hero-row { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: .75rem; }

    /* Temporary frontend-only Unused Keys page. */
    .coming-soon-card { min-height: 320px; padding: 2.25rem 1.25rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .75rem; text-align: center; }
    .coming-soon-icon { display: grid; place-items: center; width: 3.5rem; height: 3.5rem; border-radius: 1.25rem; background: color-mix(in oklab, var(--brand) 15%, transparent); color: var(--brand); }
    .coming-soon-title { font-family: var(--font-display); font-size: 1.1rem; font-weight: 700; }
    .coming-soon-text { max-width: 34rem; font-size: .78rem; color: color-mix(in oklab, var(--ink) 58%, transparent); }
    .coming-soon-badge { display: inline-flex; align-items: center; gap: .375rem; margin-top: .25rem; padding: .35rem .7rem; border-radius: 999px; background: color-mix(in oklab, var(--sun) 18%, transparent); color: var(--sun-strong); font-size: .7rem; font-weight: 700; }

    /* Existing-page compatibility helpers */
    .modal-backdrop { position: fixed; inset: 0; z-index: 70; display: grid; place-items: center; padding: 20px; background: rgba(6,8,12,.72); backdrop-filter: blur(3px); }
    .modal-box { width: 100%; max-width: 480px; border: 1px solid color-mix(in oklab,var(--panel) 34%, transparent); border-radius: 16px; background: color-mix(in oklab,var(--background) 88%, var(--panel)); box-shadow: 0 30px 80px rgba(0,0,0,.6); overflow: hidden; }
    @keyframes tin { from { opacity: 0; transform: translateY(10px) scale(.97); } to { opacity: 1; transform: none; } }
    @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .35; } }

    @media (max-width: 639px) {
      .shell { padding: .75rem; }
      .topbar { padding: .75rem 1rem; }
      .topbar-right { gap: .375rem; }
      .avatar { display: none; }
    }


    /* ============================================================
       Shared page styles for redesigned Laravel views
       ============================================================ */
    .content { display:flex; flex-direction:column; gap:1rem; }
    .page-stack { display:flex; flex-direction:column; gap:1rem; }
    .page-hero-row { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:.75rem; }
    .page-intro { padding:1.25rem; }
    .page-intro-title { font-family:var(--font-display); font-size:1rem; font-weight:700; }
    .page-intro-sub { margin-top:.25rem; font-size:.75rem; color:color-mix(in oklab,var(--ink) 60%,transparent); }
    .page-message { margin-top:.75rem; padding:.625rem .75rem; border-radius:var(--radius-2xl); font-size:.75rem; font-weight:600; background:color-mix(in oklab,var(--panel) 60%,transparent); color:color-mix(in oklab,var(--ink) 70%,transparent); }
    .page-message--ok { background:color-mix(in oklab,var(--mint) 15%,transparent); color:var(--mint-strong); }
    .page-message--err { background:color-mix(in oklab,var(--coral) 15%,transparent); color:var(--coral-strong); }
    .page-message--warn { background:color-mix(in oklab,var(--sun) 15%,transparent); color:var(--sun-strong); }

    .empty-state { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:3rem 1.5rem; text-align:center; min-height:220px; }
    .empty-state-icon { display:grid; place-items:center; width:3rem; height:3rem; margin-bottom:.75rem; border-radius:1rem; background:color-mix(in oklab,var(--brand) 12%,transparent); color:var(--brand); font-size:1.25rem; }
    .empty-state-title { font-family:var(--font-display); font-size:.9rem; font-weight:700; }
    .empty-state-text { max-width:34rem; margin-top:.35rem; font-size:.75rem; color:color-mix(in oklab,var(--ink) 55%,transparent); }

    /* Languages */
    .language-grid { display:grid; gap:1rem; grid-template-columns:1fr; }
    @media(min-width:768px) { .language-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(min-width:1280px) { .language-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    .language-card { padding:1.25rem; }
    .language-card.is-muted { opacity:.6; pointer-events:none; }
    .language-card-head { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
    .language-identity { display:flex; align-items:center; gap:.75rem; min-width:0; }
    .language-flag { font-size:1.5rem; line-height:1; }
    .language-name { font-family:var(--font-display); font-size:.875rem; font-weight:700; }
    .language-code { margin-top:.125rem; font-size:.6875rem; text-transform:uppercase; letter-spacing:.05em; color:color-mix(in oklab,var(--ink) 45%,transparent); }
    .language-stats { margin-top:1rem; }
    .language-stat-row { display:flex; align-items:center; justify-content:space-between; gap:.5rem; font-size:.75rem; }
    .language-progress { margin-top:.5rem; }
    .language-card-badges { display:flex; flex-wrap:wrap; gap:.5rem; margin-top:.75rem; }
    .language-card-action { width:100%; margin-top:1rem; }
    .rtl-badge { background:color-mix(in oklab,var(--sky) 25%,transparent); color:var(--sky-strong); }
    .switch { position:relative; width:2.25rem; height:1.25rem; flex-shrink:0; padding:0; border-radius:999px; background:color-mix(in oklab,var(--ink) 20%,transparent); transition:background-color .15s ease; }
    .switch::after { content:""; position:absolute; top:2px; left:2px; width:1rem; height:1rem; border-radius:999px; background:var(--background); box-shadow:0 1px 3px rgba(0,0,0,.25); transition:transform .15s ease; }
    .switch.is-on { background:var(--brand); }
    .switch.is-on::after { transform:translateX(1rem); }

    /* Locked keys */
    .locked-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; margin-top:1rem; }
    .search-wrap { position:relative; min-width:220px; flex:1; }
    .search-icon { position:absolute; top:50%; left:.75rem; width:.875rem; height:.875rem; transform:translateY(-50%); color:color-mix(in oklab,var(--ink) 40%,transparent); pointer-events:none; }
    [dir="rtl"] .search-icon { left:auto; right:.75rem; }
    .text-input,.select-input { width:100%; height:2.25rem; padding:0 .75rem; border:1px solid var(--border); border-radius:.5rem; background:color-mix(in oklab,var(--panel) 70%,transparent); color:var(--ink); outline:none; }
    .text-input { padding-left:2.25rem; }
    [dir="rtl"] .text-input { padding-left:.75rem; padding-right:2.25rem; }
    .select-input { min-width:12rem; }
    .text-input:focus,.select-input:focus { border-color:var(--brand); box-shadow:0 0 0 1px var(--brand); }
    .locked-table-panel,.backup-table-panel { overflow:hidden; }
    .locked-table-scroll,.table-scroll { overflow-x:auto; }
    .locked-table { width:100%; min-width:860px; border-collapse:collapse; font-size:.75rem; }
    .locked-table th { padding:.75rem; text-align:left; font-weight:600; color:color-mix(in oklab,var(--ink) 55%,transparent); background:color-mix(in oklab,var(--panel) 50%,transparent); }
    .locked-table td { padding:.75rem; border-top:1px solid color-mix(in oklab,var(--panel) 60%,transparent); vertical-align:top; }
    .locked-table tbody tr:hover { background:color-mix(in oklab,var(--panel) 40%,transparent); }
    .table-check { display:grid; place-items:center; width:1rem; height:1rem; padding:0; border:1px solid var(--brand); border-radius:.25rem; background:transparent; color:var(--brand-foreground); }
    .table-check.is-checked { background:var(--brand); }
    .table-check.is-checked::after { content:"✓"; font-size:.625rem; font-weight:800; }
    .key-cell { font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:.6875rem; font-weight:600; direction:ltr; }
    .value-cell { max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:color-mix(in oklab,var(--ink) 75%,transparent); }
    .locked-by { display:flex; flex-direction:column; gap:.125rem; font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace; font-size:.6875rem; font-weight:600; }
    .locked-by-date { font-size:.6875rem; color:color-mix(in oklab,var(--ink) 50%,transparent); direction:ltr; }
    .reason-cell { color:color-mix(in oklab,var(--ink) 60%,transparent); }
    .locked-action-cell { text-align:right; white-space:nowrap; }
    .locked-action-wrap { display:flex; align-items:center; justify-content:flex-end; gap:.5rem; }
    .locked-badge { background:color-mix(in oklab,var(--coral) 25%,transparent); color:var(--coral-strong); }

    /* History */
    .history-list { display:flex; flex-direction:column; gap:.75rem; }
    .history-item { overflow:hidden; }
    .history-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); align-items:center; gap:.75rem; width:100%; padding:1rem; text-align:start; font-size:.75rem; }
    .history-summary .summary-value { color:color-mix(in oklab,var(--ink) 70%,transparent); }
    .history-summary .summary-provider { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:color-mix(in oklab,var(--ink) 70%,transparent); }
    .history-summary .summary-cost { display:flex; align-items:center; justify-content:space-between; gap:.5rem; color:color-mix(in oklab,var(--ink) 70%,transparent); }
    .summary-date { color:color-mix(in oklab,var(--ink) 65%,transparent); }
    .history-chevron { display:inline-grid; place-items:center; width:1.5rem; height:1.5rem; border-radius:999px; color:color-mix(in oklab,var(--ink) 50%,transparent); transition:transform .15s ease; }
    .history-chevron.is-open { transform:rotate(180deg); }
    .history-details { border-top:1px solid color-mix(in oklab,var(--panel) 60%,transparent); background:color-mix(in oklab,var(--panel) 40%,transparent); padding:1rem; }
    .history-details-title { font-size:.6875rem; font-weight:600; color:color-mix(in oklab,var(--ink) 55%,transparent); }
    .changed-list { margin-top:.5rem; display:flex; flex-direction:column; gap:.375rem; }
    .changed-item { display:flex; flex-wrap:wrap; align-items:center; gap:.5rem; padding:.5rem .75rem; border-radius:var(--radius-2xl); background:color-mix(in oklab,var(--panel) 70%,transparent); font-size:.6875rem; }
    .changed-lang { padding:.125rem .5rem; border-radius:999px; background:color-mix(in oklab,var(--ink) 5%,transparent); text-transform:uppercase; }
    .changed-key { font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,monospace; font-weight:600; direction:ltr; }
    .changed-old { color:color-mix(in oklab,var(--ink) 45%,transparent); }
    .changed-arrow { color:color-mix(in oklab,var(--ink) 35%,transparent); }
    .changed-new { font-weight:600; color:var(--mint-strong); }
    .run-footer { padding:.75rem 1rem; border-top:1px solid color-mix(in oklab,var(--panel) 60%,transparent); font-size:.6875rem; color:color-mix(in oklab,var(--ink) 50%,transparent); }
    @media(min-width:900px) { .history-summary { grid-template-columns:1.35fr .8fr 1fr .9fr 1fr 1.3fr .9fr; } .history-summary .summary-date { white-space:nowrap; } }

    /* Backups */
    .backup-header { display:flex; flex-wrap:wrap; align-items:flex-start; justify-content:space-between; gap:.75rem; padding:1.25rem; }
    .section-title { font-family:var(--font-display); font-size:1rem; font-weight:700; }
    .section-subtitle { margin-top:.25rem; font-size:.75rem; color:color-mix(in oklab,var(--ink) 60%,transparent); }
    .backup-table { width:100%; min-width:720px; border-collapse:collapse; font-size:.75rem; }
    .backup-table thead { background:color-mix(in oklab,var(--panel) 50%,transparent); }
    .backup-table th { padding:.75rem; text-align:left; font-weight:600; color:color-mix(in oklab,var(--ink) 55%,transparent); }
    .backup-table td { padding:.75rem; border-top:1px solid color-mix(in oklab,var(--panel) 60%,transparent); }
    .backup-table tbody tr:hover { background:color-mix(in oklab,var(--panel) 40%,transparent); }
    .backup-actions { display:flex; align-items:center; justify-content:flex-end; gap:.5rem; }
    .table-btn { display:inline-flex; align-items:center; gap:.375rem; padding:.375rem .625rem; border-radius:999px; background:color-mix(in oklab,var(--panel) 70%,transparent); color:color-mix(in oklab,var(--ink) 70%,transparent); font-size:.6875rem; font-weight:600; }
    .table-btn:hover { background:var(--panel); color:var(--ink); }
    .settings-grid { display:grid; gap:1rem; margin-top:1rem; }
    @media(min-width:768px) { .settings-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    .setting-block { display:flex; flex-direction:column; gap:.375rem; }
    .setting-label { font-size:.875rem; font-weight:600; }
    .setting-input { width:8rem; height:2.25rem; padding:0 .75rem; border:1px solid var(--border); border-radius:.5rem; background:color-mix(in oklab,var(--panel) 70%,transparent); color:var(--ink); outline:none; }
    .setting-input:focus { border-color:var(--brand); box-shadow:0 0 0 1px var(--brand); }
    .setting-help { font-size:.6875rem; color:color-mix(in oklab,var(--ink) 50%,transparent); }
    .toggle-setting { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; padding:1rem; border-radius:var(--radius-2xl); background:color-mix(in oklab,var(--panel) 55%,transparent); }
    .toggle-text { min-width:0; }
    .toggle-title { font-size:.75rem; font-weight:600; }
    .toggle-description { margin-top:.25rem; font-size:.6875rem; color:color-mix(in oklab,var(--ink) 50%,transparent); }
    .toggle { position:relative; width:2.25rem; height:1.25rem; flex-shrink:0; padding:0; border-radius:999px; background:color-mix(in oklab,var(--ink) 20%,transparent); transition:background-color .15s ease; }
    .toggle::after { content:""; position:absolute; top:2px; left:2px; width:1rem; height:1rem; border-radius:999px; background:var(--background); box-shadow:0 1px 3px rgba(0,0,0,.25); transition:transform .15s; }
    .toggle.is-on { background:var(--brand); }
    .toggle.is-on::after { transform:translateX(1rem); }

    /* Backup settings panel */
    .settings-panel { padding:1.25rem; }
    .settings-panel .settings-section-head { margin-bottom:0; }

    /* Settings */
    .settings-layout { display:grid; gap:1rem; grid-template-columns:1fr; }
    @media(min-width:1024px) { .settings-layout { grid-template-columns:1.2fr 1fr; } }
    .settings-section { padding:1.25rem; }
    .settings-section-head { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .settings-section-title { font-family:var(--font-display); font-size:.875rem; font-weight:700; }
    .settings-section-sub { margin-top:.25rem; font-size:.6875rem; color:color-mix(in oklab,var(--ink) 50%,transparent); }
    .provider-grid { display:grid; gap:.5rem; margin-top:1rem; grid-template-columns:1fr; }
    @media(min-width:640px) { .provider-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    @media(min-width:1280px) { .provider-grid { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    .provider-option { padding:.75rem; border-radius:var(--radius-2xl); text-align:left; background:color-mix(in oklab,var(--panel) 60%,transparent); transition:background-color .15s,box-shadow .15s; }
    [dir="rtl"] .provider-option { text-align:right; }
    .provider-option:hover { background:var(--panel); }
    .provider-option.is-selected { background:color-mix(in oklab,var(--brand) 15%,transparent); box-shadow:inset 0 0 0 2px color-mix(in oklab,var(--brand) 40%,transparent); }
    .provider-name { font-size:.875rem; font-weight:600; }
    .provider-meta { margin-top:.125rem; font-size:.6875rem; color:color-mix(in oklab,var(--ink) 55%,transparent); }
    .form-grid { display:grid; gap:.75rem; margin-top:1rem; }
    @media(min-width:640px) { .form-grid.two { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    .form-field { display:flex; flex-direction:column; gap:.375rem; }
    .form-label { font-size:.75rem; font-weight:600; }
    .form-help { font-size:.6875rem; color:color-mix(in oklab,var(--ink) 50%,transparent); }
    .form-input,.form-select,.form-textarea { width:100%; border:1px solid var(--border); border-radius:.5rem; background:color-mix(in oklab,var(--panel) 70%,transparent); color:var(--ink); outline:none; }
    .form-input,.form-select { height:2.25rem; padding:0 .75rem; }
    .form-textarea { min-height:6rem; padding:.625rem .75rem; resize:vertical; }
    .form-input:focus,.form-select:focus,.form-textarea:focus { border-color:var(--brand); box-shadow:0 0 0 1px var(--brand); }
    .provider-status { display:flex; align-items:center; flex-wrap:wrap; gap:.5rem; margin-top:1rem; }
    .status-label { font-size:.6875rem; color:color-mix(in oklab,var(--ink) 50%,transparent); }
    .settings-actions { display:flex; justify-content:flex-end; gap:.5rem; margin-top:1rem; }

    .tabular-nums { font-variant-numeric:tabular-nums; }

    @media(max-width:639px) {
      .page-intro,.backup-header,.settings-section,.settings-panel { padding:1rem; }
      .backup-actions { justify-content:flex-start; }
      .locked-toolbar { align-items:stretch; }
      .select-input { min-width:0; }
      .history-summary { grid-template-columns:1fr 1fr; }
    }

  </style>
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

  <script>
    function dashboard() {
      return {
        page: @json(request()->query('__unused_keys') ? 'unused' : (request()->segment(2) ?: 'overview')),
        ar: {{ $_isRtl ? 'true' : 'false' }},
        expanded: true,
        toasts: [],
        restoreOpen: false,
        restoreTarget: '',
        lockOpen: false,
        lockLang: @json($cfgLangsJs[0]['code'] ?? 'ar'),
        lockKey: '',
        lockReason: '',
        addOpen: false,
        addQuery: '',
        addingLang: '',
        activeProvider: @json($cfgDriver),
        providerDisplay: @json($_providerDisplay),
        configuredLangs: @json($cfgLangsJs),
        sourceLang: @json($cfgSource),
        languageCatalog: @json($_languageCatalog),
        t: @json($_trans),

        async init() {
          try {
            this.expanded = localStorage.getItem('lat-sidebar-collapsed') !== '1';
          } catch (e) {}
          this.startBackgroundPoller();
        },

        _tBak() {
          const en = {
            overview: 'Overview', languages: 'Languages', locked: 'Locked Keys', history: 'History', unused: 'Unused Keys', backups: 'Backups', settings: 'Settings', collapse: 'Collapse',
            quickActions: 'Quick actions', scan: 'Scan', translate: 'Translate', dryRun: 'Dry run', activeProvider: 'Active provider', lastSync: 'Last sync',
            coverageByLang: 'Coverage by language', addLanguage: 'Add language', addLanguageHint: 'Select a language to add', searchLanguage: 'Search languages...',
            language: 'Language', key: 'Key', value: 'Value', lockedBy: 'Locked by', reason: 'Reason', unlock: 'Unlock', lockKeyTitle: 'Lock a Key',
            reasonOptional: 'Reason (optional)', reasonPlaceholder: 'e.g. Client preferred term', lockKeyConfirm: 'Lock Key', cancel: 'Cancel',
            restoreTitle: 'Restore Backup', restoreWarn: 'This will replace all current language files.', restoreConfirm: 'Yes, restore', backupSettings: 'Backup settings',
            keepLast: 'Keep last', keepLastHint: 'Older backups are deleted automatically', autoBackup: 'Auto-backup', autoBackupHint: 'Before every translation run',
            createBackup: 'Create backup', download: 'Download', restore: 'Restore', emptyBackupsT: 'No backups yet', emptyBackupsB: 'A backup is created automatically before every translation run.',
            save: 'Save changes', reset: 'Reset to defaults', saving: 'Saving...', files: 'files', keys: 'keys'
          };
          const ar = {
            overview: 'نظرة عامة', languages: 'اللغات', locked: 'المفاتيح المقفلة', history: 'السجل', unused: 'المفاتيح غير المستخدمة', backups: 'النسخ الاحتياطية', settings: 'الإعدادات', collapse: 'طيّ',
            quickActions: 'إجراءات سريعة', scan: 'مسح', translate: 'ترجمة', dryRun: 'تجريبي', activeProvider: 'المزوّد النشط', lastSync: 'آخر مزامنة',
            coverageByLang: 'التغطية حسب اللغة', addLanguage: 'إضافة لغة', addLanguageHint: 'اختر لغة لإضافتها', searchLanguage: 'ابحث عن لغة...',
            language: 'اللغة', key: 'المفتاح', value: 'القيمة', lockedBy: 'قفل بواسطة', reason: 'السبب', unlock: 'فتح', lockKeyTitle: 'قفل مفتاح',
            reasonOptional: 'السبب (اختياري)', reasonPlaceholder: 'مثال: مصطلح العميل', lockKeyConfirm: 'تأكيد القفل', cancel: 'إلغاء',
            restoreTitle: 'استعادة نسخة', restoreWarn: 'سيؤدي هذا إلى استبدال جميع ملفات اللغة.', restoreConfirm: 'تأكيد الاستعادة', backupSettings: 'إعدادات النسخ الاحتياطي',
            keepLast: 'الاحتفاظ بآخر', keepLastHint: 'يتم حذف النسخ القديمة تلقائياً', autoBackup: 'نسخ تلقائي', autoBackupHint: 'قبل كل عملية ترجمة',
            createBackup: 'إنشاء نسخة', download: 'تحميل', restore: 'استعادة', emptyBackupsT: 'لا توجد نسخ احتياطية', emptyBackupsB: 'تُنشأ نسخة تلقائياً قبل كل ترجمة.',
            save: 'حفظ التغييرات', reset: 'إعادة الضبط', saving: 'جاري الحفظ...', files: 'ملفات', keys: 'مفتاح'
          };
          return this.ar ? ar : en;
        },

        get pageTitle() {
          const e = { overview: 'Overview', languages: 'Languages', locked: 'Locked Keys', history: 'History', unused: 'Unused Keys', backups: 'Backups', settings: 'Settings' };
          const a = { overview: 'نظرة عامة', languages: 'اللغات', locked: 'المفاتيح المقفلة', history: 'السجل', unused: 'المفاتيح غير المستخدمة', backups: 'النسخ الاحتياطية', settings: 'الإعدادات' };
          return (this.ar ? a : e)[this.page] || '';
        },

        get pageSub() {
          const e = {
            overview: 'Translation state of your application',
            languages: 'Locales configured in config/ai-translator.php',
            locked: 'Keys protected from being overwritten',
            history: 'Every sync run, with cost and diff',
            unused: 'Translation keys that are not currently referenced',
            backups: 'Snapshots of your language files',
            settings: 'Provider, models and behaviour'
          };
          const a = {
            overview: 'حالة الترجمة في تطبيقك',
            languages: 'اللغات المُهيّأة',
            locked: 'المفاتيح المحمية',
            history: 'سجلات المزامنة',
            unused: 'مفاتيح الترجمة غير المستخدمة',
            backups: 'لقطات ملفات اللغة',
            settings: 'المزوّد والنماذج'
          };
          return (this.ar ? a : e)[this.page] || '';
        },

        get providerLabel() {
          return this.providerDisplay || this.activeProvider;
        },

        get filteredAddOptions() {
          const configuredCodes = this.configuredLangs.map(l => l.code);
          const available = (this.languageCatalog || []).filter(o => o.code !== this.sourceLang && !configuredCodes.includes(o.code));
          if (!this.addQuery) return available;
          const q = this.addQuery.toLowerCase();
          return available.filter(o =>
            o.code.toLowerCase().includes(q) ||
            o.name.toLowerCase().includes(q) ||
            o.native.toLowerCase().includes(q)
          );
        },

        toggleSidebar() {
          this.expanded = !this.expanded;
          try { localStorage.setItem('lat-sidebar-collapsed', this.expanded ? '0' : '1'); } catch (e) {}
        },

        go(p) {
          const urls = {
            overview: @json(route('ai-translator.overview')),
            languages: @json(route('ai-translator.languages')),
            locked: @json(route('ai-translator.locked')),
            history: @json(route('ai-translator.history')),
            backups: @json(route('ai-translator.backups')),
            settings: @json(route('ai-translator.settings')),
            unused: @json(route('ai-translator.overview', ['__unused_keys' => 1])),
          };
          if (urls[p]) window.location.href = urls[p];
        },

        async api(endpoint, body = {}) {
          const token = document.querySelector('meta[name="csrf-token"]')?.content;
          const r = await fetch(@json(url('') . '/' . trim(config('ai-translator.dashboard.path', 'ai-translator'), '/')) + '/api/' + endpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            body: JSON.stringify(body),
          });
          return r.json();
        },

        async confirmRestore() {
          try {
            const r = await this.api('backups/restore', { timestamp: this.restoreTarget });
            this.restoreOpen = false;
            this.toast(r.success ? (this.ar ? 'تمت الاستعادة' : 'Backup restored') : (r.message || 'Restore failed'), '', r.success ? 'ok' : 'err');
          } catch (e) {
            this.toast(this.ar ? 'فشلت الاستعادة' : 'Restore failed', e.message, 'err');
          }
        },

        async confirmLock() {
          if (!this.lockKey.trim()) return;
          const key = this.lockKey.trim();
          try {
            const r = await this.api('lock', { lang: this.lockLang, key, reason: this.lockReason.trim() });
            this.lockOpen = false;
            this.lockKey = '';
            this.lockReason = '';
            if (r.success) {
              this.toast(this.ar ? 'تم القفل' : 'Key locked', this.lockLang + ' / ' + key);
              setTimeout(() => window.location.reload(), 900);
            } else {
              this.toast(r.message || 'Failed to lock', '', 'err');
            }
          } catch (e) {
            this.toast(this.ar ? 'فشل القفل' : 'Lock failed', e.message, 'err');
          }
        },

        async pickLanguage(opt) {
          this.addOpen = false;
          this.addingLang = opt.code;

          try {
            const r = await this.api('languages/add', { locale: opt.code });
            if (!r.success) {
              this.toast(r.message || 'Failed to add language', '', 'err');
              this.addingLang = '';
              return;
            }

            let prevCompletedAt = null;
            try {
              const pre = await fetch(@json(url('') . '/' . trim(config('ai-translator.dashboard.path', 'ai-translator'), '/')) + '/api/translate/status', { headers: { 'Accept': 'application/json' } }).then(r => r.json());
              prevCompletedAt = pre.data?.last_completed_at ?? null;
            } catch (e) {}

            fetch(@json(url('') . '/' . trim(config('ai-translator.dashboard.path', 'ai-translator'), '/')) + '/api/translate', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              },
              body: JSON.stringify({ lang: opt.code, force: true }),
            }).catch(() => {});

            localStorage.setItem('ai_translator_pending', JSON.stringify({
              lang: opt.code,
              name: opt.name,
              started: Date.now(),
              prevCompletedAt,
            }));

            this.toast(
              this.ar ? 'جاري الترجمة في الخلفية' : opt.name + ' is being translated in the background',
              this.ar ? 'يمكنك التنقل بحرية' : 'You can navigate freely'
            );

            setTimeout(() => { window.location.href = @json(route('ai-translator.languages')); }, 800);
          } catch (e) {
            this.toast('Error: ' + e.message, '', 'err');
          }

          this.addingLang = '';
        },

        startBackgroundPoller() {
          const pending = localStorage.getItem('ai_translator_pending');
          if (!pending) return;

          try {
            const p = JSON.parse(pending);
            const name = p.name || p.lang;
            const prevCompletedAt = p.prevCompletedAt ?? null;
            const startTime = p.started;
            const self = this;

            this.toast(
              this.ar ? 'جاري الترجمة في الخلفية' : 'Translating ' + name + ' in background...',
              this.ar ? 'سيتم الإشعار عند الانتهاء' : 'You will be notified when done'
            );

            const poll = setInterval(async () => {
              try {
                const s = await fetch(@json(url('') . '/' . trim(config('ai-translator.dashboard.path', 'ai-translator'), '/')) + '/api/translate/status', { headers: { 'Accept': 'application/json' } }).then(r => r.json());
                if (!s.success) return;

                const data = s.data;
                const done = !data.running && data.last_completed_at !== null && data.last_completed_at !== prevCompletedAt;
                if (done) {
                  clearInterval(poll);
                  localStorage.removeItem('ai_translator_pending');
                  const success = data.last_result?.success ?? true;

                  if (success) {
                    self.toast(self.ar ? 'اكتملت الترجمة' : name + ' translation complete', self.ar ? 'تم تحديث الإحصائيات' : 'Stats updated');
                  } else {
                    self.toast(self.ar ? 'فشلت الترجمة' : name + ' translation failed', '', 'err');
                  }

                  if (typeof self.refreshStats === 'function') self.refreshStats();
                }

                if (Date.now() - startTime > 900000) {
                  clearInterval(poll);
                  localStorage.removeItem('ai_translator_pending');
                }
              } catch (e) {}
            }, 3000);
          } catch (e) {
            localStorage.removeItem('ai_translator_pending');
          }
        },

        toast(title, body = '', kind = 'ok') {
          const id = Date.now() + Math.random();
          this.toasts.push({ id, title, body, kind });
          setTimeout(() => {
            this.toasts = this.toasts.filter(t => t.id !== id);
          }, 3500);
        },
      };
    }

    function langSelector() {
      const allLangs = @json($_dashboardLangs);

      return {
        open: false,
        generating: false,
        generatingCode: '',
        currentCode: @json($_dashLang),
        allLangs,
        open: false,
menuStyle: '',

toggleLanguage(event) {
    this.open = !this.open;

    if (this.open) {
        this.$nextTick(() => {
            this.positionMenu(event.currentTarget);
        });
    }
},

positionMenu(button) {
    const rect = button.getBoundingClientRect();

    const menuWidth = 220;
    const gap = 8;
    const margin = 8;

    let left = rect.right - menuWidth;

    // Keep menu inside viewport
    left = Math.max(margin, left);
    left = Math.min(left, window.innerWidth - menuWidth - margin);

    this.menuStyle =
        `top:${rect.bottom + gap}px;left:${left}px;width:${menuWidth}px;`;
},

        get currentLabel() {
          const lang = this.allLangs.find(l => l.code === this.currentCode);
          return lang ? lang.native + ' (' + lang.code.toUpperCase() + ')' : 'EN';
        },

        async selectLang(lang) {
          this.open = false;
          if (lang.code === this.currentCode) return;

          if (lang.preTranslated) {
            this.setCookieAndReload(lang.code);
            return;
          }

          this.generating = true;
          this.generatingCode = lang.code;

          try {
            const r = await fetch(@json(url('ai-translator/api/dashboard-lang')), {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              },
              body: JSON.stringify({ locale: lang.code }),
            }).then(r => r.json());

            if (r.success) {
              this.setCookieAndReload(lang.code);
            } else {
              alert('Could not generate translations: ' + (r.message || 'Unknown error'));
            }
          } catch (e) {
            alert('Network error: ' + e.message);
          }

          this.generating = false;
          this.generatingCode = '';
        },

        setCookieAndReload(code) {
          window.location.href = @json(url('ai-translator/set-lang')) + '/' + code;
        },
      };
    }

    document.addEventListener('DOMContentLoaded', function () {
      const toggle = document.querySelector('[data-theme-toggle]');
      if (!toggle) return;
      const updateLabel = () => {
        const dark = document.documentElement.classList.contains('dark');
        toggle.setAttribute('aria-label', dark ? 'Switch to light' : 'Switch to dark');
        toggle.setAttribute('title', dark ? 'Switch to light' : 'Switch to dark');
      };
      updateLabel();
      toggle.addEventListener('click', function () {
        const dark = document.documentElement.classList.toggle('dark');
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        try { localStorage.setItem('lat-theme', dark ? 'dark' : 'light'); } catch (e) {}
        updateLabel();
      });
    });
  </script>
</body>
</html>