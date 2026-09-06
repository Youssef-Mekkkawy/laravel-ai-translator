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
    <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:18px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
        <span style="width:32px;height:22px;border-radius:5px;background:#161C27;border:1px solid #2B3446;display:grid;place-items:center;font-family:'JetBrains Mono',monospace;font-size:10px;color:#8B93A5">{{ strtoupper($lang['code']) }}</span>
        <div>
          <div style="font-weight:600;font-size:13.5px">{{ $lang['name'] }}</div>
          <div style="font-size:11.5px;color:#5C6678">{{ $lang['nativeName'] }}</div>
        </div>
        {{-- Toggle --}}
        <div style="margin-inline-start:auto"
          @click="toggle('{{ $lang['code'] }}', {{ $lang['enabled'] ? 'false' : 'true' }})"
          :style="toggling === '{{ $lang['code'] }}' ? 'opacity:.5;cursor:wait;pointer-events:none' : 'cursor:pointer'">
          <div style="width:42px;height:24px;border-radius:99px;background:{{ $lang['enabled'] ? '#6EE7B7' : '#2B3446' }};position:relative;transition:background .2s;flex-shrink:0">
            <div style="width:18px;height:18px;border-radius:50%;background:#fff;position:absolute;top:3px;{{ $lang['enabled'] ? 'right:3px' : 'left:3px' }};transition:left .2s,right .2s;box-shadow:0 1px 3px rgba(0,0,0,.3)"></div>
          </div>
        </div>
      </div>
      <div style="height:5px;border-radius:99px;background:#161C27;overflow:hidden;margin-bottom:12px">
        <div style="height:100%;border-radius:99px;background:{{ $lang['pct'] >= 95 ? '#6EE7B7' : ($lang['pct'] >= 70 ? '#38bdf8' : '#FBBF24') }};width:{{ $lang['pct'] }}%;transition:width .3s"></div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;font-size:12.5px;color:#8B93A5">
        <span>{{ number_format($totalKeys) }} {{ $tr['keys'] ?? 'keys' }}</span>
        <span style="color:#6EE7B7;font-weight:600">{{ $lang['pct'] }}%</span>
        @if($lang['missing'] > 0)
        <span style="color:#FBBF24">{{ number_format($lang['missing']) }} {{ $tr['missing'] ?? 'Missing' }}</span>
        @else
        <span style="color:#6EE7B7">✓ {{ $tr['complete'] ?? 'Complete' }}</span>
        @endif
      </div>
    </div>
    @endforeach
  </div>
  @endif
</div>

<script>
function languagesPage() {
  return {
    toggling: '',
    msg:      '',
    msgOk:    true,

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