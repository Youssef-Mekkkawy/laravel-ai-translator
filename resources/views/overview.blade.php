@extends('ai-translator::layout')
@section('title', '{{ $_trans["overview"] ?? "Overview" }}')

@section('content')
@php
    $_d     = config('ai-translator.driver', 'ollama');
    $_prov  = config('ai-translator.providers.'.$_d, []);
    $_model = '';
    if (is_array($_prov)) {
        $_model = is_string($_prov['model'] ?? null) ? $_prov['model']
                : (is_string($_prov['plan']  ?? null) ? $_prov['plan'] : '');
    }
    $_providerName = ucfirst($_d);
    $_providerInit = strtoupper(substr($_d, 0, 1));
    $tr = $_trans ?? [];
@endphp
<div x-data="overviewPage()" style="display:flex;flex-direction:column;gap:20px">

  {{-- Running banner --}}
  <div x-show="running" style="border:1px solid #1E3A34;border-radius:14px;background:linear-gradient(180deg,rgba(110,231,183,.07),rgba(110,231,183,.02));padding:18px 20px">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
      <span style="width:15px;height:15px;border-radius:50%;border:2px solid rgba(110,231,183,.25);border-top-color:#6EE7B7;animation:spin .8s linear infinite;display:inline-block"></span>
      <span style="font-weight:600;font-size:13.5px" x-text="statusMsg"></span>
      <span style="margin-inline-start:auto;font-family:'JetBrains Mono',monospace;font-size:12px;color:#8B93A5" x-text="progressPct + '%'"></span>
    </div>
    <div style="height:6px;border-radius:99px;background:#161C27;overflow:hidden">
      <div :style="`height:100%;border-radius:99px;background:linear-gradient(90deg,#6EE7B7,#38bdf8);transition:width .3s ease;width:${progressPct}%`"></div>
    </div>
  </div>

  {{-- Stats cards --}}
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px">
    <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:18px">
      <div style="font-size:11.5px;text-transform:uppercase;letter-spacing:.9px;color:#5C6678;margin-bottom:10px">{{ $tr['total_keys'] ?? 'Total Keys' }}</div>
      <div style="font-family:'JetBrains Mono',monospace;font-size:30px;font-weight:600;letter-spacing:-1px" x-text="totalKeys.toLocaleString()"></div>
      <div style="font-size:12px;color:#5C6678;margin-top:6px" x-text="totalKeys + ' {{ addslashes($tr["keys"] ?? "keys") }}'"></div>
    </div>
    <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:18px">
      <div style="font-size:11.5px;text-transform:uppercase;letter-spacing:.9px;color:#5C6678;margin-bottom:10px">{{ $tr['translated'] ?? 'Translated' }}</div>
      <div style="font-family:'JetBrains Mono',monospace;font-size:30px;font-weight:600;letter-spacing:-1px;color:#6EE7B7" x-text="translatedCount.toLocaleString()"></div>
      <div style="font-size:12px;color:#5C6678;margin-top:6px" x-text="(totalKeys > 0 ? Math.round((translatedCount / Math.max(totalKeys,1)) * 100) : 0) + '%'"></div>
    </div>
    <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:18px">
      <div style="font-size:11.5px;text-transform:uppercase;letter-spacing:.9px;color:#5C6678;margin-bottom:10px">{{ $tr['missing'] ?? 'Missing' }}</div>
      <div style="font-family:'JetBrains Mono',monospace;font-size:30px;font-weight:600;letter-spacing:-1px;color:#FBBF24" x-text="missingCount.toLocaleString()"></div>
      <div style="font-size:12px;color:#5C6678;margin-top:6px">{{ $tr['keys'] ?? 'keys' }}</div>
    </div>
    <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:18px">
      <div style="font-size:11.5px;text-transform:uppercase;letter-spacing:.9px;color:#5C6678;margin-bottom:10px">{{ $tr['locked_keys'] ?? 'Locked' }}</div>
      <div style="font-family:'JetBrains Mono',monospace;font-size:30px;font-weight:600;letter-spacing:-1px;color:#C4B5FD" x-text="lockedCount.toLocaleString()"></div>
      <div style="font-size:12px;color:#5C6678;margin-top:6px">{{ $tr['keys'] ?? 'keys' }}</div>
    </div>
  </div>

  {{-- Quick actions + Active provider --}}
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px">
    <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:20px">
      <div style="font-size:13px;font-weight:600;margin-bottom:14px">{{ $tr['quick_actions'] ?? 'Quick actions' }}</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
        <button @click="doScan()" :disabled="running"
          style="display:flex;align-items:center;gap:8px;padding:9px 14px;border-radius:10px;border:1px solid #2B3446;background:#161C27;color:#E6E9EF;font-size:13px;cursor:pointer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          {{ $tr['scan'] ?? 'Scan' }}
        </button>
        <button @click="doTranslate(false)" :disabled="running || !providerOk"
          :title="!providerOk ? providerMsg : ''"
          :style="!providerOk ? 'display:flex;align-items:center;gap:8px;padding:9px 14px;border-radius:10px;border:1px solid #2B3446;background:#161C27;color:#5C6678;font-size:13px;font-weight:600;cursor:not-allowed;opacity:.5' : 'display:flex;align-items:center;gap:8px;padding:9px 14px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer'">
          <span x-show="running" style="width:12px;height:12px;border-radius:50%;border:2px solid rgba(6,42,32,.3);border-top-color:#062A20;animation:spin .8s linear infinite;display:inline-block"></span>
          {{ $tr['translate'] ?? 'Translate' }}
        </button>
        <button @click="doTranslate(true)" :disabled="running || !providerOk"
          :style="!providerOk ? 'display:flex;align-items:center;gap:8px;padding:9px 14px;border-radius:10px;border:1px solid #2B3446;background:transparent;color:#5C6678;font-size:13px;cursor:not-allowed;opacity:.5' : 'display:flex;align-items:center;gap:8px;padding:9px 14px;border-radius:10px;border:1px solid #2B3446;background:transparent;color:#8B93A5;font-size:13px;cursor:pointer'">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          {{ $tr['dry_run'] ?? 'Dry run' }}
        </button>
      </div>

      {{-- Provider not connected warning --}}
      <div x-show="!providerOk" style="font-size:12px;color:#FBBF24;padding:6px 0;display:flex;align-items:center;gap:6px">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M10.3 3.9 2.4 18a2 2 0 0 0 1.7 3h15.8a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
        <span x-text="providerMsg"></span>
      </div>
      <div x-show="statusMsg && providerOk" style="font-size:12px;color:#6EE7B7;padding:6px 0" x-text="statusMsg"></div>
      <div style="font-family:'JetBrains Mono',monospace;font-size:11.5px;color:#3A4761;padding:10px 12px;border-radius:8px;background:#0D111A;border:1px solid #161C27">$ php artisan lang:translate</div>
    </div>

    <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:20px">
      <div style="font-size:13px;font-weight:600;margin-bottom:14px">{{ $tr['active_provider'] ?? 'Active provider' }}</div>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px">
        <div style="width:36px;height:36px;border-radius:10px;background:#161C27;border:1px solid #2B3446;display:grid;place-items:center;font-weight:700;font-size:14px;color:#6EE7B7">{{ $_providerInit }}</div>
        <div>
          <div style="font-weight:600;font-size:13.5px">{{ $_providerName }}</div>
          <div style="font-size:12px;color:#5C6678">{{ $_model }}</div>
        </div>
        @if($_d === 'ollama')
        {{-- Live Ollama status --}}
        <span :style="ollamaOk ? 'margin-inline-start:auto;display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:99px;background:rgba(110,231,183,.1);border:1px solid rgba(110,231,183,.25);color:#6EE7B7;font-size:11.5px;font-weight:600' : 'margin-inline-start:auto;display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:99px;background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:#F87171;font-size:11.5px;font-weight:600'">
          <span :style="'width:6px;height:6px;border-radius:50%;animation:pulse 2s infinite;background:' + (ollamaOk ? '#6EE7B7' : '#F87171')"></span>
          <span x-text="ollamaOk ? '{{ addslashes($tr['connected'] ?? 'Connected') }}' : 'Not running'"></span>
        </span>
        @else
        <span style="margin-inline-start:auto;display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:99px;background:rgba(110,231,183,.1);border:1px solid rgba(110,231,183,.25);color:#6EE7B7;font-size:11.5px;font-weight:600">
          <span style="width:6px;height:6px;border-radius:50%;background:#6EE7B7;animation:pulse 2s infinite"></span>
          {{ $tr['connected'] ?? 'Connected' }}
        </span>
        @endif
      </div>
      @if($lastSync)
      <div style="display:flex;gap:24px;padding-top:14px;border-top:1px solid #1B2130">
        <div>
          <div style="font-size:11.5px;color:#5C6678;margin-bottom:4px">{{ $tr['last_sync'] ?? 'Last sync' }}</div>
          <div style="font-weight:600;font-size:13px">{{ \Carbon\Carbon::parse($lastSync)->diffForHumans() }}</div>
        </div>
      </div>
      @endif
    </div>
  </div>

  {{-- Coverage by language --}}
  @if(count($coverage) > 0)
  <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:20px">
    <div style="font-size:13px;font-weight:600;margin-bottom:16px">{{ $tr['coverage_by_lang'] ?? 'Coverage by language' }}</div>
    <div style="display:flex;flex-direction:column;gap:14px">
      @foreach($coverage as $coverageItem)
      <div style="display:flex;align-items:center;gap:14px">
        <span style="width:28px;height:20px;flex:none;border-radius:4px;background:#161C27;border:1px solid #2B3446;display:grid;place-items:center;font-family:'JetBrains Mono',monospace;font-size:9px;color:#8B93A5">{{ strtoupper($coverageItem['lang']) }}</span>
        <span style="width:80px;font-size:13px">{{ ucfirst($coverageItem['lang']) }}</span>
        <div style="flex:1;height:6px;border-radius:99px;background:#161C27;overflow:hidden">
          <div style="height:100%;border-radius:99px;background:{{ $coverageItem['pct'] >= 95 ? '#6EE7B7' : ($coverageItem['pct'] >= 70 ? '#38bdf8' : '#FBBF24') }};width:{{ $coverageItem['pct'] }}%"></div>
        </div>
        <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:#8B93A5;width:36px;text-align:end">{{ $coverageItem['pct'] }}%</span>
      </div>
      @endforeach
    </div>
  </div>
  @endif

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

    // Check Ollama status on load — auto-start if configured
    async init() {
      @if($_d === 'ollama')
      try {
        const r = await fetch('{{ url("ai-translator/api/ollama/status") }}').then(r => r.json());
        this.ollamaOk = r.success && r.data?.running === true;
        this.providerOk = this.ollamaOk;
        this.providerMsg = this.ollamaOk ? '' : 'Ollama is not running. Go to Settings to start it.';

        @if(config('ai-translator.providers.ollama.auto_start', false))
        // Auto-start is enabled — start Ollama if not running (non-blocking)
        if (!this.ollamaOk) {
          this.autoStartOllama();
        }
        @endif

      } catch { this.ollamaOk = false; }
      @else
      this.ollamaOk = true;
      @endif
    },

    // Live stats — updated after scan/translate
    totalKeys:       {{ $totalKeys }},
    translatedCount: {{ $translatedCount }},
    missingCount:    {{ $missingCount }},
    lockedCount:     {{ $lockedCount }},
    coverage:        @json($coverage),

    autoStartOllama() {
      if (this._ollamaPolling) return; // prevent multiple polls
      this._ollamaPolling = true;

      // Fire start — don't wait
      fetch('{{ url("ai-translator/api/ollama/start") }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
        },
        body: JSON.stringify({}),
      }).catch(() => {});

      // Poll with setTimeout instead of setInterval — easier to control
      let attempts = 0;
      const self = this;

      const checkStatus = () => {
        if (attempts >= 15 || self.ollamaOk) {
          self._ollamaPolling = false;
          return;
        }
        attempts++;

        fetch('{{ url("ai-translator/api/ollama/status") }}')
          .then(r => r.json())
          .then(s => {
            if (s.data?.running) {
              self.ollamaOk    = true;
              self.providerOk  = true;
              self.providerMsg = '';
              self._ollamaPolling = false;
            } else {
              setTimeout(checkStatus, 2000);
            }
          })
          .catch(() => { self._ollamaPolling = false; });
      };

      setTimeout(checkStatus, 2000);

      // Stop on navigation
      window.addEventListener('beforeunload', () => { self._ollamaPolling = false; }, { once: true });
    },

    async refreshStats() {
      try {
        const r = await fetch('{{ url("ai-translator/api/stats") }}').then(r => r.json());
        if (r.success) {
          this.totalKeys       = r.data.totalKeys;
          this.translatedCount = r.data.translatedCount;
          this.missingCount    = r.data.missingCount;
          this.lockedCount     = r.data.lockedCount;
          this.coverage        = r.data.coverage;
        }
      } catch {}
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
      this.statusMsg = dryRun ? '{{ addslashes($tr["dry_run"] ?? "Dry run") }}...' : '{{ addslashes($tr["translate"] ?? "Translating") }}...';
      const ticker = setInterval(() => { if (this.progressPct < 85) this.progressPct += 5; }, 800);
      try {
        const r = await fetch('{{ url("ai-translator/api/translate") }}', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
          body: JSON.stringify({ dry_run: dryRun })
        }).then(r => r.json());
        clearInterval(ticker);
        this.progressPct = 100;
        if (r.success) await this.refreshStats();
        setTimeout(() => {
          this.running = false;
          this.progressPct = 0;
          this.statusMsg = r.success ? '✓ {{ addslashes($tr["translate"] ?? "Translation") }} complete' : 'Error: ' + (r.message || 'Failed');
          setTimeout(() => { this.statusMsg = ''; }, 5000);
        }, 400);
      } catch (e) {
        clearInterval(ticker);
        this.running = false;
        this.progressPct = 0;
        this.statusMsg = 'Error: ' + e.message;
      }
    },
  }
}
</script>
@endsection
