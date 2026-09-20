{{-- Add language modal --}}
<div class="modal-backdrop" x-show="addOpen" x-cloak @click.self="addOpen = false">
    <div class="modal-box">
        <div
            style="display:flex;align-items:center;gap:12px;padding:18px 20px;border-bottom:1px solid color-mix(in oklab,var(--panel) 55%,transparent)">
            <div style="font-size:14px;font-weight:600;flex:1">{{ $_trans['add_language'] ?? 'Add language' }}</div>
            <button type="button" @click="addOpen = false"
                style="width:28px;height:28px;display:grid;place-items:center;border-radius:8px;background:color-mix(in oklab,var(--ink) 5%,transparent)">×</button>
        </div>
        <div style="padding:16px 20px 20px">
            <input x-model="addQuery" placeholder="{{ $_trans['search_language'] ?? 'Search languages...' }}"
                style="width:100%;height:40px;padding:0 12px;border-radius:9px;border:1px solid var(--border);background:color-mix(in oklab,var(--panel) 65%,transparent);color:var(--ink);font-size:13px;outline:none;margin-bottom:10px">
            <div style="display:flex;flex-direction:column;gap:6px;max-height:260px;overflow-y:auto">
                <template x-for="opt in filteredAddOptions" :key="opt.code">
                    <button type="button" @click="pickLanguage(opt)"
                        style="display:flex;align-items:center;gap:10px;width:100%;padding:10px 12px;border-radius:9px;border:1px solid color-mix(in oklab,var(--panel) 60%,transparent);background:color-mix(in oklab,var(--panel) 55%,transparent);color:var(--ink);text-align:start">
                        <span
                            style="width:32px;height:22px;flex:none;border-radius:5px;background:color-mix(in oklab,var(--panel) 70%,transparent);border:1px solid color-mix(in oklab,var(--panel) 65%,transparent);display:grid;place-items:center;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:10px"
                            x-text="opt.code"></span>
                        <span style="font-size:13px;font-weight:500" x-text="opt.name"></span>
                        <span style="margin-inline-start:auto;font-size:12px;opacity:.6" x-text="opt.native"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>