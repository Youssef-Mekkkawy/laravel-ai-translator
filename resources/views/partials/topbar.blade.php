
<header class="topbar glass">
  <div class="topbar-left">
    <button type="button" class="menu-btn" aria-label="Menu" @click="mobileOpen = true">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
        stroke-linejoin="round" stroke-width="2" aria-hidden="true">
        <path d="M4 5h16"></path>
        <path d="M4 12h16"></path>
        <path d="M4 19h16"></path>
      </svg>
    </button>
    <h1 class="page-title" x-text="pageTitle"></h1>
    <span class="sync-badge"><span class="status-dot"></span>Synced 2 min</span>
  </div>

  <div class="topbar-right" x-data="langSelector()">
    <div class="language-control" x-data="langSelector()" @click.outside="open = false">
      <button type="button" class="pill-btn" @click.stop="toggleLanguage($event)"
        :aria-expanded="open ? 'true' : 'false'" aria-label="Interface language" title="Interface language">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
          stroke-linejoin="round" stroke-width="2" aria-hidden="true">
          <circle cx="12" cy="12" r="10"></circle>
          <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
          <path d="M2 12h20"></path>
        </svg>
        <span x-text="currentCode.toUpperCase()">EN</span>
      </button>

      <div class="lang-menu glass" :class="open ? 'is-open' : ''" :style="menuStyle" x-cloak role="menu">
        <template x-for="lang in allLangs" :key="lang.code">
          <button type="button" :class="lang.code === currentCode ? 'is-active' : ''" @click="selectLang(lang)">
            <span x-text="lang.native"></span>
            <span style="opacity:.5;font-size:.7rem"
              x-text="' (' + lang.code.toUpperCase() + ')' + (lang.preTranslated ? '' : ' ✦ AI')"></span>
          </button>
        </template>
      </div>
    </div>
    <button type="button" class="icon-circle-btn" data-theme-toggle aria-label="Switch theme" title="Switch theme">
      <svg class="icon icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
        stroke-linejoin="round" stroke-width="2" aria-hidden="true">
        <circle cx="12" cy="12" r="4"></circle>
        <path d="M12 2v2"></path>
        <path d="M12 20v2"></path>
        <path d="m4.93 4.93 1.41 1.41"></path>
        <path d="m17.66 17.66 1.41 1.41"></path>
        <path d="M2 12h2"></path>
        <path d="M20 12h2"></path>
        <path d="m6.34 17.66-1.41 1.41"></path>
        <path d="m19.07 4.93-1.41 1.41"></path>
      </svg>
      <svg class="icon icon--moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
        stroke-linejoin="round" stroke-width="2" aria-hidden="true">
        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path>
      </svg>
    </button>

    <span class="avatar"><span>YM</span><span class="avatar-status"></span></span>
  </div>
</header>