@extends('ai-translator::layout')
@section('title', '{{ $_trans["locked"] ?? "Locked Keys" }}')

@section('content')
@php $tr = $_trans ?? []; @endphp
<div x-data="lockedPage()" style="display:flex;flex-direction:column;gap:16px">

  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <input x-model="search" placeholder="{{ $tr['search_language'] ?? 'Search by key, language or value...' }}"
      style="flex:1;min-width:200px;max-width:380px;padding:9px 13px;border-radius:10px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-size:13px;outline:none">
    <select x-model="filterLang" @change="filterByLang()"
      style="padding:9px 12px;border-radius:10px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-size:13px;outline:none">
      <option value="">{{ $tr['all_languages'] ?? 'All languages' }}</option>
      @foreach(array_filter(config('ai-translator.languages',[]), fn($l) => $l !== config('ai-translator.default_language','en')) as $lang)
      <option value="{{ $lang }}">{{ strtoupper($lang) }}</option>
      @endforeach
    </select>
    <button @click="$dispatch('open-lock-modal')"
      style="margin-inline-start:auto;display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer">
      🔒 {{ $tr['lock_key_title'] ?? 'Lock a Key' }}
    </button>
  </div>

  <div x-show="msg" x-text="msg"
    :style="msgOk ? 'font-size:13px;color:#6EE7B7;padding:8px 12px;border-radius:9px;background:rgba(110,231,183,.08);border:1px solid rgba(110,231,183,.2)' : 'font-size:13px;color:#F87171;padding:8px 12px;border-radius:9px;background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.2)'"></div>

  @if(count($locks) === 0)
  <div style="border:1px dashed #232B3B;border-radius:14px;background:#0E1219;padding:64px 24px;text-align:center">
    <div style="width:52px;height:52px;margin:0 auto 16px;border-radius:14px;background:#141A26;display:grid;place-items:center;color:#3A4761">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
    </div>
    <div style="font-size:15px;font-weight:600;margin-bottom:6px">{{ $tr['no_locked_keys'] ?? 'No locked keys' }}</div>
    <div style="font-size:13px;color:#5C6678;margin-bottom:20px">{{ $tr['lock_hint'] ?? 'Lock a translation to protect it from being overwritten by AI.' }}</div>
    <button @click="$dispatch('open-lock-modal')"
      style="padding:10px 18px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer">
      {{ $tr['lock_first'] ?? 'Lock your first key' }}
    </button>
  </div>

  @else
  <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;min-width:600px">
      <thead>
        <tr style="background:#0E1219;border-bottom:1px solid #1B2130">
          <th style="padding:11px 16px;text-align:start;font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:#5C6678;font-weight:600">{{ $tr['language'] ?? 'Language' }}</th>
          <th style="padding:11px 16px;text-align:start;font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:#5C6678;font-weight:600">{{ $tr['key'] ?? 'Key' }}</th>
          <th style="padding:11px 16px;text-align:start;font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:#5C6678;font-weight:600">{{ $tr['value'] ?? 'Value' }}</th>
          <th style="padding:11px 16px;text-align:start;font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:#5C6678;font-weight:600">{{ $tr['locked_by'] ?? 'Locked by' }}</th>
          <th style="padding:11px 16px;text-align:start;font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:#5C6678;font-weight:600">{{ $tr['reason'] ?? 'Reason' }}</th>
          <th style="padding:11px 16px"></th>
        </tr>
      </thead>
      <tbody>
        @foreach($locks as $lock)
        <tr style="border-top:1px solid #161C27"
          x-show="!search || '{{ strtolower($lock['lang'].' '.$lock['key'].' '.($lock['value']??'')) }}'.includes(search.toLowerCase())">
          <td style="padding:13px 16px">
            <span style="width:28px;height:20px;border-radius:4px;background:#161C27;border:1px solid #2B3446;display:grid;place-items:center;font-family:'JetBrains Mono',monospace;font-size:9.5px;color:#8B93A5;font-weight:600">{{ strtoupper($lock['lang']) }}</span>
          </td>
          <td style="padding:13px 16px">
            <code style="font-family:'JetBrains Mono',monospace;font-size:12px;color:#E6E9EF;background:#0D111A;padding:3px 7px;border-radius:5px;border:1px solid #1B2130">{{ $lock['key'] }}</code>
          </td>
          <td style="padding:13px 16px;font-size:13px;color:#8B93A5;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $lock['value'] ?? '—' }}</td>
          <td style="padding:13px 16px">
            <div style="display:flex;align-items:center;gap:6px">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#5C6678" stroke-width="1.8" stroke-linecap="round" style="flex:none"><rect x="2" y="4" width="20" height="13" rx="2"/><path d="M7 9l3 2.5L7 14M12.5 14H17M8 21h8"/></svg>
              <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:#E6E9EF;direction:ltr">{{ $lock['locked_by'] ?? 'system' }}</span>
            </div>
            @if($lock['locked_at'])
            <div style="font-size:11px;color:#5C6678;margin-top:2px;font-family:'JetBrains Mono',monospace">{{ \Carbon\Carbon::parse($lock['locked_at'])->format('Y-m-d H:i') }}</div>
            @endif
          </td>
          <td style="padding:13px 16px">
            @if($lock['reason'])
            <span style="display:inline-block;padding:3px 9px;border-radius:6px;background:rgba(167,139,250,.1);border:1px solid rgba(167,139,250,.25);color:#C4B5FD;font-size:11px">{{ $lock['reason'] }}</span>
            @else
            <span style="color:#3A4761;font-size:12px">—</span>
            @endif
          </td>
          <td style="padding:13px 16px;text-align:end">
            <button @click="unlock('{{ $lock['lang'] }}', '{{ $lock['key'] }}')"
              :disabled="unlocking === '{{ $lock['lang'] }}.{{ $lock['key'] }}'"
              style="padding:6px 14px;border-radius:8px;border:1px solid #2B3446;background:#161C27;color:#8B93A5;font-size:12px;cursor:pointer">
              <span x-show="unlocking === '{{ $lock['lang'] }}.{{ $lock['key'] }}'">...</span>
              <span x-show="unlocking !== '{{ $lock['lang'] }}.{{ $lock['key'] }}'">{{ $tr['unlock'] ?? 'Unlock' }}</span>
            </button>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div style="font-size:12px;color:#5C6678">{{ count($locks) }} {{ $tr['locked_keys'] ?? 'locked key(s)' }}</div>
  @endif

</div>

<script>
function lockedPage() {
  return {
    search: '', filterLang: '', unlocking: '', msg: '', msgOk: true,
    filterByLang() {
      const lang = this.filterLang;
      window.location.href = '{{ route("ai-translator.locked") }}' + (lang ? '?lang=' + lang : '');
    },
    async unlock(lang, key) {
      this.unlocking = lang + '.' + key;
      this.msg = '';
      try {
        const r = await fetch('{{ url("ai-translator/api/unlock") }}', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
          body: JSON.stringify({ lang, key }),
        }).then(r => r.json());
        this.msgOk = r.success;
        this.msg   = r.message || (r.success ? '{{ addslashes($tr["unlock"] ?? "Unlocked") }}.' : 'Failed.');
        if (r.success) setTimeout(() => window.location.reload(), 800);
      } catch (e) { this.msgOk = false; this.msg = 'Error: ' + e.message; }
      this.unlocking = '';
    },
  };
}
</script>
@endsection
