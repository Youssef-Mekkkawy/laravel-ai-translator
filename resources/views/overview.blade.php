@extends('ai-translator::layout')
@section('title', '{{ $_trans["overview"] ?? "Overview" }}')
@section('content')
    @php
        $_d = config('ai-translator.driver', 'ollama');
        $_prov = config('ai-translator.providers.' . $_d, []);
        $_model = '';
        if (is_array($_prov)) {
            $_model = is_string($_prov['model'] ?? null) ? $_prov['model']
                : (is_string($_prov['plan'] ?? null) ? $_prov['plan'] : '');
        }
        $_providerName = ucfirst($_d);
        $_providerInit = strtoupper(substr($_d, 0, 1));
        $tr = $_trans ?? [];
        $_flagMap = [
            'ar' => '🇸🇦',
            'fr' => '🇫🇷',
            'es' => '🇪🇸',
            'de' => '🇩🇪',
            'zh' => '🇨🇳',
            'ja' => '🇯🇵',
            'tr' => '🇹🇷',
            'ru' => '🇷🇺',
            'pt' => '🇵🇹',
            'ko' => '🇰🇷',
            'it' => '🇮🇹',
            'nl' => '🇳🇱',
            'pl' => '🇵🇱',
            'hi' => '🇮🇳',
            'sv' => '🇸🇪',
            'vi' => '🇻🇳',
            'id' => '🇮🇩',
            'en' => '🇬🇧',
        ];
    @endphp
    <link rel="stylesheet" href="{{ url('ai-translator/assets/overview.css') }}?v={{ $version }}">


    <div x-data="overviewPage()" class="overview-stack">

        {{-- Running translation banner --}}
        <section class="status-banner glass--strong" x-show="running" x-cloak>
            <div class="banner-head">
                <div>
                    <p class="banner-title" x-text="statusMsg || '{{ addslashes($tr['translate'] ?? 'Translating') }}...'">
                    </p>
                    <p class="banner-sub">
                        {{ $tr['background_translation'] ?? 'Translation is running in the background. You can keep working while it finishes.' }}
                    </p>
                </div>
                <div class="banner-actions">
                    <button class="btn btn--ghost" type="button" @click="doScan()" :disabled="running">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        {{ $tr['scan'] ?? 'Scan' }}
                    </button>
                    <button class="btn btn--primary" type="button" @click="doTranslate(false)"
                        :disabled="!providerOk || running">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 5a2 2 0 0 1 3.008-1.728l11.997 6.998a2 2 0 0 1 .003 3.458l-12 7A2 2 0 0 1 5 19z">
                            </path>
                        </svg>
                        {{ $tr['translate'] ?? 'Translate' }}
                    </button>
                    <button class="btn btn--ghost" type="button" @click="doTranslate(true)"
                        :disabled="!providerOk || running">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        {{ $tr['dry_run'] ?? 'Dry run' }}
                    </button>
                </div>
            </div>
            <div class="banner-progress">
                <div class="progress-track">
                    <div class="progress-fill progress-fill--brand" :style="`width:${progressPct}%`"></div>
                </div>
                <p class="tabular-nums" x-text="progressPct + '%'">0%</p>
            </div>
        </section>

        {{-- Translation stats --}}
        <section class="overview-stats-grid" aria-label="Translation stats">
            <div class="overview-stat-card glass">
                <p class="overview-stat-label">{{ $tr['total_keys'] ?? 'Total keys' }}</p>
                <p class="overview-stat-value tabular-nums" x-text="totalKeys.toLocaleString()"></p>
                <div class="progress-track">
                    <div class="progress-fill progress-fill--brand" style="width:100%"></div>
                </div>
                <p class="overview-stat-note">{{ count($coverage) }} {{ $tr['languages'] ?? 'languages' }}</p>
            </div>

            <div class="overview-stat-card glass">
                <p class="overview-stat-label">{{ $tr['translated'] ?? 'Translated' }}</p>
                <p class="overview-stat-value tabular-nums" style="color:var(--mint)"
                    x-text="translatedCount.toLocaleString()"></p>
                <div class="progress-track">
                    <div class="progress-fill progress-fill--mint"
                        :style="`width:${totalKeys > 0 ? Math.min(100, Math.round((translatedCount / totalKeys) * 10000) / 100) : 0}%`">
                    </div>
                </div>
                <p class="overview-stat-note"
                    x-text="(totalKeys > 0 ? Math.round((translatedCount / totalKeys) * 100) : 0) + '%'">0%</p>
            </div>

            <div class="overview-stat-card glass">
                <p class="overview-stat-label">{{ $tr['missing'] ?? 'Missing' }}</p>
                <p class="overview-stat-value tabular-nums" style="color:var(--sun)" x-text="missingCount.toLocaleString()">
                </p>
                <div class="progress-track">
                    <div class="progress-fill progress-fill--sun"
                        :style="`width:${totalKeys > 0 ? Math.min(100, Math.round((missingCount / Math.max(totalKeys,1)) * 10000) / 100) : 0}%`">
                    </div>
                </div>
                <p class="overview-stat-note">{{ $tr['keys'] ?? 'keys' }}</p>
            </div>

            <div class="overview-stat-card glass">
                <p class="overview-stat-label">{{ $tr['locked_keys'] ?? 'Locked' }}</p>
                <p class="overview-stat-value tabular-nums" style="color:var(--coral)"
                    x-text="lockedCount.toLocaleString()"></p>
                <div class="progress-track">
                    <div class="progress-fill progress-fill--coral"
                        :style="`width:${totalKeys > 0 ? Math.min(100, Math.round((lockedCount / Math.max(totalKeys,1)) * 10000) / 100) : 0}%`">
                    </div>
                </div>
                <p class="overview-stat-note">{{ $tr['keys'] ?? 'keys' }}</p>
            </div>

            <div class="overview-stat-card glass">
                <p class="overview-stat-label">{{ $tr['total_cost'] ?? 'Total cost' }}</p>
                <p class="overview-stat-value tabular-nums" style="color:var(--sky)" dir="ltr">
                    ${{ number_format($totalCost, 4) }}
                </p>
                <div class="progress-track">
                    <div class="progress-fill" style="background:var(--sky);width:{{ $totalCost > 0 ? 100 : 0 }}%"></div>
                </div>
                <p class="overview-stat-note">{{ $tr['across_all_runs'] ?? 'across all runs' }}</p>
            </div>
        </section>

        {{-- Coverage + Recent activity --}}
        <section class="overview-panels-grid">
            <div class="panel glass">
                <div class="overview-panel-head">
                    <h2 class="overview-panel-title">{{ $tr['coverage_by_lang'] ?? 'Coverage by language' }}</h2>
                    <a href="{{ route('ai-translator.languages') }}"
                        class="btn btn--ghost">{{ $tr['languages'] ?? 'Languages' }}</a>
                </div>

                @if(count($coverage) > 0)
                    <ul class="overview-lang-list">
                        @foreach($coverage as $coverageItem)
                            @php
                                $langCode = $coverageItem['lang'];
                                $pct = (int) $coverageItem['pct'];
                                $barClass = $pct >= 85 ? 'progress-fill--mint' : ($pct >= 60 ? 'progress-fill--brand' : 'progress-fill--sun');
                                $missing = (int) ($coverageItem['missing'] ?? 0);
                            @endphp
                            <li class="overview-lang-row">
                                <span class="overview-lang-flag" aria-hidden="true">{{ $_flagMap[$langCode] ?? '🌐' }}</span>
                                <div class="overview-lang-info">
                                    <div class="overview-lang-top">
                                        <span class="overview-lang-name" dir="ltr">{{ ucfirst($langCode) }}</span>
                                        <span class="overview-lang-pct tabular-nums" dir="ltr">{{ $pct }}%</span>
                                    </div>
                                    <div class="overview-lang-track progress-track">
                                        <div class="progress-fill {{ $barClass }}" style="width:{{ $pct }}%"></div>
                                    </div>
                                </div>
                                <span class="badge {{ $missing > 0 ? 'badge--missing' : 'badge--sync' }}" dir="ltr">
                                    {{ number_format($missing) }} {{ $tr['missing'] ?? 'missing' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="overview-empty" style="margin-top:1rem">
                        {{ $tr['no_languages'] ?? 'No translation languages are configured yet.' }}</div>
                @endif
            </div>

            <div class="panel glass">
                <div class="overview-panel-head">
                    <h2 class="overview-panel-title">{{ $tr['active_provider'] ?? 'Active provider' }}</h2>
                    <a href="{{ route('ai-translator.settings') }}"
                        class="btn btn--ghost">{{ $tr['settings'] ?? 'Settings' }}</a>
                </div>

                <div class="overview-provider-row">
                    <div class="overview-provider-icon">{{ $_providerInit }}</div>
                    <div>
                        <div class="overview-provider-name">{{ $_providerName }}</div>
                        <div class="overview-provider-model" dir="ltr">{{ $_model ?: '—' }}</div>
                    </div>
                    <div class="overview-provider-status">
                        @if($_d === 'ollama')
                            <span class="badge" :class="ollamaOk ? 'badge--sync' : 'badge--missing'">
                                <span class="status-dot" :class="ollamaOk ? 'status-dot--ok' : 'status-dot--warning'"></span>
                                <span
                                    x-text="ollamaOk ? '{{ addslashes($tr['connected'] ?? 'Connected') }}' : '{{ addslashes($tr['not_running'] ?? 'Not running') }}'"></span>
                            </span>
                        @else
                            <span class="badge badge--sync">
                                <span class="status-dot status-dot--ok"></span>
                                {{ $tr['connected'] ?? 'Connected' }}
                            </span>
                        @endif
                    </div>
                </div>

                @if($lastSync)
                    <div class="overview-footer-row">
                        <span class="overview-muted">{{ $tr['last_sync'] ?? 'Last sync' }}</span>
                        <span class="font-semibold" dir="ltr">{{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}</span>
                    </div>
                @else
                    <div class="overview-footer-row">
                        <span class="overview-muted">{{ $tr['last_sync'] ?? 'Last sync' }}</span>
                        <span class="overview-muted">{{ $tr['never'] ?? 'Never' }}</span>
                    </div>
                @endif

                <div class="overview-code">$ php artisan lang:translate</div>
                <div x-show="!providerOk" x-cloak style="margin-top:.75rem;font-size:.75rem;color:var(--sun)">
                    <span x-text="providerMsg"></span>
                </div>
                <div x-show="statusMsg && providerOk && !running" x-cloak
                    style="margin-top:.75rem;font-size:.75rem;color:var(--mint)" x-text="statusMsg"></div>
            </div>
        </section>
    </div>

    <script>
        @php $tr = $_trans ?? []; @endphp
        function overviewPage() {
            return {
                running: false,
                progressPct: 0,
                statusMsg: '',
                ollamaOk: false,
                providerOk: true,
                providerMsg: '',
                _pollTimer: null,
                _tickTimer: null,
                async init() {
                    @if($_d === 'ollama')
                                    try {
                                        const r = await fetch('{{ url("ai-translator/api/ollama/status") }}').then(r => r.json());
                                        this.ollamaOk = r.success && r.data?.running === true;
                                        this.providerOk = this.ollamaOk;
                                        this.providerMsg = this.ollamaOk ? '' : 'Ollama is not running. Go to Settings to start it.';
                                        @if(config('ai-translator.providers.ollama.auto_start', false))
                                            if (!this.ollamaOk) this.autoStartOllama();
                                        @endif
                          } catch { this.ollamaOk = false; }
                    @else
                        this.ollamaOk = true;
                    @endif

                    this.checkRunningOnLoad();
                },
                totalKeys:       {{ $totalKeys }},
                translatedCount: {{ $translatedCount }},
                missingCount:    {{ $missingCount }},
                lockedCount:     {{ $lockedCount }},
                coverage: @json($coverage),
                async checkRunningOnLoad() {
                    try {
                        const s = await fetch('{{ url("ai-translator/api/translate/status") }}').then(r => r.json());
                        if (s.success && s.data?.running) {
                            this.running = true;
                            this.progressPct = 30;
                            this.statusMsg = '{{ addslashes($tr["translate"] ?? "Translating") }} in background...';
                            this.startPolling(null);
                        }
                    } catch { }
                },
                autoStartOllama() {
                    if (this._ollamaPolling) return;
                    this._ollamaPolling = true;
                    fetch('{{ url("ai-translator/api/ollama/start") }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' },
                        body: JSON.stringify({}),
                    }).catch(() => { });
                    let attempts = 0;
                    const self = this;
                    const check = () => {
                        if (attempts >= 15 || self.ollamaOk) { self._ollamaPolling = false; return; }
                        attempts++;
                        fetch('{{ url("ai-translator/api/ollama/status") }}').then(r => r.json()).then(s => {
                            if (s.data?.running) { self.ollamaOk = true; self.providerOk = true; self.providerMsg = ''; self._ollamaPolling = false; }
                            else setTimeout(check, 2000);
                        }).catch(() => { self._ollamaPolling = false; });
                    };
                    setTimeout(check, 2000);
                    window.addEventListener('beforeunload', () => { self._ollamaPolling = false; }, { once: true });
                },
                async refreshStats() {
                    try {
                        const r = await fetch('{{ url("ai-translator/api/stats") }}').then(r => r.json());
                        if (r.success) {
                            this.totalKeys = r.data.totalKeys;
                            this.translatedCount = r.data.translatedCount;
                            this.missingCount = r.data.missingCount;
                            this.lockedCount = r.data.lockedCount;
                            this.coverage = r.data.coverage;
                        }
                    } catch { }
                },
                async doScan() {
                    this.statusMsg = '{{ addslashes($tr["scan"] ?? "Scanning") }}...';
                    try {
                        const r = await fetch('{{ url("ai-translator/api/scan") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({})
                        }).then(r => r.json());
                        if (r.success) {
                            this.statusMsg = r.data.total + ' {{ addslashes($tr["keys"] ?? "keys") }}';
                            await this.refreshStats();
                            setTimeout(() => { this.statusMsg = ''; }, 4000);
                        } else {
                            this.statusMsg = 'Error: ' + (r.message || 'Failed');
                        }
                    } catch (e) { this.statusMsg = 'Error: ' + e.message; }
                },
                async doTranslate(dryRun = false) {
                    this.running = true;
                    this.progressPct = 5;
                    this.statusMsg = dryRun
                        ? '{{ addslashes($tr["dry_run"] ?? "Dry run") }}...'
                        : '{{ addslashes($tr["translate"] ?? "Translating") }}...';

                    this._tickTimer = setInterval(() => {
                        if (this.progressPct < 85) this.progressPct += 3;
                    }, 1500);
                    try {
                        let prevCompletedAt = null;
                        try {
                            const pre = await fetch('{{ url("ai-translator/api/translate/status") }}').then(r => r.json());
                            prevCompletedAt = pre.data?.last_completed_at ?? null;
                        } catch { }
                        const r = await fetch('{{ url("ai-translator/api/translate") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                            body: JSON.stringify({ dry_run: dryRun }),
                        }).then(r => r.json());
                        if (!r.success) {
                            clearInterval(this._tickTimer);
                            this.running = false;
                            this.progressPct = 0;
                            this.statusMsg = 'Error: ' + (r.message || 'Failed to queue');
                            return;
                        }

                        this.statusMsg = '{{ addslashes($tr["translate"] ?? "Translating") }} in background...';
                        this.startPolling(prevCompletedAt);
                    } catch (e) {
                        clearInterval(this._tickTimer);
                        this.running = false;
                        this.progressPct = 0;
                        this.statusMsg = 'Error: ' + e.message;
                    }
                },

                startPolling(prevCompletedAt) {
                    if (this._pollTimer) clearInterval(this._pollTimer);

                    const startTime = Date.now();
                    this._pollTimer = setInterval(async () => {
                        try {
                            const s = await fetch('{{ url("ai-translator/api/translate/status") }}').then(r => r.json());

                            if (!s.success) return;

                            const data = s.data;
                            if (data.queue_size > 0) {
                                this.statusMsg = '{{ addslashes($tr["translate"] ?? "Translating") }}... (' + data.queue_size + ' more queued)';
                            } else if (data.running) {
                                this.statusMsg = '{{ addslashes($tr["translate"] ?? "Translating") }} in background...';
                            }
                            const done = !data.running && data.last_completed_at !== null
                                && data.last_completed_at !== prevCompletedAt;

                            if (done) {
                                clearInterval(this._pollTimer);
                                clearInterval(this._tickTimer);
                                this._pollTimer = null;

                                this.progressPct = 100;
                                const success = data.last_result?.success ?? true;
                                await this.refreshStats();

                                setTimeout(() => {
                                    this.running = false;
                                    this.progressPct = 0;
                                    this.statusMsg = success
                                        ? '✓ {{ addslashes($tr["translate"] ?? "Translation") }} complete'
                                        : 'Error: translation failed — check History for details';
                                    setTimeout(() => { this.statusMsg = ''; }, 5000);
                                }, 400);
                            }
                            if (Date.now() - startTime > 900000) {
                                clearInterval(this._pollTimer);
                                clearInterval(this._tickTimer);
                                this._pollTimer = null;
                                this.running = false;
                                this.progressPct = 0;
                                this.statusMsg = 'Timed out — check History for results.';
                            }

                        } catch { }
                    }, 3000);
                },
            }
        }
    </script>
@endsection