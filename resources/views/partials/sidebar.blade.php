{{-- Sidebar: brand, navigation, active provider, support links.
     Included inside .layout, so it shares the `mobileOpen` Alpine state. --}}
@php
  // key => English fallback label + inner SVG markup. The visible text comes from t.<key>.
  $navItems = [
    'overview' => [
      'label' => 'Overview',
      'icon'  => '<rect width="7" height="7" x="3" y="3" rx="1"></rect><rect width="7" height="7" x="14" y="3" rx="1"></rect><rect width="7" height="7" x="14" y="14" rx="1"></rect><rect width="7" height="7" x="3" y="14" rx="1"></rect>',
    ],
    'languages' => [
      'label' => 'Languages',
      'icon'  => '<path d="m5 8 6 6"></path><path d="m4 14 6-6 2-3"></path><path d="M2 5h12"></path><path d="M7 2h1"></path><path d="m22 22-5-10-5 10"></path><path d="M14 18h6"></path>',
    ],
    'locked' => [
      'label' => 'Locked Keys',
      'icon'  => '<rect width="18" height="11" x="3" y="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>',
    ],
    'history' => [
      'label' => 'History',
      'icon'  => '<path d="M3 12a9 9 0 1 0 3-6.7"></path><path d="M3 4v5h5"></path><path d="M12 7v5l4 2"></path>',
    ],
    'unused' => [
      'label' => 'Unused keys',
      'icon'  => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path>',
    ],
    'backups' => [
      'label' => 'Backups',
      'icon'  => '<rect width="20" height="5" x="2" y="3" rx="1"></rect><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"></path><path d="M10 12h4"></path>',
    ],
    'settings' => [
      'label' => 'Settings',
      'icon'  => '<path d="M9.671 4.136a2.34 2.34 0 0 1 4.659 0 2.34 2.34 0 0 0 3.319 1.915 2.34 2.34 0 0 1 2.33 4.033 2.34 2.34 0 0 0 0 3.831 2.34 2.34 0 0 1-2.33 4.033 2.34 2.34 0 0 0-3.319 1.915 2.34 2.34 0 0 1-4.659 0 2.34 2.34 0 0 0-3.32-1.915 2.34 2.34 0 0 1-2.33-4.033 2.34 2.34 0 0 0 0-3.831A2.34 2.34 0 0 1 6.35 6.051a2.34 2.34 0 0 0 3.319-1.915"></path><circle cx="12" cy="12" r="3"></circle>',
    ],
  ];
@endphp

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
      @foreach($navItems as $key => $item)
        <button type="button" class="nav-link" :class="page === '{{ $key }}' ? 'is-active' : ''" @click="go('{{ $key }}'); mobileOpen = false">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $item['icon'] !!}</svg>
          <span x-show="expanded" x-text="t.{{ $key }} || '{{ $item['label'] }}'"></span>
        </button>
      @endforeach
    </nav>

    <button type="button" class="collapse-btn" @click="toggleSidebar()" aria-label="Collapse sidebar">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="M9 3v18"></path><path d="m16 15-3-3 3-3"></path>
      </svg>
      <span x-show="expanded" x-text="t.collapse || 'Collapse'"></span>
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
