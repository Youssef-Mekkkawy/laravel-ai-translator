@extends('ai-translator::layout')
@section('title', '{{ $_trans["languages"] ?? "Languages" }}')

@section('content')
@php $tr = $_trans ?? []; @endphp
<div x-data="languagesPage()" style="display:flex;flex-direction:column;gap:16px">

  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <span style="font-size:13px;color:#5C6678">
      {{ count(array_filter($languages, fn($l) => $l['enabled'])) }} {{ $tr['enabled_of'] ?? 'enabled of' }} {{ count($languages) }}
    </span>
    <button @click="$dispatch('open-add-language')"
      style="display:flex;align-items:center;gap:8px;padding:10px 15px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer">
      + {{ $tr['add_language'] ?? 'Add language' }}
    </button>
  </div>

  <div x-show="msg" x-text="msg"
    :style="msgOk ? 'font-size:13px;color:#6EE7B7;padding:8px 12px;border-radius:9px;background:rgba(110,231,183,.08);border:1px solid rgba(110,231,183,.2)' : 'font-size:13px;color:#F87171;padding:8px 12px;border-radius:9px;background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.2)'"></div>

  @if(count($languages) === 0)
  <div style="border:1px dashed #232B3B;border-radius:14px;background:#0E1219;padding:64px 24px;text-align:center">
    <div style="font-size:15px;font-weight:600;margin-bottom:8px">{{ $tr['no_languages'] ?? 'No languages configured' }}</div>
    <div style="font-size:13px;color:#5C6678;margin-bottom:20px">{{ $tr['add_config_hint'] ?? 'Add languages in config/ai-translator.php' }}</div>
  </div>
  @else
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
    @foreach($languages as $lang)
    <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:18px;position:relative">

      {{-- Remove button (top right) --}}
      <button
        @click="confirmRemove('{{ $lang['code'] }}')"
        style="position:absolute;top:12px;right:12px;width:26px;height:26px;border-radius:7px;border:1px solid #2B3446;background:#161C27;color:#5C6678;cursor:pointer;display:grid;place-items:center;transition:all .15s"
        onmouseover="this.style.borderColor='#F87171';this.style.color='#F87171'"
        onmouseout="this.style.borderColor='#2B3446';this.style.color='#5C6678'"
        title="{{ $tr['remove'] ?? 'Remove language' }}">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
      </button>

      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;padding-inline-end:30px">
        <span style="width:32px;height:22px;border-radius:5px;background:#161C27;border:1px solid #2B3446;display:grid;place-items:center;font-family:'JetBrains Mono',monospace;font-size:10px;color:#8B93A5">{{ strtoupper($lang['code']) }}</span>
        <div>
          <div style="font-weight:600;font-size:13.5px">{{ $lang['name'] }}</div>
          <div style="font-size:11.5px;color:#5C6678">{{ $lang['nativeName'] }}</div>
        </div>
        {{-- Toggle (enable/disable) --}}
        <div style="margin-inline-start:auto"
          @click="toggle('{{ $lang['code'] }}', {{ $lang['enabled'] ? 'false' : 'true' }})"
          :style="toggling === '{{ $lang['code'] }}' ? 'opacity:.5;cursor:wait;pointer-events:none' : 'cursor:pointer'">
          <div style="width:42px;height:24px;border-radius:99px;background:{{ $lang['enabled'] ? '#6EE7B7' : '#2B3446' }};position:relative;transition:background .2s;flex-shrink:0">
            <div style="width:18px;height:18px;border-radius:50%;background:#fff;position:absolute;top:3px;{{ $lang['enabled'] ? 'right:3px' : 'left:3px' }};transition:left .2s,right .2s;box-shadow:0 1px 3px rgba(0,0,0,.3)"></div>
          </div>
        </div>
      </div>

      <div style="height:5px;border-radius:99px;background:#161C27;overflow:hidden;margin-bottom:12px;opacity:{{ $lang['enabled'] ? '1' : '0.4' }}">
        <div style="height:100%;border-radius:99px;background:{{ $lang['pct'] >= 95 ? '#6EE7B7' : ($lang['pct'] >= 70 ? '#38bdf8' : '#FBBF24') }};width:{{ $lang['pct'] }}%;transition:width .3s"></div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;font-size:12.5px;color:#8B93A5;opacity:{{ $lang['enabled'] ? '1' : '0.5' }}">
        <span>{{ number_format($totalKeys) }} {{ $tr['keys'] ?? 'keys' }}</span>
        <span style="color:#6EE7B7;font-weight:600">{{ $lang['pct'] }}%</span>
        @if($lang['missing'] > 0)
        <span style="color:#FBBF24">{{ number_format($lang['missing']) }} {{ $tr['missing'] ?? 'Missing' }}</span>
        @else
        <span style="color:#6EE7B7">✓ {{ $tr['complete'] ?? 'Complete' }}</span>
        @endif
      </div>

      @if(!$lang['enabled'])
      <div style="margin-top:8px;font-size:11px;color:#5C6678;padding:4px 8px;border-radius:6px;background:#0D111A;display:inline-block">
        {{ $tr['disabled'] ?? 'Disabled — will be skipped during translation' }}
      </div>
      @endif
    </div>
    @endforeach
  </div>
  @endif

  {{-- Remove confirmation modal --}}
  <div x-show="removeOpen" x-cloak
    style="position:fixed;inset:0;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;z-index:100"
    @click.self="removeOpen=false">
    <div style="background:#101420;border:1px solid #1B2130;border-radius:16px;padding:24px;max-width:420px;width:90%">
      <div style="font-size:15px;font-weight:600;margin-bottom:8px">{{ $tr['remove_language'] ?? 'Remove language' }}?</div>
      <div style="font-size:13px;color:#8B93A5;margin-bottom:20px">
        {{ $tr['remove_language_warn'] ?? 'This will remove the language from your config. Translation files will not be deleted.' }}
        <span style="font-family:\'JetBrains Mono\',monospace;color:#F87171;font-weight:600" x-text="removeTarget.toUpperCase()"></span>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button @click="removeOpen=false"
          style="padding:9px 16px;border-radius:10px;border:1px solid #2B3446;background:transparent;color:#8B93A5;font-size:13px;cursor:pointer">
          {{ $tr['cancel'] ?? 'Cancel' }}
        </button>
        <button @click="removeLanguage()"
          style="padding:9px 16px;border-radius:10px;border:1px solid #F87171;background:rgba(248,113,113,.1);color:#F87171;font-size:13px;font-weight:600;cursor:pointer">
          {{ $tr['remove'] ?? 'Remove' }}
        </button>
      </div>
    </div>
  </div>

