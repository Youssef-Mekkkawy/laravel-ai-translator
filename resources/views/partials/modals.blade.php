{{-- Restore backup modal --}}

<div class="modal-backdrop" x-show="restoreOpen" x-cloak @click.self="restoreOpen = false">
  <div class="modal-box">
    <div
      style="display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid color-mix(in oklab,var(--panel) 55%,transparent)">
      <div style="font-size:14px;font-weight:600;flex:1">{{ $_trans['restore_title'] ?? 'Restore Backup' }}</div>
      <button type="button" @click="restoreOpen = false"
        style="width:28px;height:28px;display:grid;place-items:center;border-radius:8px;background:color-mix(in oklab,var(--ink) 5%,transparent)">×</button>
    </div>
    <div style="padding:18px 20px">
      <p style="font-size:13px;color:color-mix(in oklab,var(--ink) 60%,transparent);margin-bottom:14px">
        {{ $_trans['restore_warn'] ?? 'This will replace all current language files.' }}</p>
      <div
        style="padding:12px 14px;border-radius:10px;background:color-mix(in oklab,var(--panel) 48%,transparent);border:1px solid color-mix(in oklab,var(--panel) 60%,transparent);margin-bottom:18px">
        <div style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;direction:ltr"
          x-text="restoreTarget"></div>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end">
        <button type="button" class="btn btn--ghost"
          @click="restoreOpen = false">{{ $_trans['cancel'] ?? 'Cancel' }}</button>
        <button type="button" class="btn" style="background:var(--sun);color:#2a1d02"
          @click="confirmRestore()">{{ $_trans['restore_confirm'] ?? 'Yes, restore' }}</button>
      </div>
    </div>
  </div>
</div>