{{-- Lock key modal --}}
<div class="modal-backdrop" x-show="lockOpen" x-cloak @click.self="lockOpen = false">
    <div class="modal-box">
        <div
            style="display:flex;align-items:center;padding:18px 20px;border-bottom:1px solid color-mix(in oklab,var(--panel) 55%,transparent)">
            <div style="font-size:14px;font-weight:600;flex:1">{{ $_trans['lock_key_title'] ?? 'Lock a Key' }}</div>
            <button type="button" @click="lockOpen = false"
                style="width:28px;height:28px;display:grid;place-items:center;border-radius:8px;background:color-mix(in oklab,var(--ink) 5%,transparent)">×</button>
        </div>
        <div style="padding:18px 20px;display:flex;flex-direction:column;gap:14px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <label>
                    <span
                        style="display:block;font-size:12px;color:color-mix(in oklab,var(--ink) 58%,transparent);margin-bottom:7px">{{ $_trans['language'] ?? 'Language' }}</span>
                    <select x-model="lockLang"
                        style="width:100%;height:38px;padding:0 12px;border-radius:9px;border:1px solid var(--border);background:color-mix(in oklab,var(--panel) 65%,transparent);color:var(--ink);outline:none">
                        @foreach(array_filter($cfgLangs ?? [], fn($l) => $l !== $cfgSource) as $l)
                            <option value="{{ $l }}">{{ strtoupper($l) }}</option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span
                        style="display:block;font-size:12px;color:color-mix(in oklab,var(--ink) 58%,transparent);margin-bottom:7px">{{ $_trans['key'] ?? 'Key' }}</span>
                    <input x-model="lockKey" placeholder="auth.login"
                        style="width:100%;height:38px;padding:0 12px;border-radius:9px;border:1px solid var(--border);background:color-mix(in oklab,var(--panel) 65%,transparent);color:var(--ink);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;outline:none;direction:ltr">
                </label>
            </div>
            <label>
                <span
                    style="display:block;font-size:12px;color:color-mix(in oklab,var(--ink) 58%,transparent);margin-bottom:7px">{{ $_trans['reason_optional'] ?? 'Reason (optional)' }}</span>
                <input x-model="lockReason"
                    placeholder="{{ $_trans['reason_placeholder'] ?? 'e.g. Client preferred term' }}"
                    style="width:100%;height:38px;padding:0 12px;border-radius:9px;border:1px solid var(--border);background:color-mix(in oklab,var(--panel) 65%,transparent);color:var(--ink);font-size:13px;outline:none">
            </label>
        </div>
        <div style="display:flex;gap:10px;justify-content:flex-end;padding:0 20px 18px">
            <button type="button" class="btn btn--ghost"
                @click="lockOpen = false">{{ $_trans['cancel'] ?? 'Cancel' }}</button>
            <button type="button" class="btn btn--primary" @click="confirmLock()"
                :disabled="!lockKey.trim()">{{ $_trans['lock_key_confirm'] ?? 'Lock Key' }}</button>
        </div>
    </div>
</div>