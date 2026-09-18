@extends('ai-translator::layout')
@section('title', '{{ $_trans["languages"] ?? "Languages" }}')
@section('content')
@php
  $tr = $_trans ?? [];
  $flags = ['ar'=>'🇸🇦','fr'=>'🇫🇷','es'=>'🇪🇸','de'=>'🇩🇪','pt'=>'🇵🇹','ja'=>'🇯🇵','it'=>'🇮🇹','ru'=>'🇷🇺','zh'=>'🇨🇳','tr'=>'🇹🇷','ko'=>'🇰🇷','nl'=>'🇳🇱','pl'=>'🇵🇱','hi'=>'🇮🇳','sv'=>'🇸🇪','da'=>'🇩🇰'];
@endphp
<div x-data="languagesPage()" class="page-stack">
  <section class="page-intro glass--strong">
    <div class="page-hero-row">
      <div>
        <p class="page-intro-title">{{ $tr['languages'] ?? 'Languages' }}</p>
        <p class="page-intro-sub">{{ $tr['languages_sub'] ?? 'Enable the locales this package keeps in sync.' }}</p>
        <div style="margin-top:.65rem;font-size:.6875rem;color:color-mix(in oklab,var(--ink) 50%,transparent)">
          {{ count(array_filter($languages, fn($l) => $l['enabled'])) }} {{ $tr['enabled_of'] ?? 'enabled of' }} {{ count($languages) }}
        </div>
      </div>
      <button class="btn btn--primary" type="button" @click="$dispatch('open-add-language')">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        {{ $tr['add_language'] ?? 'Add language' }}
      </button>
    </div>
    <div x-show="msg" x-text="msg" x-transition class="page-message" :class="msgOk ? 'page-message--ok' : 'page-message--err'"></div>
  </section>

  @if(count($languages) === 0)
    <section class="glass empty-state">
      <div class="empty-state-icon">+</div>
      <p class="empty-state-title">{{ $tr['no_languages'] ?? 'No languages configured' }}</p>
      <p class="empty-state-text">{{ $tr['add_config_hint'] ?? 'Add languages in config/ai-translator.php' }}</p>
    </section>
  @else
    <section class="language-grid">
      @foreach($languages as $lang)
        @php
          $code = $lang['code'];
          $enabled = (bool)($lang['enabled'] ?? false);
          $pct = (int)($lang['pct'] ?? 0);
          $flag = $flags[$code] ?? '🌐';
        @endphp
        <article class="language-card glass" :class="{ 'is-muted': toggling === @js($code) }">
          <div class="language-card-head">
            <div class="language-identity">
              <span class="language-flag">{{ $flag }}</span>
              <div>
                <p class="language-name">{{ $lang['name'] }}</p>
                <p class="language-code" dir="ltr">{{ $code }}</p>
                @if(in_array($code, ['ar','fa','he','ur']))
                  <span class="badge rtl-badge" style="margin-top:.375rem">RTL</span>
                @endif
              </div>
            </div>
            <button type="button" role="switch" aria-label="{{ $lang['name'] }}" aria-checked="{{ $enabled ? 'true' : 'false' }}"
              class="switch {{ $enabled ? 'is-on' : '' }}" @click="toggle('{{ $code }}', {{ $enabled ? 'false' : 'true' }})">
            </button>
          </div>

          <div class="language-stats">
            <div class="language-stat-row">
              <span class="lang-pct">{{ number_format($totalKeys) }} {{ $tr['keys'] ?? 'keys' }}</span>
              <span class="tabular-nums" dir="ltr">{{ $pct }}%</span>
            </div>
            <div class="language-progress progress-track">
              <div class="progress-fill {{ $pct >= 85 ? 'progress-fill--mint' : ($pct >= 60 ? 'progress-fill--brand' : 'progress-fill--sun') }}" style="width:{{ $pct }}%"></div>
            </div>
          </div>

          <div class="language-card-badges">
            <span class="badge" style="background:color-mix(in oklab,var(--mint) 25%,transparent);color:var(--mint-strong)">
              {{ number_format($lang['translated'] ?? 0) }} {{ $tr['translated'] ?? 'translated' }}
            </span>
            @if(($lang['missing'] ?? 0) > 0)
              <span class="badge badge--missing">{{ number_format($lang['missing']) }} {{ $tr['missing'] ?? 'missing' }}</span>
            @else
              <span class="badge" style="background:color-mix(in oklab,var(--mint) 25%,transparent);color:var(--mint-strong)">✓ {{ $tr['complete'] ?? 'Complete' }}</span>
            @endif
          </div>

          <button class="btn {{ $enabled ? 'btn--primary' : 'btn--ghost' }} language-card-action" type="button"
            {{ $enabled ? '' : 'disabled' }} @click="translateOne('{{ $code }}')">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 5a2 2 0 0 1 3.008-1.728l11.997 6.998a2 2 0 0 1 .003 3.458l-12 7A2 2 0 0 1 5 19z"/></svg>
            <span x-text="translating === '{{ $code }}' ? '{{ addslashes($tr['starting'] ?? 'Starting…') }}' : '{{ addslashes($tr['translate_now'] ?? 'Translate this one now') }}'"></span>
          </button>
        </article>
      @endforeach
    </section>
  @endif
</div>
<script>
function languagesPage() {
  return {
    toggling: '', translating: '', msg: '', msgOk: true,
    async toggle(locale, enabled) {
      this.toggling = locale; this.msg = '';
      try {
        const r = await fetch('{{ url("ai-translator/api/languages/toggle") }}', {
          method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
          body: JSON.stringify({locale, enabled})
        }).then(r=>r.json());
        this.msgOk = !!r.success; this.msg = r.message || (r.success ? 'Updated.' : 'Failed.');
        if (r.success) setTimeout(()=>window.location.reload(), 600);
      } catch(e){ this.msgOk=false; this.msg='Error: '+e.message; }
      this.toggling='';
    },
    async translateOne(lang) {
      if (!lang || this.translating) return;
      this.translating=lang; this.msg='';
      try {
        let prevCompletedAt=null;
        try { const pre=await fetch('{{ url("ai-translator/api/translate/status") }}').then(r=>r.json()); prevCompletedAt=pre.data?.last_completed_at ?? null; } catch {}
        fetch('{{ url("ai-translator/api/translate") }}', {
          method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
          body:JSON.stringify({lang, force:true})
        }).catch(()=>{});
        localStorage.setItem('ai_translator_pending', JSON.stringify({lang, name:lang, started:Date.now(), prevCompletedAt}));
        this.msgOk=true; this.msg= document.documentElement.dir === 'rtl' ? 'جارٍ تشغيل الترجمة في الخلفية' : 'Translation started in the background.';
        setTimeout(()=>window.location.href='{{ route("ai-translator.languages") }}', 700);
      } catch(e){ this.msgOk=false; this.msg='Error: '+e.message; }
      this.translating='';
    },
  };
}
</script>
@endsection
