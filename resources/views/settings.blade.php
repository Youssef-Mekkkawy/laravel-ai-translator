@extends('ai-translator::layout')
@section('title', '{{ $_trans["settings"] ?? "Settings" }}')
@section('content')
  @php
    $d = $driver;
    $tr = $_trans ?? [];
    $backupKeep = (int) config('ai-translator.backup.keep', 5);
    $backupAuto = (bool) config('ai-translator.backup.auto', true);
    $providerMeta = ['ollama' => ['Ollama', 'Local · free'], 'claude' => ['Claude', 'Anthropic'], 'openai' => ['ChatGPT', 'OpenAI'], 'gemini' => ['Gemini', 'Google'], 'deepl' => ['DeepL', 'Translation API']];
  @endphp
  <div x-data="settingsPage()" class="page-stack">
    <section class="page-intro glass--strong">
      <div class="page-hero-row">
        <div>
          <p class="page-intro-title">{{ $tr['settings'] ?? 'Settings' }}</p>
          <p class="page-intro-sub">{{ $tr['settings_sub'] ?? 'Provider credentials and translation behaviour.' }}</p>
        </div><button class="btn btn--primary" @click="save()" :disabled="saving"><svg class="icon" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h10l6 6v10a2 2 0 0 1-2 2Z" />
            <path d="M17 21v-8H7v8" />
            <path d="M7 3v5h8" />
          </svg><span
            x-text="saving?'{{ addslashes($tr['saving'] ?? 'Saving…') }}':'{{ addslashes($tr['save'] ?? 'Save changes') }}'"></span></button>
      </div>
      <div x-show="resultMsg" x-text="resultMsg" class="page-message"
        :class="resultOk?'page-message--ok':'page-message--err'"></div>
    </section>

    <section class="settings-layout">
      <div class="settings-section glass">
        <div class="settings-section-head">
          <h2 class="settings-section-title">{{ $tr['active_provider'] ?? 'AI provider' }}</h2>
        </div>
        <div class="provider-grid">
          @foreach($providerMeta as $id => $meta)
            <button type="button" class="provider-option" :class="provider==='{{ $id }}'?'is-selected':''"
              @click="switchProvider('{{ $id }}')">
              <p class="provider-name">{{ $meta[0] }}</p>
              <p class="provider-meta">{{ $meta[1] }}</p>
            </button>
          @endforeach
        </div>

        <div class="provider-status" x-show="provider==='ollama'"><span class="status-dot"></span><span class="badge"
            :style="ollamaUp?'background:color-mix(in oklab,var(--mint) 25%,transparent);color:var(--mint-strong)':'background:color-mix(in oklab,var(--coral) 18%,transparent);color:var(--coral-strong)'"
            x-text="ollamaTesting?'Checking…':(ollamaUp?'{{ addslashes($tr['connected'] ?? 'Connected') }}':'Not running')"></span><button
            class="table-btn" @click="testOllama()">{{ $tr['test_connection'] ?? 'Test connection' }}</button><button
            class="table-btn" x-show="!ollamaUp" @click="startOllama()" :disabled="ollamaStarting"
            x-text="ollamaStarting?'Starting…':'Start Ollama'"></button></div>
        <div x-show="ollamaPulling" class="page-message page-message--warn">Model is downloading in the background. The
          page will refresh when ready.</div>
        <p x-show="ollamaMsg" x-text="ollamaMsg" style="margin-top:.75rem;font-size:.75rem;color:var(--mint-strong)"></p>

        <div class="form-grid two" x-show="provider!=='ollama'">
          <div class="form-field"><label class="form-label">API key</label><input class="form-input" type="password"
              x-model="apiKey" placeholder="sk-•••••••••••••" dir="ltr"></div>
          <div class="form-field"><label class="form-label">Model</label><select class="form-select"
              x-model="model"><template x-for="m in availableModels" :key="m">
                <option :value="m" x-text="m"></option>
              </template></select></div>
        </div>
        <div class="form-grid two" x-show="provider==='ollama'">
          <div class="form-field"><label class="form-label">{{ $tr['api_url'] ?? 'API URL' }}</label><input
              class="form-input" x-model="ollamaUrl" dir="ltr"></div>
          <div class="form-field"><label class="form-label">{{ $tr['model'] ?? 'Model' }}</label><select class="form-select"
              x-model="model">
              <option value="" x-show="ollamaModels.length===0" x-text="ollamaModelsLoading?'Loading…':'No models found'">
              </option><template x-for="m in ollamaModels" :key="m">
                <option :value="m" x-text="m"></option>
              </template>
            </select></div>
        </div>
      </div>

      <div class="settings-section glass">
        <div class="settings-section-head">
          <h2 class="settings-section-title">{{ $tr['translate'] ?? 'Translation behaviour' }}</h2>
        </div>
        <div class="form-grid two">
          <div class="form-field"><label class="form-label">{{ $tr['source_language'] ?? 'Source language' }}</label><select
              class="form-select" x-model="sourceLang" dir="ltr">
              <option value="en">English (en)</option>
              <option value="ar">Arabic (ar)</option>
              <option value="fr">French (fr)</option>
              <option value="es">Spanish (es)</option>
              <option value="de">German (de)</option>
            </select></div>
          <div class="form-field"><label class="form-label">{{ $tr['chunk_size'] ?? 'Chunk size' }}</label><input
              class="form-input" type="number" x-model.number="chunk" min="1">
            <p class="form-help">{{ $tr['chunk_help'] ?? 'Keys sent per request.' }}</p>
          </div>
          <div class="form-field" style="grid-column:1/-1"><label
              class="form-label">{{ $tr['context'] ?? 'Context' }}</label><textarea class="form-textarea" x-model="context"
              rows="5"></textarea>
            <p class="form-help">{{ $tr['context_hint_short'] ?? 'Extra instructions passed with every request.' }}</p>
          </div>
        </div>
      </div>
    </section>

  </div>
  <script>
    function settingsPage() {
      return {
        provider: @json($driver), apiKey: '', model: @json($ollamaModel), sourceLang: @json($sourceLang), chunk: @json($chunkSize), context: @json($context), ollamaUrl: @json($ollamaUrl),
        backupKeep:{{ $backupKeep }}, backupAuto:{{ $backupAuto ? 'true' : 'false' }}, ollamaUp: false, ollamaTesting: false, ollamaStarting: false, ollamaPulling: false, ollamaMsg: '', saving: false, resultMsg: '', resultOk: true, ollamaModels: [], ollamaModelsLoading: false,
        get availableModels() { const m = { claude: ['claude-sonnet-4-5', 'claude-3-5-sonnet-20241022', 'claude-3-haiku-20240307'], openai: ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo'], gemini: ['gemini-1.5-pro', 'gemini-1.5-flash'], deepl: ['default'] }; return m[this.provider] || [] },
        async init() { if (this.provider === 'ollama') await this.testOllama(); },
        switchProvider(p) { this.provider = p; if (p === 'ollama') { this.fetchOllamaModels(); this.testOllama() } else if (this.availableModels.length) this.model = this.availableModels[0] },
        async startOllama() { this.ollamaStarting = true; this.ollamaMsg = ''; try { await fetch('{{ url("ai-translator/api/ollama/start") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({}) }).then(r => r.json()); this.ollamaMsg = 'Starting Ollama…'; let attempts = 0; const poll = setInterval(async () => { attempts++; const s = await fetch('{{ url("ai-translator/api/ollama/status") }}').then(r => r.json()); if (s.success && s.data?.running) { clearInterval(poll); this.ollamaUp = true; this.ollamaStarting = false; if (!s.data?.hasModel) { this.ollamaPulling = true; this.ollamaMsg = 'Ollama started. Model is downloading in background.' } else { this.ollamaMsg = 'Ollama started successfully!'; await this.fetchOllamaModels(); setTimeout(() => this.ollamaMsg = '', 4000) } } else if (attempts >= 15) { clearInterval(poll); this.ollamaStarting = false; this.ollamaMsg = 'Could not start Ollama.' } }, 2000) } catch (e) { this.ollamaStarting = false; this.ollamaMsg = 'Error: ' + e.message } },
        async testOllama() { this.ollamaTesting = true; try { const r = await fetch('{{ url("ai-translator/api/ollama/status") }}').then(r => r.json()); this.ollamaUp = r.success && r.data?.running; if (this.ollamaUp && r.data?.models?.length) { this.ollamaModels = r.data.models; if (!this.ollamaModels.includes(this.model)) this.model = this.ollamaModels[0] } } catch { this.ollamaUp = false } this.ollamaTesting = false },
        async fetchOllamaModels() { this.ollamaModelsLoading = true; try { const r = await fetch('{{ url("ai-translator/api/ollama/status") }}').then(r => r.json()); if (r.success && r.data?.models?.length) { this.ollamaModels = r.data.models; if (!this.ollamaModels.includes(this.model)) this.model = this.ollamaModels[0]; this.ollamaUp = true } } catch { } this.ollamaModelsLoading = false },
        async save() { this.saving = true; this.resultMsg = ''; try { const payload = { driver: this.provider, source_lang: this.sourceLang, chunk_size: this.chunk, context: this.context, ollama_url: this.ollamaUrl, ollama_model: this.provider === 'ollama' ? this.model : null, backup_keep: parseInt(this.backupKeep), backup_auto: this.backupAuto }; if (this.provider === 'deepl') payload.deepl_key = this.apiKey; if (this.provider === 'claude') payload.claude_key = this.apiKey; if (this.provider === 'openai') payload.openai_key = this.apiKey; if (this.provider === 'gemini') payload.gemini_key = this.apiKey; const r = await fetch('{{ url("ai-translator/api/settings") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(payload) }).then(r => r.json()); this.resultOk = !!r.success; this.resultMsg = r.message || (r.success ? 'Saved.' : 'Error saving.'); setTimeout(() => this.resultMsg = '', 4000) } catch (e) { this.resultOk = false; this.resultMsg = 'Error: ' + e.message } this.saving = false }
      }
    }
  </script>
@endsection