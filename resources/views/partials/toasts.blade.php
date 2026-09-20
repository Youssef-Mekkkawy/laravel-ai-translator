{{-- Toast stack. Toasts are pushed by dashboard().toast(); styles: .toast-* in dashboard.css. --}}
<div class="toast-stack">
  <template x-for="toast in toasts" :key="toast.id">
    <div class="toast">
      <span class="toast-dot" :class="toast.kind === 'err' ? 'toast-dot--err' : ''"></span>
      <div>
        <div class="toast-title" x-text="toast.title"></div>
        <div class="toast-body" x-text="toast.body" x-show="toast.body"></div>
      </div>
    </div>
  </template>
</div>
