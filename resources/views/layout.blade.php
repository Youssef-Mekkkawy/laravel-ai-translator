@php
    $_dashLang = session('dashboard_lang', request()->cookie('dashboard_lang', 'en'));
    $_preTranslated = ['en','ar','fr','es','de','zh','ja','tr','ru','pt'];
    $_pkgPath  = app('ai-translator.package_path');
    $_langFile = $_pkgPath.'/resources/lang/'.$_dashLang.'/dashboard.php';
    $_enFile   = $_pkgPath.'/resources/lang/en/dashboard.php';
    $_trans    = file_exists($_langFile) ? include $_langFile : (file_exists($_enFile) ? include $_enFile : []);
    $_rtlLangs = ['ar', 'he', 'fa', 'ur'];
    $_isRtl    = in_array($_dashLang, $_rtlLangs);
    $_allLangs = [
        'en'=>['English','English'], 'ar'=>['Arabic','العربية'],
        'fr'=>['French','Français'], 'es'=>['Spanish','Español'],
        'de'=>['German','Deutsch'],  'zh'=>['Chinese','中文'],
        'ja'=>['Japanese','日本語'], 'tr'=>['Turkish','Türkçe'],
        'ru'=>['Russian','Русский'], 'pt'=>['Portuguese','Português'],
        'ko'=>['Korean','한국어'],   'it'=>['Italian','Italiano'],
        'nl'=>['Dutch','Nederlands'],'pl'=>['Polish','Polski'],
        'hi'=>['Hindi','हिन्दी'],   'sv'=>['Swedish','Svenska'],
        'vi'=>['Vietnamese','Tiếng Việt'], 'id'=>['Indonesian','Bahasa Indonesia'],
    ];
    $cfgLangs   = config('ai-translator.languages', []);
    $cfgSource  = config('ai-translator.default_language', 'en');
    $cfgDriver  = config('ai-translator.driver', 'ollama');
    $cfgLangsJs = array_values(array_map(
        fn($l) => ['code' => $l, 'label' => strtoupper($l)],
        array_filter($cfgLangs, fn($l) => $l !== $cfgSource)
    ));
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>AI Translator — @yield('title', 'Dashboard')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; }

html, body {
  margin: 0; padding: 0;
  height: 100%;
  background: #0B0D12;
  color: #E6E9EF;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 14px;
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}

/* App shell — fixed-height, no-scroll root */
#app-shell {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: #0B0D12;
}

/* Sidebar */
#sidebar {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  background: #0D111A;
  border-right: 1px solid #1B2130;
  overflow-y: auto;
  overflow-x: hidden;
  transition: width .2s ease;
  width: 200px;
}
#sidebar.collapsed { width: 56px; }
[dir="rtl"] #sidebar { border-right: none; border-left: 1px solid #1B2130; }

/* Main column */
#main-col {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Sticky top header */
#top-bar {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
  padding: 0 24px;
  min-height: 64px;
  border-bottom: 1px solid #1B2130;
  background: rgba(11,13,18,.9);
  backdrop-filter: blur(12px);
  position: sticky;
  top: 0;
  z-index: 20;
}

/* Scrollable page content */
#page-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

