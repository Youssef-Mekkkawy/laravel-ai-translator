@extends('ai-translator::layout')
@section('title', '{{ $_trans["backups"] ?? "Backups" }}')
@section('content')
@php $tr=$_trans??[]; @endphp
<div x-data="backupsPage()" class="page-stack">
  <section class="page-intro glass--strong">
    <div class="backup-header">
      <div><p class="section-title">{{ $tr['backups'] ?? 'Backups' }}</p><p class="section-subtitle">{{ $tr['backups_sub'] ?? 'Snapshots of your language files, so any run can be undone.' }}</p></div>
      <button class="btn btn--primary" @click="createBackup()" :disabled="creating">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
        <span x-text="creating ? '{{ addslashes($tr['saving']??'Creating…') }}' : '{{ addslashes($tr['create_backup']??'Create backup now') }}'"></span>
      </button>
    </div>
    <div style="margin-top:.75rem;font-size:.6875rem;color:color-mix(in oklab,var(--ink) 50%,transparent)">{{ count($backups) }} {{ $tr['backups']??'backup(s)' }} · {{ $tr['keep_last']??'keep last' }} {{ $keepCount }}</div>
    <div x-show="msg" x-text="msg" class="page-message" :class="msgOk ? 'page-message--ok' : 'page-message--err'"></div>
  </section>

  <section class="backup-table-panel glass">
    @if(count($backups)===0)
      <div class="empty-state"><div class="empty-state-icon">▤</div><p class="empty-state-title">{{ $tr['empty_backups_t']??'No backups yet' }}</p><p class="empty-state-text">{{ $tr['empty_backups_b']??'A backup is created automatically before every translation run.' }}</p><button class="btn btn--primary" @click="createBackup()">{{ $tr['create_backup']??'Create backup' }}</button></div>
    @else
      <div class="table-scroll">
        <table class="backup-table"><thead><tr><th>{{ $tr['created']??'Created' }}</th><th>{{ $tr['size']??'Size' }}</th><th>{{ $tr['files']??'Files' }}</th><th>{{ $tr['triggered_by']??'Triggered by' }}</th><th></th></tr></thead>
        <tbody>
        @foreach($backups as $backup)
          @php $ts=$backup['timestamp']??''; $size=$backup['size']??0; $sizeStr=$size>1024*1024?round($size/1024/1024,1).' MB':($size>0?round($size/1024,1).' KB':'—'); $fileCount=$backup['files']??$backup['file_count']??'—'; $trigger=$backup['triggered_by']??$backup['trigger']??'Automatic'; @endphp
          <tr><td dir="ltr" class="font-mono">{{ $ts }}</td><td dir="ltr" class="font-mono">{{ $sizeStr }}</td><td>{{ $fileCount }}</td><td><span class="badge" style="background:color-mix(in oklab,var(--mint) 22%,transparent);color:var(--mint-strong)">{{ $trigger }}</span></td><td><div class="backup-actions"><button class="table-btn" type="button" disabled style="opacity:.45;cursor:not-allowed">{{ $tr['download']??'Download' }}</button><button class="table-btn" type="button" style="background:color-mix(in oklab,var(--brand) 14%,transparent);color:var(--brand)" @click="$dispatch('open-restore',{timestamp:'{{ addslashes($ts) }}'})">{{ $tr['restore']??'Restore' }}</button></div></td></tr>
        @endforeach
        </tbody></table>
      </div>
    @endif
  </section>

  <section class="settings-panel glass">
    <div class="settings-section-head"><h2 class="settings-section-title">{{ $tr['backup_settings']??'Backup settings' }}</h2></div>
    <div class="settings-grid">
      <div class="setting-block"><label class="setting-label">{{ $tr['keep_last']??'Keep last backups' }}</label><input class="setting-input" type="number" x-model.number="keepLast" min="1" max="50" dir="ltr"><p class="setting-help">{{ $tr['keep_last_hint']??'Older backups are deleted automatically.' }}</p></div>
      <div class="toggle-setting"><div class="toggle-text"><p class="toggle-title">{{ $tr['auto_backup']??'Auto-backup before every translate run' }}</p><p class="toggle-description">{{ $tr['auto_backup_hint']??'A snapshot is taken before any file is written.' }}</p></div><button class="toggle" :class="autoBackup ? 'is-on' : ''" role="switch" :aria-checked="autoBackup" @click="autoBackup=!autoBackup"></button></div>
    </div>
    <div class="settings-actions" style="justify-content:flex-start"><button class="btn btn--primary" @click="saveSettings()" :disabled="savingSettings"><span x-text="savingSettings?'{{ addslashes($tr['saving']??'Saving…') }}':'{{ addslashes($tr['save']??'Save settings') }}'"></span></button><span x-show="settingsMsg" x-text="settingsMsg" :style="settingsMsgOk?'color:var(--mint-strong);font-size:.75rem':'color:var(--coral);font-size:.75rem'"></span></div>
  </section>
</div>
<script>
function backupsPage(){return{creating:false,keepLast:{{ $keepCount }},autoBackup:true,msg:'',msgOk:true,savingSettings:false,settingsMsg:'',settingsMsgOk:true,async createBackup(){this.creating=true;this.msg='';try{const r=await fetch('{{ url("ai-translator/api/backups/create") }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({})}).then(r=>r.json());this.msgOk=!!r.success;this.msg=r.message||(r.success?'Backup created.':'Failed.');if(r.success)setTimeout(()=>location.reload(),800)}catch(e){this.msgOk=false;this.msg='Error: '+e.message}this.creating=false},async saveSettings(){this.savingSettings=true;this.settingsMsg='';try{const r=await fetch('{{ url("ai-translator/api/settings") }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({backup_keep:parseInt(this.keepLast),backup_auto:this.autoBackup})}).then(r=>r.json());this.settingsMsgOk=!!r.success;this.settingsMsg=r.message||(r.success?'Saved.':'Failed.');setTimeout(()=>this.settingsMsg='',3000)}catch(e){this.settingsMsgOk=false;this.settingsMsg='Error: '+e.message}this.savingSettings=false}}}
</script>
@endsection
