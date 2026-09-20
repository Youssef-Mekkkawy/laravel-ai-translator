{{-- Toasts --}}
<div
  style="position:fixed;bottom:24px;inset-inline-end:24px;z-index:80;display:flex;flex-direction:column;gap:10px;pointer-events:none">
  <template x-for="toast in toasts" :key="toast.id">
    <div
      style="display:flex;align-items:center;gap:12px;min-width:260px;max-width:360px;padding:13px 16px;border-radius:12px;background:color-mix(in oklab,var(--background) 94%, var(--panel));border:1px solid color-mix(in oklab,var(--panel) 65%,transparent);box-shadow:0 16px 40px rgba(0,0,0,.35);animation:tin .22s ease">
      <span
        :style="'width:8px;height:8px;flex:none;border-radius:50%;background:' + (toast.kind === 'err' ? 'var(--coral)' : 'var(--mint)')"></span>
      <div>
        <div style="font-size:13px;font-weight:600" x-text="toast.title"></div>
        <div style="font-size:12px;opacity:.6;margin-top:2px" x-text="toast.body" x-show="toast.body"></div>
      </div>
    </div>
  </template>
</div>