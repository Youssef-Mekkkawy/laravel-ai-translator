@extends('ai-translator::layout')
@section('title', '{{ $_trans["settings"] ?? "Settings" }}')

@section('content')
@php
    $d        = config('ai-translator.driver', 'ollama');
    $provs    = config('ai-translator.providers', []);
    $curModel = $provs[$d]['model'] ?? $provs[$d]['plan'] ?? '';
    $tr       = $_trans ?? [];
@endphp

<div x-data="settingsPage()" style="display:flex;flex-direction:column;gap:16px">

  {{-- AI Provider --}}
  <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:20px">
    <div style="font-size:13px;font-weight:600;margin-bottom:6px">{{ $tr['active_provider'] ?? 'AI provider' }}</div>
    <div style="font-size:12px;color:#5C6678;margin-bottom:16px">{{ $tr['settings_sub'] ?? 'Provider, models and behaviour' }}</div>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
      @foreach(['ollama'=>['Ollama','local · free'],'deepl'=>['DeepL','classic MT'],'claude'=>['Claude','anthropic'],'openai'=>['ChatGPT','openai'],'gemini'=>['Gemini','google']] as $id=>[$name,$sub])
      <button @click="switchProvider('{{ $id }}')"
        :style="provider==='{{ $id }}' ? 'padding:13px 14px;border-radius:11px;cursor:pointer;border:1px solid #6EE7B7;background:rgba(110,231,183,.07);text-align:start;min-width:110px' : 'padding:13px 14px;border-radius:11px;cursor:pointer;border:1px solid #1B2130;background:#0D111A;text-align:start;min-width:110px'">
        <div style="font-weight:600;font-size:13px;color:#E6E9EF">{{ $name }}</div>
        <div style="font-size:11px;color:#5C6678;margin-top:2px">{{ $sub }}</div>
      </button>
      @endforeach
    </div>

    {{-- Ollama --}}
    <div x-show="provider === 'ollama'" style="display:flex;flex-direction:column;gap:14px">
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <span :style="ollamaUp ? 'display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:99px;font-size:11px;font-weight:600;color:#6EE7B7;background:rgba(110,231,183,.1);border:1px solid rgba(110,231,183,.25)' : 'display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:99px;font-size:11px;font-weight:600;color:#F87171;background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25)'">
          <span :style="'width:6px;height:6px;border-radius:50%;background:' + (ollamaUp ? '#6EE7B7' : '#F87171')"></span>
          <span x-text="ollamaTesting ? '{{ addslashes($tr['saving'] ?? 'Testing...') }}' : (ollamaUp ? '{{ addslashes($tr['connected'] ?? 'Connected') }}' : '{{ addslashes($tr['not_running'] ?? 'Not running') }}')"></span>
        </span>
        <button @click="testOllama()" style="display:inline-flex;align-items:center;gap:8px;padding:7px 13px;border-radius:9px;border:1px solid #2B3446;background:#161C27;color:#8B93A5;font-size:12px;cursor:pointer">
          {{ $tr['test_connection'] ?? 'Test connection' }}
        </button>
      </div>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px">
        <div>
          <label style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px">API URL</label>
          <input x-model="ollamaUrl" style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-family:'JetBrains Mono',monospace;font-size:12px;outline:none;direction:ltr">
        </div>
        <div>
          <label style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px">
            {{ $tr['model'] ?? 'Model' }}
            <span x-show="ollamaModelsLoading" style="margin-inline-start:6px;color:#5C6678">({{ $tr['saving'] ?? 'loading...' }})</span>
          </label>
          <select x-model="model" style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-size:13px;outline:none">
            <option x-show="ollamaModels.length === 0" value="" x-text="ollamaModelsLoading ? '{{ addslashes($tr['saving'] ?? 'Loading...') }}' : 'No models found'"></option>
            <template x-for="m in ollamaModels" :key="m"><option :value="m" x-text="m"></option></template>
          </select>
        </div>
      </div>
    </div>

    {{-- Cloud provider --}}
    <div x-show="provider !== 'ollama'" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
      <div>
        <label style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px">{{ $tr['api_key'] ?? 'API key' }}</label>
        <input type="password" x-model="apiKey" placeholder="••••••••••••••••••••"
          style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-size:13px;outline:none">
        <div style="font-size:11px;color:#5C6678;margin-top:6px">{{ $tr['api_key_hint'] ?? 'Stored in your .env — never in the database.' }}</div>
      </div>
      <div>
        <label style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px">{{ $tr['model'] ?? 'Model' }}</label>
        <select x-model="model" style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-size:13px;outline:none">
          <template x-for="m in availableModels" :key="m"><option :value="m" x-text="m"></option></template>
        </select>
      </div>
    </div>
  </div>

  {{-- Translation behaviour --}}
  <div style="border:1px solid #1B2130;border-radius:14px;background:#101420;padding:20px">
    <div style="font-size:13px;font-weight:600;margin-bottom:16px">{{ $tr['translate'] ?? 'Translation behaviour' }}</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
      <div>
        <label style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px">{{ $tr['source_language'] ?? 'Source language' }}</label>
        <select x-model="sourceLang" style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-size:13px;outline:none">
          <option value="en">English (en)</option>
          <option value="ar">Arabic (ar)</option>
          <option value="fr">French (fr)</option>
          <option value="es">Spanish (es)</option>
          <option value="de">German (de)</option>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px" x-text="'Chunk · ' + chunk + ' {{ addslashes($tr['keys'] ?? 'keys') }}'"></label>
        <input type="range" x-model="chunk" min="5" max="100" step="5" style="width:100%;accent-color:#6EE7B7">
        <div style="display:flex;justify-content:space-between;font-size:11px;color:#5C6678;margin-top:4px"><span>5</span><span>100</span></div>
      </div>
    </div>
    <div style="margin-top:14px">
      <label style="display:block;font-size:12px;color:#8B93A5;margin-bottom:7px">{{ $tr['context'] ?? 'Context prompt' }} <span style="color:#5C6678">({{ $tr['reason_optional'] ?? 'optional' }})</span></label>
      <textarea x-model="context" rows="3"
        placeholder="{{ $tr['context_hint'] ?? 'e.g. This is an e-commerce app. Keep translations formal.' }}"
        style="width:100%;padding:10px 12px;border-radius:9px;border:1px solid #1B2130;background:#0D111A;color:#E6E9EF;font-size:13px;outline:none;resize:vertical;font-family:inherit"></textarea>
    </div>
  </div>

  {{-- Save --}}
  <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <button @click="save()" :disabled="saving"
      style="padding:10px 20px;border-radius:10px;border:1px solid #6EE7B7;background:#6EE7B7;color:#062A20;font-size:13px;font-weight:600;cursor:pointer">
      <span x-show="saving" style="display:inline-block;width:12px;height:12px;border-radius:50%;border:2px solid rgba(6,42,32,.3);border-top-color:#062A20;animation:spin .8s linear infinite;margin-inline-end:6px"></span>
      <span x-text="saving ? '{{ addslashes($tr['saving'] ?? 'Saving...') }}' : '{{ addslashes($tr['save'] ?? 'Save changes') }}'"></span>
    </button>
    <button style="padding:10px 18px;border-radius:10px;border:1px solid #2B3446;background:transparent;color:#8B93A5;font-size:13px;cursor:pointer">{{ $tr['reset'] ?? 'Reset to defaults' }}</button>
    <span x-show="resultMsg" x-text="resultMsg" :style="resultOk ? 'font-size:13px;color:#6EE7B7' : 'font-size:13px;color:#F87171'"></span>
  </div>

