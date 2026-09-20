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
        {{-- Sidebar --}}
        @include('ai-translator::partials.sidebar')

        <main class="main">
          {{-- Top bar --}}
          @include('ai-translator::partials.topbar')

          {{-- Page content --}}
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

    @include('ai-translator::partials.modals')
    {{-- Lock key modal --}}
    @include('ai-translator::partials.lock-modal')
    {{-- Add language modal --}}
    @include('ai-translator::partials.add-modal')

    {{-- Toasts --}}
        @include('ai-translator::partials.toasts')

  </div>

  @include('ai-translator::partials.scripts')
</body>
</html>