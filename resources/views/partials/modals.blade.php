{{-- Global modals. State (restoreOpen, lockOpen, addOpen, ...) lives in dashboard() in dashboard.js.
     Styles: .dlg-* classes in dashboard.css. --}}

{{-- Restore backup --}}
<div class="modal-backdrop" x-show="restoreOpen" x-cloak @click.self="restoreOpen = false">
  <div class="modal-box">
    <div class="dlg-head">
      <div class="dlg-title">{{ $trans['restore_title'] ?? 'Restore Backup' }}</div>
      <button type="button" class="dlg-close" aria-label="Close" @click="restoreOpen = false">×</button>
    </div>
    <div class="dlg-body">
      <p class="dlg-hint">{{ $trans['restore_warn'] ?? 'This will replace all current language files.' }}</p>
      <div class="dlg-code" x-text="restoreTarget"></div>
    </div>
    <div class="dlg-actions">
      <button type="button" class="btn btn--ghost" @click="restoreOpen = false">{{ $trans['cancel'] ?? 'Cancel' }}</button>
      <button type="button" class="btn btn--sun" @click="confirmRestore()">{{ $trans['restore_confirm'] ?? 'Yes, restore' }}</button>
    </div>
  </div>
</div>

{{-- Lock key --}}
<div class="modal-backdrop" x-show="lockOpen" x-cloak @click.self="lockOpen = false">
  <div class="modal-box">
    <div class="dlg-head">
      <div class="dlg-title">{{ $trans['lock_key_title'] ?? 'Lock a Key' }}</div>
      <button type="button" class="dlg-close" aria-label="Close" @click="lockOpen = false">×</button>
    </div>
    <div class="dlg-body">
      <div class="dlg-grid">
        <label>
          <span class="dlg-label">{{ $trans['language'] ?? 'Language' }}</span>
          <select class="dlg-input" x-model="lockLang">
            @foreach($configuredLangs as $l)
              <option value="{{ $l['code'] }}">{{ $l['label'] }}</option>
            @endforeach
          </select>
        </label>
        <label>
          <span class="dlg-label">{{ $trans['key'] ?? 'Key' }}</span>
          <input class="dlg-input dlg-input--mono" x-model="lockKey" placeholder="auth.login">
        </label>
      </div>
      <label>
        <span class="dlg-label">{{ $trans['reason_optional'] ?? 'Reason (optional)' }}</span>
        <input class="dlg-input" x-model="lockReason" placeholder="{{ $trans['reason_placeholder'] ?? 'e.g. Client preferred term' }}">
      </label>
    </div>
    <div class="dlg-actions">
      <button type="button" class="btn btn--ghost" @click="lockOpen = false">{{ $trans['cancel'] ?? 'Cancel' }}</button>
      <button type="button" class="btn btn--primary" @click="confirmLock()" :disabled="!lockKey.trim()">{{ $trans['lock_key_confirm'] ?? 'Lock Key' }}</button>
    </div>
  </div>
</div>

{{-- Add language --}}
<div class="modal-backdrop" x-show="addOpen" x-cloak @click.self="addOpen = false">
  <div class="modal-box">
    <div class="dlg-head">
      <div class="dlg-title">{{ $trans['add_language'] ?? 'Add language' }}</div>
      <button type="button" class="dlg-close" aria-label="Close" @click="addOpen = false">×</button>
    </div>
    <div class="dlg-body dlg-body--tight">
      <input class="dlg-input dlg-input--search" x-model="addQuery" placeholder="{{ $trans['search_language'] ?? 'Search languages...' }}">
      <div class="dlg-list">
        <template x-for="opt in filteredAddOptions" :key="opt.code">
          <button type="button" class="dlg-lang" @click="pickLanguage(opt)">
            <span class="dlg-lang-code" x-text="opt.code"></span>
            <span class="dlg-lang-name" x-text="opt.name"></span>
            <span class="dlg-lang-native" x-text="opt.native"></span>
          </button>
        </template>
      </div>
    </div>
  </div>
</div>
