@extends('ai-translator::layout')
@section('title', '{{ $_trans["languages"] ?? "Languages" }}')

@section('content')
@php $tr = $_trans ?? []; @endphp
<div style="display:flex;flex-direction:column;gap:16px">
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <span style="font-size:13px;color:#5C6678">
      {{ count(array_filter($languages, fn($l) => $l['enabled'])) }} {{ $tr['enabled_of'] ?? 'enabled of' }} {{ count($languages) }}
    </span>
    <button @click="$dispatch('open-add-language')"
      style="display:flex;align-items:center;gap:8px;padding:10px 15px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer">
      + {{ $tr['add_language'] ?? 'Add language' }}
    </button>
  </div>

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
        <div style="margin-inline-start:auto">
          <div style="width:40px;height:22px;border-radius:99px;background:{{ $lang['enabled'] ? '#6EE7B7' : '#2B3446' }};position:relative;cursor:pointer">
            <div style="width:16px;height:16px;border-radius:50%;background:#fff;position:absolute;top:3px;{{ $lang['enabled'] ? 'right:3px' : 'left:3px' }};transition:.2s"></div>
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
@endsection
