@extends('ai-translator::layout')
@section('title', 'Backups')

@section('content')
<style>
.backup-row { display:flex; align-items:center; gap:14px; flex-wrap:wrap; border:1px solid #1B2130; border-radius:12px; background:#101420; padding:14px 16px; }
.backup-icon { width:34px; height:34px; flex:none; border-radius:10px; background:#161C27; border:1px solid #2B3446; display:grid; place-items:center; color:#5C6678; }
.backup-time { font-family:'JetBrains Mono',monospace; font-size:12.5px; color:#E6E9EF; direction:ltr; }
.backup-ago  { font-size:11.5px; color:#5C6678; }
.backup-size { font-family:'JetBrains Mono',monospace; font-size:12px; color:#8B93A5; direction:ltr; }
.backup-actions { margin-inline-start:auto; display:flex; gap:8px; }
.btn-sm { display:flex; align-items:center; gap:6px; padding:7px 13px; border-radius:9px; font-size:12px; cursor:pointer; white-space:nowrap; }
.btn-ghost  { border:1px solid #2B3446; background:#161C27; color:#8B93A5; }
.btn-purple { border:1px solid rgba(167,139,250,.4); background:rgba(167,139,250,.1); color:#C4B5FD; font-weight:600; }
</style>

<div x-data="backupsPage()" style="display:flex;flex-direction:column;gap:16px">

  {{-- Toolbar --}}
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <div style="font-size:13px;color:#5C6678">
      {{ count($backups) }} backup(s) · keep last {{ $keepCount }}
    </div>
    <button @click="createBackup()"
      style="margin-inline-start:auto;display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer">
      <span x-show="creating" style="width:13px;height:13px;border-radius:50%;border:2px solid rgba(6,42,32,.3);border-top-color:#062A20;animation:spin .8s linear infinite;display:inline-block;flex-shrink:0"></span>
      <span x-text="creating ? 'Creating...' : t.createBackup"></span>
    </button>
  </div>

  {{-- Result message --}}
  <div x-show="msg" x-text="msg"
    :style="msgOk ? 'font-size:13px;color:#6EE7B7;padding:8px 12px;border-radius:9px;background:rgba(110,231,183,.08);border:1px solid rgba(110,231,183,.2)' : 'font-size:13px;color:#F87171;padding:8px 12px;border-radius:9px;background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.2)'">
  </div>

  {{-- Empty state --}}
  @if(count($backups) === 0)
  <div style="border:1px dashed #232B3B;border-radius:14px;background:#0E1219;padding:64px 24px;text-align:center">
    <div style="width:52px;height:52px;margin:0 auto 16px;border-radius:14px;background:#141A26;display:grid;place-items:center;color:#3A4761">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/></svg>
    </div>
    <div style="font-size:15px;font-weight:600;margin-bottom:6px" x-text="t.emptyBackupsT"></div>
    <div style="font-size:13px;color:#5C6678;margin-bottom:20px" x-text="t.emptyBackupsB"></div>
    <button @click="createBackup()"
      style="padding:10px 18px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer"
      x-text="t.createBackup"></button>
  </div>

  @else
  <div style="display:flex;flex-direction:column;gap:8px">
    @foreach($backups as $i => $backup)
    @php
      $ts   = $backup['timestamp'] ?? '';
      $date = isset($backup['date']) && $backup['date'] ? $backup['date'] : null;
      $size = $backup['size'] ?? 0;
      $sizeStr = $size > 0 ? round($size / 1024, 1) . ' KB' : '—';
    @endphp
    <div class="backup-row">
      <div class="backup-icon">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6"/></svg>
      </div>

      <div>
        <div class="backup-time">{{ $ts }}</div>
        @if($date)
        <div class="backup-ago">{{ \Carbon\Carbon::parse($date)->diffForHumans() }}</div>
        @endif
      </div>

      <span style="display:inline-flex;padding:3px 9px;border-radius:6px;background:rgba(110,231,183,.1);border:1px solid rgba(110,231,183,.25);color:#6EE7B7;font-size:11px">
        auto
      </span>

      <span class="backup-size">{{ $sizeStr }}</span>

      <div class="backup-actions">
        <button class="btn-sm btn-ghost" title="Download (coming soon)" disabled style="opacity:.5;cursor:not-allowed">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11M7.5 11L12 15.5 16.5 11M5 19h14"/></svg>
          <span x-text="t.download"></span>
        </button>
        <button class="btn-sm btn-purple"
          @click="$dispatch('open-restore', { timestamp: '{{ $ts }}' })">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
          <span x-text="t.restore"></span>
        </button>
      </div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- Backup settings --}}
  <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:20px;margin-top:8px">
    <div style="font-size:13px;font-weight:600;margin-bottom:16px" x-text="t.backupSettings"></div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
      <div>
        <label style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px" x-text="t.keepLast + ' X backups'">Keep last</label>
        <input type="number" x-model="keepLast" min="1" max="50"
          style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-family:'JetBrains Mono',monospace;font-size:13px;outline:none;direction:ltr">
        <span style="display:block;font-size:11px;color:#5C6678;margin-top:6px" x-text="t.keepLastHint"></span>
      </div>
      <div style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:10px;border:1px solid #1B2130;background:#0D111A">
        <div style="flex:1;min-width:0">
          <div style="font-size:13px;font-weight:500" x-text="t.autoBackup"></div>
          <div style="font-size:11px;color:#5C6678;margin-top:2px" x-text="t.autoBackupHint"></div>
        </div>
        <button @click="autoBackup = !autoBackup"
          :style="'width:40px;height:22px;border-radius:99px;border:none;cursor:pointer;position:relative;transition:.2s;background:' + (autoBackup ? '#6EE7B7' : '#2B3446')">
          <span :style="'width:16px;height:16px;border-radius:50%;background:#fff;position:absolute;top:3px;transition:.2s;' + (autoBackup ? 'right:3px' : 'left:3px')"></span>
        </button>
      </div>
    </div>
  </div>

</div>

<script>
function backupsPage() {
  return {
    creating:   false,
    keepLast:   {{ $keepCount }},
    autoBackup: true,
    msg:        '',
    msgOk:      true,

    async createBackup() {
      this.creating = true;
      this.msg = '';
      try {
        const r = await fetch('{{ url("ai-translator/api/backups/create") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
          },
          body: JSON.stringify({}),
        }).then(r => r.json());

        this.msgOk = r.success;
        this.msg   = r.success ? 'Backup created successfully.' : (r.message || 'Failed to create backup.');

        if (r.success) {
          setTimeout(() => window.location.reload(), 800);
        }
      } catch (e) {
        this.msgOk = false;
        this.msg   = 'Network error: ' + e.message;
      }
      this.creating = false;
    },
  };
}
</script>
@endsection
