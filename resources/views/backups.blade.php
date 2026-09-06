@extends('ai-translator::layout')
@section('title', 'Backups')

@section('content')
<div x-data="backupsPage()" style="display:flex;flex-direction:column;gap:16px">

  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <div style="font-size:13px;color:#5C6678">{{ count($backups) }} backup(s) · keep last {{ $keepCount }}</div>
    <button @click="createBackup()" style="margin-inline-start:auto;display:flex;align-items:center;gap:8px;padding:10px 15px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer">
      <span x-show="creating" style="width:13px;height:13px;border-radius:50%;border:2px solid rgba(6,42,32,.3);border-top-color:#062A20;animation:spin .8s linear infinite;display:inline-block"></span>
      <span x-text="creating ? '...' : t.createBackup">Create backup</span>
    </button>
  </div>

  @if(count($backups) === 0)
  <div style="border:1px dashed #232B3B;border-radius:14px;background:#0E1219;padding:64px 24px;text-align:center">
    <div style="width:52px;height:52px;margin:0 auto 16px;border-radius:14px;background:#141A26;display:grid;place-items:center;color:#3A4761">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="6" rx="8" ry="3"></ellipse><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6"></path></svg>
    </div>
    <div style="font-size:15px;font-weight:600;margin-bottom:6px" x-text="t.emptyBackupsT"></div>
    <div style="font-size:13px;color:#5C6678;margin-bottom:20px" x-text="t.emptyBackupsB"></div>
    <button @click="createBackup()" style="padding:10px 18px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer" x-text="t.createBackup"></button>
  </div>
  @else
  <div style="display:flex;flex-direction:column;gap:8px">
    @foreach($backups as $backup)
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;border:1px solid #1B2130;border-radius:12px;background:#101420;padding:14px 16px">
      <div style="width:34px;height:34px;flex:none;border-radius:10px;background:#161C27;border:1px solid #2B3446;display:grid;place-items:center;color:#5C6678">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="6" rx="8" ry="3"></ellipse><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6"></path></svg>
      </div>
      <div style="min-width:150px">
        <div style="font-family:'JetBrains Mono',monospace;font-size:12.5px;color:#E6E9EF;direction:ltr">{{ $backup['timestamp'] }}</div>
        <div style="font-size:11.5px;color:#5C6678">{{ isset($backup['date']) && $backup['date'] ? $backup['date']->diffForHumans() : '' }}</div>
      </div>
      <span style="display:inline-flex;padding:3px 9px;border-radius:6px;background:rgba(110,231,183,.1);border:1px solid rgba(110,231,183,.25);color:#6EE7B7;font-size:11px">auto</span>
      <span style="font-family:'JetBrains Mono',monospace;font-size:12px;color:#8B93A5;direction:ltr">{{ $backup['size'] > 0 ? round($backup['size']/1024, 1).'KB' : '—' }}</span>
      <div style="margin-inline-start:auto;display:flex;gap:8px">
        <button style="display:flex;align-items:center;gap:7px;padding:7px 13px;border-radius:9px;border:1px solid #2B3446;background:#161C27;color:#8B93A5;font-size:12px;cursor:pointer;white-space:nowrap">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11M7.5 11L12 15.5 16.5 11M5 19h14"></path></svg>
          <span x-text="t.download">Download</span>
        </button>
        <button @click="openRestore('{{ $backup['timestamp'] }}')"
          style="display:flex;align-items:center;gap:7px;padding:7px 13px;border-radius:9px;border:1px solid #3A2E4B;background:rgba(167,139,250,.1);color:#C4B5FD;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"></path><path d="M3 4v5h5"></path></svg>
          <span x-text="t.restore">Restore</span>
        </button>
      </div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- Backup Settings --}}
  <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:20px">
    <div style="font-size:13px;font-weight:600;margin-bottom:16px" x-text="t.backupSettings"></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px">
      <div>
        <label style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px" x-text="t.keepLast"></label>
        <input type="number" x-model="keepLast" min="1" max="50" style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-family:'JetBrains Mono',monospace;font-size:12px;outline:none;direction:ltr" />
        <span style="display:block;font-size:11px;color:#5C6678;margin-top:6px" x-text="t.keepLastHint"></span>
      </div>
      <div style="display:flex;align-items:center;gap:12px;padding:11px 13px;border-radius:10px;border:1px solid #1B2130;background:#0D111A">
        <div style="min-width:0;flex:1">
          <div style="font-size:12.5px;font-weight:500" x-text="t.autoBackup"></div>
          <div style="font-size:11px;color:#5C6678" x-text="t.autoBackupHint"></div>
        </div>
        <button @click="autoBackup = !autoBackup"
          :style="autoBackup ? 'width:40px;height:22px;border-radius:99px;background:#6EE7B7;border:none;cursor:pointer;position:relative' : 'width:40px;height:22px;border-radius:99px;background:#2B3446;border:none;cursor:pointer;position:relative'">
          <span :style="'width:16px;height:16px;border-radius:50%;background:#fff;position:absolute;top:3px;transition:.2s;' + (autoBackup ? 'right:3px' : 'left:3px')"></span>
        </button>
      </div>
    </div>
  </div>

</div>

<script>
function backupsPage() {
  return {
    creating: false,
    keepLast: {{ $keepCount }},
    autoBackup: true,
    async createBackup() {
      this.creating = true;
      const r = await fetch('{{ url("ai-translator/api/backups/create") }}', {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({}),
      }).then(r => r.json());
      this.creating = false;
      if (r.success) window.location.reload();
    },
    openRestore(timestamp) {
      const layout = document.querySelector('[x-data]').__x.$data;
      layout.restoreOpen = true;
      layout.restoreTarget = timestamp;
      layout.restoreMeta = timestamp;
    },
  }
}
</script>
@endsection