</div>

<script>
function settingsPage() {
  return {
    provider:  '{{ $d }}',
    apiKey:    '',
    model:     '{{ env("OLLAMA_MODEL", $curModel) }}',
    sourceLang:'{{ config("ai-translator.default_language", "en") }}',
    chunk:     {{ config("ai-translator.options.chunk_size", 20) }},
    context:   '{{ addslashes(config("ai-translator.options.context", "")) }}',
    ollamaUrl: '{{ config("ai-translator.providers.ollama.api_url", "http://localhost:11434") }}',
    ollamaUp:  false, ollamaTesting: false,
    saving: false, resultMsg: '', resultOk: true,
    ollamaModels: [], ollamaModelsLoading: false,

    get availableModels() {
      const m = { claude:['claude-sonnet-4-5','claude-3-5-sonnet-20241022','claude-3-haiku-20240307'], openai:['gpt-4o','gpt-4o-mini','gpt-4-turbo'], gemini:['gemini-1.5-pro','gemini-1.5-flash'], deepl:['default'] };
      return m[this.provider] || [];
    },

    async init() { if (this.provider === 'ollama') await this.fetchOllamaModels(); },

    switchProvider(p) {
      this.provider = p;
      if (p === 'ollama') this.fetchOllamaModels();
      else if (this.availableModels.length) this.model = this.availableModels[0];
    },

    async testOllama() {
      this.ollamaTesting = true;
      try {
        const r = await fetch(this.ollamaUrl + '/api/tags', { signal: AbortSignal.timeout(3000) });
        this.ollamaUp = r.ok;
        if (r.ok) await this.fetchOllamaModels();
      } catch { this.ollamaUp = false; }
      this.ollamaTesting = false;
    },

    async fetchOllamaModels() {
      this.ollamaModelsLoading = true;
      try {
        const r = await fetch('{{ url("ai-translator/api/ollama-models") }}').then(r => r.json());
        if (r.success && r.data.models.length) {
          this.ollamaModels = r.data.models;
          if (!this.ollamaModels.includes(this.model)) this.model = this.ollamaModels[0];
          this.ollamaUp = true;
        }
      } catch {}
      this.ollamaModelsLoading = false;
    },

    async save() {
      this.saving = true; this.resultMsg = '';
      try {
        const payload = { driver: this.provider, source_lang: this.sourceLang, chunk_size: this.chunk, context: this.context, ollama_url: this.ollamaUrl, ollama_model: this.provider === 'ollama' ? this.model : null };
        if (this.provider === 'deepl')  payload.deepl_key  = this.apiKey;
        if (this.provider === 'claude') payload.claude_key = this.apiKey;
        if (this.provider === 'openai') payload.openai_key = this.apiKey;
        if (this.provider === 'gemini') payload.gemini_key = this.apiKey;
        const r = await fetch('{{ url("ai-translator/api/settings") }}', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
          body: JSON.stringify(payload),
        }).then(r => r.json());
        this.resultOk = r.success;
        this.resultMsg = r.message || (r.success ? '{{ addslashes($tr['save'] ?? 'Saved.') }}' : 'Error saving.');
        setTimeout(() => { this.resultMsg = ''; }, 4000);
      } catch (e) { this.resultOk = false; this.resultMsg = 'Error: ' + e.message; }
      this.saving = false;
    },
  };
}
</script>
@endsection