</div>

<script>
function languagesPage() {
  return {
    toggling:     '',
    removeOpen:   false,
    removeTarget: '',
    msg:          '',
    msgOk:        true,

    confirmRemove(locale) {
      this.removeTarget = locale;
      this.removeOpen   = true;
    },

    async removeLanguage() {
      this.removeOpen = false;
      const locale    = this.removeTarget;
      this.msg        = '';
      try {
        const r = await fetch('{{ url("ai-translator/api/languages/remove") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
          },
          body: JSON.stringify({ locale }),
        }).then(r => r.json());

        this.msgOk = r.success;
        this.msg   = r.message || (r.success ? 'Language removed.' : 'Failed.');
        if (r.success) setTimeout(() => window.location.reload(), 600);
      } catch (e) {
        this.msgOk = false;
        this.msg   = 'Error: ' + e.message;
      }
    },

    async toggle(locale, enable) {
      this.toggling = locale;
      this.msg      = '';
      try {
        const r = await fetch('{{ url("ai-translator/api/languages/toggle") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
          },
          body: JSON.stringify({ locale, enabled: enable }),
        }).then(r => r.json());

        this.msgOk = r.success;
        this.msg   = r.message || (r.success ? 'Updated.' : 'Failed.');
        if (r.success) setTimeout(() => window.location.reload(), 600);
      } catch (e) {
        this.msgOk = false;
        this.msg   = 'Error: ' + e.message;
      }
      this.toggling = '';
    },
  };
}
</script>
@endsection