/* Nav buttons */
.nav-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 9px 10px;
  border-radius: 9px;
  border: none;
  cursor: pointer;
  font-size: 13px;
  font-weight: 500;
  background: transparent;
  color: #8B93A5;
  transition: background .15s, color .15s;
  white-space: nowrap;
}
.nav-btn:hover { background: rgba(110,231,183,.06); color: #E6E9EF; }
.nav-btn.active { background: rgba(110,231,183,.12); color: #6EE7B7; }

/* Pill toggle */
.pill-wrap {
  display: flex;
  padding: 3px;
  border-radius: 10px;
  background: #10141C;
  border: 1px solid #1B2130;
  gap: 2px;
}
.pill-btn {
  padding: 6px 12px;
  border-radius: 8px;
  border: none;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  background: transparent;
  color: #5C6678;
  transition: background .15s, color .15s;
}
.pill-btn.active { background: #6EE7B7; color: #062A20; }

/* Modal backdrop */
.modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 70;
  display: grid;
  place-items: center;
  padding: 20px;
  background: rgba(6,8,12,.72);
  backdrop-filter: blur(3px);
}
.modal-box {
  width: 100%;
  max-width: 480px;
  border: 1px solid #232B3B;
  border-radius: 16px;
  background: #101420;
  box-shadow: 0 30px 80px rgba(0,0,0,.6);
  overflow: hidden;
}

/* Animations */
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes tin  { from { opacity:0; transform: translateY(10px) scale(.97); } to { opacity:1; transform:none; } }
@keyframes pulse { 0%,100% { opacity:1 } 50% { opacity:.35 } }

[x-cloak] { display: none !important; }

/* RTL font override */
[dir="rtl"] { font-family: 'IBM Plex Sans Arabic', 'Space Grotesk', sans-serif; }
</style>
</head>
<body>

<div id="app-shell" x-data="dashboard()" x-cloak :dir="ar ? 'rtl' : 'ltr'"
  @open-lock-modal.window="lockOpen = true"
  @open-add-language.window="addOpen = true"
  @open-restore.window="restoreOpen = true; restoreTarget = $event.detail.timestamp"
>

  {{-- ── SIDEBAR ──────────────────────────────────────── --}}
  <div id="sidebar" :class="expanded ? '' : 'collapsed'">

    {{-- Logo --}}
    <div style="display:flex;align-items:center;gap:10px;padding:18px 14px;border-bottom:1px solid #1B2130;min-height:64px;flex-shrink:0">
      <div style="width:28px;height:28px;flex:none;border-radius:8px;background:linear-gradient(140deg,#6EE7B7,#38bdf8);display:grid;place-items:center;color:#06231a;font-weight:700;font-size:13px;font-family:'JetBrains Mono',monospace">t</div>
      <div x-show="expanded" style="overflow:hidden">
        <div style="font-weight:600;font-size:13.5px;white-space:nowrap">AI Translator</div>
        <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;color:#5C6678">v1.0.0</div>
      </div>
    </div>

    {{-- Nav --}}
    <nav style="display:flex;flex-direction:column;gap:2px;padding:12px 10px;flex:1">
      <button class="nav-btn" :class="page==='overview'  ? 'active' : ''" @click="go('overview')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
        <span x-show="expanded" x-text="t.overview"></span>
      </button>
      <button class="nav-btn" :class="page==='languages' ? 'active' : ''" @click="go('languages')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 3 2.6 15 0 18M12 3c-2.6 3-2.6 15 0 18"/></svg>
        <span x-show="expanded" x-text="t.languages"></span>
      </button>
      <button class="nav-btn" :class="page==='locked'    ? 'active' : ''" @click="go('locked')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
        <span x-show="expanded" x-text="t.locked"></span>
      </button>
      <button class="nav-btn" :class="page==='history'   ? 'active' : ''" @click="go('history')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 8v4.5l3 1.8"/></svg>
        <span x-show="expanded" x-text="t.history"></span>
      </button>
      <button class="nav-btn" :class="page==='backups'   ? 'active' : ''" @click="go('backups')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/><path d="M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg>
        <span x-show="expanded" x-text="t.backups"></span>
      </button>
      <button class="nav-btn" :class="page==='settings'  ? 'active' : ''" @click="go('settings')">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="flex:none"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.6 1.6 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.6 1.6 0 0 0-2.7 1.1V21a2 2 0 1 1-4 0v-.1A1.6 1.6 0 0 0 7.5 19.4l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.6 1.6 0 0 0 3 14.6H3a2 2 0 1 1 0-4h.1A1.6 1.6 0 0 0 4.6 7.5l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.6 1.6 0 0 0 10 3V3a2 2 0 1 1 4 0v.1a1.6 1.6 0 0 0 2.5 1.4l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.6 1.6 0 0 0 1.1 2.7H21a2 2 0 1 1 0 4h-.1a1.6 1.6 0 0 0-1.5 1z"/></svg>
        <span x-show="expanded" x-text="t.settings"></span>
      </button>
    </nav>

    {{-- Footer --}}
    <div style="padding:12px 10px;border-top:1px solid #1B2130;display:flex;flex-direction:column;gap:8px;flex-shrink:0">
      <div x-show="expanded" style="display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:9px;background:#10141C;border:1px solid #1B2130;overflow:hidden">
        <span style="width:7px;height:7px;flex:none;border-radius:50%;background:#6EE7B7;animation:pulse 2.4s infinite"></span>
        <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:#8B93A5;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" x-text="providerLabel"></span>
      </div>
      <a href="https://buymeacoffee.com/youssef.mekkawy" target="_blank"
        style="display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:9px;font-size:12px;color:#5C6678;text-decoration:none;transition:color .15s"
        onmouseover="this.style.color='#FFDD00'" onmouseout="this.style.color='#5C6678'">
        <span style="font-size:14px">☕</span>
        <span x-show="expanded" style="white-space:nowrap">Buy me a coffee</span>
      </a>
      <button @click="expanded = !expanded" class="nav-btn" style="justify-content:center">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="flex:none;transition:.2s" :style="expanded ? '' : 'transform:rotate(180deg)'"><path d="M15 18l-6-6 6-6"/></svg>
        <span x-show="expanded" x-text="t.collapse"></span>
      </button>
    </div>
  </div>

  {{-- ── MAIN COLUMN ──────────────────────────────────── --}}
  <div id="main-col">

    {{-- Top bar --}}
    <div id="top-bar">
      <div style="flex:1;min-width:0">
        <div style="font-size:16px;font-weight:600;letter-spacing:-.3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" x-text="pageTitle"></div>
        <div style="font-size:12px;color:#5C6678;white-space:nowrap" x-text="pageSub"></div>
      </div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:nowrap">
        {{-- Dashboard language selector (simple HTML select) --}}
        <div style="position:relative;display:inline-block">
          <select onchange="window.location.href='{{ url('') }}/ai-translator/set-lang/' + this.value"
            style="padding:8px 30px 8px 12px;border-radius:10px;border:1px solid #1B2130;background:#10141C;color:#E6E9EF;font-size:12px;cursor:pointer;outline:none;-webkit-appearance:none;appearance:none">
            @foreach($_allLangs as $code => [$name, $native])
            <option value="{{ $code }}" {{ $code === $_dashLang ? 'selected' : '' }} style="background:#101420;color:#E6E9EF">
              {{ $native }} ({{ strtoupper($code) }}){{ !in_array($code, $_preTranslated) ? ' ✦ AI' : '' }}
            </option>
            @endforeach
          </select>
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#5C6678" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none"><path d="M6 9l6 6 6-6"/></svg>
        </div>
        <a href="https://github.com/Youssef-Mekkkawy/laravel-ai-translator" target="_blank"
          style="display:flex;align-items:center;gap:7px;padding:8px 12px;border-radius:10px;border:1px solid #1B2130;background:#10141C;color:#8B93A5;font-size:12px;white-space:nowrap;text-decoration:none">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-3.16 19.49c.5.09.68-.22.68-.48v-1.7c-2.78.6-3.37-1.34-3.37-1.34-.45-1.16-1.11-1.47-1.11-1.47-.91-.62.07-.6.07-.6 1 .07 1.53 1.03 1.53 1.03.9 1.53 2.36 1.09 2.94.83.09-.65.35-1.09.63-1.34-2.22-.25-4.55-1.11-4.55-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.65 0 0 .84-.27 2.75 1.02a9.5 9.5 0 0 1 5 0c1.91-1.29 2.75-1.02 2.75-1.02.55 1.38.2 2.4.1 2.65.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.68-4.57 4.93.36.31.68.92.68 1.85v2.74c0 .27.18.58.69.48A10 10 0 0 0 12 2z"/></svg>
          Star on GitHub
        </a>
      </div>
    </div>

    {{-- Page content --}}
    <div id="page-content">
      @yield('content')
    </div>
  </div>

  {{-- ── MODALS ───────────────────────────────────────── --}}

  {{-- Restore backup modal --}}
  <div class="modal-backdrop" x-show="restoreOpen" x-cloak @click.self="restoreOpen=false">
    <div style="width:100%;max-width:440px;border:1px solid #4A3A2A;border-radius:16px;background:#101420;padding:22px;box-shadow:0 30px 80px rgba(0,0,0,.6)">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
        <div style="width:38px;height:38px;flex:none;border-radius:11px;background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.3);display:grid;place-items:center;color:#FBBF24">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 8v5"/><path d="M12 17h.01"/><path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg>
        </div>
        <div style="font-size:15px;font-weight:600" x-text="t.restoreTitle"></div>
      </div>
      <div style="font-size:13px;color:#8B93A5;margin-bottom:14px" x-text="t.restoreWarn"></div>
      <div style="padding:12px 14px;border-radius:10px;background:#0D111A;border:1px solid #1B2130;margin-bottom:18px">
        <div style="font-family:'JetBrains Mono',monospace;font-size:12.5px;color:#E6E9EF;direction:ltr" x-text="restoreTarget"></div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button @click="restoreOpen=false" style="padding:9px 16px;border-radius:10px;border:1px solid #2B3446;background:transparent;color:#8B93A5;font-size:13px;cursor:pointer" x-text="t.cancel"></button>
        <button @click="confirmRestore()" style="padding:9px 16px;border-radius:10px;border:1px solid #FBBF24;background:#FBBF24;color:#2A1D02;font-size:13px;font-weight:600;cursor:pointer" x-text="t.restoreConfirm"></button>
      </div>
    </div>
  </div>

  {{-- Lock key modal --}}
  <div class="modal-backdrop" x-show="lockOpen" x-cloak @click.self="lockOpen=false">
    <div class="modal-box">
      <div style="display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid #1B2130">
        <div style="font-size:14px;font-weight:600;flex:1" x-text="t.lockKeyTitle"></div>
        <button @click="lockOpen=false" style="width:28px;height:28px;display:grid;place-items:center;border-radius:8px;border:1px solid #1B2130;background:transparent;color:#5C6678;cursor:pointer;font-size:15px">×</button>
      </div>
      <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <label>
            <span style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px" x-text="t.language"></span>
            <select x-model="lockLang" style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-size:13px;outline:none">
              <template x-for="l in configuredLangs" :key="l.code">
                <option :value="l.code" x-text="l.label"></option>
              </template>
            </select>
          </label>
          <label>
            <span style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px" x-text="t.key"></span>
            <input x-model="lockKey" placeholder="auth.login" style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-family:'JetBrains Mono',monospace;font-size:12px;outline:none;direction:ltr">
          </label>
        </div>
        <label>
          <span style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px" x-text="t.reasonOptional"></span>
          <input x-model="lockReason" :placeholder="t.reasonPlaceholder" style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-size:13px;outline:none">
        </label>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;padding:0 20px 18px">
        <button @click="lockOpen=false" style="padding:9px 16px;border-radius:10px;border:1px solid #2B3446;background:transparent;color:#8B93A5;font-size:13px;cursor:pointer" x-text="t.cancel"></button>
        <button @click="confirmLock()" :disabled="!lockKey.trim()"
          :style="lockKey.trim() ? 'padding:9px 16px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer' : 'padding:9px 16px;border-radius:10px;border:1px solid #2B3446;background:#161C27;color:#5C6678;font-size:13px;font-weight:600;cursor:not-allowed'"
          x-text="t.lockKeyConfirm"></button>
      </div>
    </div>
  </div>

  {{-- Add language modal --}}
  <div class="modal-backdrop" x-show="addOpen" x-cloak @click.self="addOpen=false">
    <div class="modal-box">
      <div style="display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid #1B2130">
        <div style="font-size:14px;font-weight:600;flex:1" x-text="t.addLanguage"></div>
        <button @click="addOpen=false" style="width:28px;height:28px;display:grid;place-items:center;border-radius:8px;border:1px solid #1B2130;background:transparent;color:#5C6678;cursor:pointer;font-size:15px">×</button>
      </div>
      <div style="padding:16px 20px 20px">
        <input x-model="addQuery" :placeholder="t.searchLanguage" style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-size:13px;outline:none;margin-bottom:10px">
        <div style="display:flex;flex-direction:column;gap:6px;max-height:230px;overflow-y:auto">
          <template x-for="opt in filteredAddOptions" :key="opt.code">
            <button @click="pickLanguage(opt)" style="display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;cursor:pointer;text-align:start">
              <span style="width:30px;height:22px;flex:none;border-radius:5px;background:#161C27;border:1px solid #2B3446;display:grid;place-items:center;font-family:'JetBrains Mono',monospace;font-size:10px;color:#8B93A5" x-text="opt.code"></span>
              <span style="font-size:13px;font-weight:500" x-text="opt.name"></span>
              <span style="margin-inline-start:auto;font-size:12px;color:#5C6678" x-text="opt.native"></span>
            </button>
          </template>
        </div>
      </div>
    </div>
  </div>

  {{-- ── TOASTS ───────────────────────────────────────── --}}
  <div style="position:fixed;bottom:24px;inset-inline-end:24px;z-index:80;display:flex;flex-direction:column;gap:10px;pointer-events:none">
    <template x-for="toast in toasts" :key="toast.id">
      <div style="display:flex;align-items:center;gap:12px;min-width:260px;max-width:360px;padding:13px 16px;border-radius:12px;background:#141A26;border:1px solid #2B3446;box-shadow:0 16px 40px rgba(0,0,0,.5);animation:tin .22s ease">
        <span :style="'width:8px;height:8px;flex:none;border-radius:50%;background:' + (toast.kind==='err' ? '#F87171' : '#6EE7B7')"></span>
        <div>
          <div style="font-size:13px;font-weight:600;color:#E6E9EF" x-text="toast.title"></div>
          <div style="font-size:12px;color:#8B93A5;margin-top:2px" x-text="toast.body" x-show="toast.body"></div>
        </div>
      </div>
    </template>
  </div>

</div>{{-- /#app-shell --}}


<script>

function dashboard() {
  return {
    page:         '{{ request()->segment(2) ?: "overview" }}',
    ar:           {{ $_isRtl ? 'true' : 'false' }},
    expanded:     true,
    toasts:       [],
    restoreOpen:  false, restoreTarget: '',
    lockOpen:     false, lockLang: '{{ $cfgLangsJs[0]["code"] ?? "ar" }}', lockKey: '', lockReason: '',
    addOpen:      false, addQuery: '',
    activeProvider: '{{ $cfgDriver }}',
    configuredLangs: @json($cfgLangsJs),

    // PHP-driven translations (switches on page reload via cookie)
    t: @json($_trans),

    // Keep the getter for backwards compatibility but use PHP data
    _tBak() {
      const en = {
        overview:'Overview', languages:'Languages', locked:'Locked Keys',
        history:'History', backups:'Backups', settings:'Settings', collapse:'Collapse',
        quickActions:'Quick actions', scan:'Scan', translate:'Translate', dryRun:'Dry run',
        activeProvider:'Active provider', lastSync:'Last sync',
        coverageByLang:'Coverage by language', addLanguage:'Add language',
        addLanguageHint:'Select a language to add', searchLanguage:'Search languages...',
        language:'Language', key:'Key', value:'Value', lockedBy:'Locked by',
        reason:'Reason', unlock:'Unlock', lockKeyTitle:'Lock a Key',
        reasonOptional:'Reason (optional)', reasonPlaceholder:'e.g. Client preferred term',
        lockKeyConfirm:'Lock Key', cancel:'Cancel',
        restoreTitle:'Restore Backup', restoreWarn:'This will replace all current language files.',
        restoreConfirm:'Yes, restore', backupSettings:'Backup settings',
        keepLast:'Keep last', keepLastHint:'Older backups are deleted automatically',
        autoBackup:'Auto-backup', autoBackupHint:'Before every translation run',
        createBackup:'Create backup', download:'Download', restore:'Restore',
        emptyBackupsT:'No backups yet', emptyBackupsB:'A backup is created automatically before every translation run.',
        save:'Save changes', reset:'Reset to defaults', saving:'Saving...',
        files:'files', keys:'keys',
      };
      const ar = {
        overview:'نظرة عامة', languages:'اللغات', locked:'المفاتيح المقفلة',
        history:'السجل', backups:'النسخ الاحتياطية', settings:'الإعدادات', collapse:'طيّ',
        quickActions:'إجراءات سريعة', scan:'مسح', translate:'ترجمة', dryRun:'تجريبي',
        activeProvider:'المزوّد النشط', lastSync:'آخر مزامنة',
        coverageByLang:'التغطية حسب اللغة', addLanguage:'إضافة لغة',
        addLanguageHint:'اختر لغة لإضافتها', searchLanguage:'ابحث عن لغة...',
        language:'اللغة', key:'المفتاح', value:'القيمة', lockedBy:'قفل بواسطة',
        reason:'السبب', unlock:'فتح', lockKeyTitle:'قفل مفتاح',
        reasonOptional:'السبب (اختياري)', reasonPlaceholder:'مثال: مصطلح العميل',
        lockKeyConfirm:'تأكيد القفل', cancel:'إلغاء',
        restoreTitle:'استعادة نسخة', restoreWarn:'سيؤدي هذا إلى استبدال جميع ملفات اللغة.',
        restoreConfirm:'تأكيد الاستعادة', backupSettings:'إعدادات النسخ الاحتياطي',
        keepLast:'الاحتفاظ بآخر', keepLastHint:'يتم حذف النسخ القديمة تلقائياً',
        autoBackup:'نسخ تلقائي', autoBackupHint:'قبل كل عملية ترجمة',
        createBackup:'إنشاء نسخة', download:'تحميل', restore:'استعادة',
        emptyBackupsT:'لا توجد نسخ احتياطية', emptyBackupsB:'تُنشأ نسخة تلقائياً قبل كل ترجمة.',
        save:'حفظ التغييرات', reset:'إعادة الضبط', saving:'جاري الحفظ...',
        files:'ملفات', keys:'مفتاح',
      };
      return this.ar ? ar : en;
    },

    get pageTitle() {
      const e = { overview:'Overview',languages:'Languages',locked:'Locked Keys',history:'History',backups:'Backups',settings:'Settings' };
      const a = { overview:'نظرة عامة',languages:'اللغات',locked:'المفاتيح المقفلة',history:'السجل',backups:'النسخ الاحتياطية',settings:'الإعدادات' };
      return (this.ar ? a : e)[this.page] || '';
    },
    get pageSub() {
      const e = { overview:'Translation state of your application',languages:'Locales configured in config/ai-translator.php',locked:'Keys protected from being overwritten',history:'Every sync run, with cost and diff',backups:'Snapshots of your language files',settings:'Provider, models and behaviour' };
      const a = { overview:'حالة الترجمة في تطبيقك',languages:'اللغات المُهيّأة',locked:'المفاتيح المحمية',history:'سجلات المزامنة',backups:'لقطات ملفات اللغة',settings:'المزوّد والنماذج' };
      return (this.ar ? a : e)[this.page] || '';
    },
    get providerLabel() {
      return this.activeProvider;
    },
    get filteredAddOptions() {
      const all = [
        {code:'ar',name:'Arabic',native:'العربية'},{code:'fr',name:'French',native:'Français'},
        {code:'es',name:'Spanish',native:'Español'},{code:'de',name:'German',native:'Deutsch'},
        {code:'it',name:'Italian',native:'Italiano'},{code:'pt',name:'Portuguese',native:'Português'},
        {code:'ru',name:'Russian',native:'Русский'},{code:'zh',name:'Chinese',native:'中文'},
        {code:'ja',name:'Japanese',native:'日本語'},{code:'ko',name:'Korean',native:'한국어'},
        {code:'tr',name:'Turkish',native:'Türkçe'},{code:'nl',name:'Dutch',native:'Nederlands'},
      ];
      if (!this.addQuery) return all;
      const q = this.addQuery.toLowerCase();
      return all.filter(o => o.name.toLowerCase().includes(q) || o.code.includes(q));
    },

    go(p) {
      const urls = {
        overview: '{{ route("ai-translator.overview") }}',
        languages:'{{ route("ai-translator.languages") }}',
        locked:   '{{ route("ai-translator.locked") }}',
        history:  '{{ route("ai-translator.history") }}',
        backups:  '{{ route("ai-translator.backups") }}',
        settings: '{{ route("ai-translator.settings") }}',
      };
      if (urls[p]) window.location.href = urls[p];
    },

    async api(endpoint, body = {}) {
      const r = await fetch('{{ url("") }}/ai-translator/api/' + endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify(body),
      });
      return r.json();
    },

    confirmRestore() {
      this.api('backups/restore', { timestamp: this.restoreTarget }).then(r => {
        this.restoreOpen = false;
        this.toast(r.success ? (this.ar ? 'تمت الاستعادة' : 'Backup restored') : r.message, '', r.success ? 'ok' : 'err');
      });
    },
    async confirmLock() {
      if (!this.lockKey.trim()) return;
      const r = await this.api('lock', { lang: this.lockLang, key: this.lockKey.trim(), reason: this.lockReason.trim() });
      this.lockOpen = false; this.lockKey = ''; this.lockReason = '';
      this.toast(r.success ? (this.ar ? 'تم القفل' : 'Key locked') : r.message, this.lockKey, r.success ? 'ok' : 'err');
    },
    pickLanguage(opt) {
      this.addOpen = false;
      this.toast(this.ar ? 'تمت الإضافة' : 'Language added', opt.name);
    },
    toast(title, body = '', kind = 'ok') {
      const id = Date.now() + Math.random();
      this.toasts.push({ id, title, body, kind });
      setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 3500);
    },
  };
}

function langSelector() {
  const preTranslated = @json($_preTranslated);
  const allLangsMap   = @json($_allLangs);

  const allLangs = Object.entries(allLangsMap).map(([code, names]) => ({
    code,
    name:          names[0],
    native:        names[1],
    preTranslated: preTranslated.includes(code),
  }));

  return {
    open:           false,
    generating:     false,
    generatingCode: '',
    currentCode:    '{{ $_dashLang }}',
    allLangs,

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

      // On-demand generation via Ollama
      this.generating     = true;
      this.generatingCode = lang.code;

      try {
        const r = await fetch('{{ url("ai-translator/api/dashboard-lang") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
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

      this.generating     = false;
      this.generatingCode = '';
    },

    setCookieAndReload(code) {
      document.cookie = 'dashboard_lang=' + code + ';path=/;max-age=31536000';
      window.location.reload();
    },
  };
}
</script>
</body>
</html>
