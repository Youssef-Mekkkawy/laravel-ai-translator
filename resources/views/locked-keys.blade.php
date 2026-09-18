@extends('ai-translator::layout')
@section('title', '{{ $_trans["locked"] ?? "Locked Keys" }}')
@section('content')
@php $tr = $_trans ?? []; @endphp
<div x-data="lockedPage()" class="page-stack">
  <section class="page-intro glass--strong">
    <div class="page-hero-row">
      <div>
        <p class="page-intro-title">{{ $tr['locked'] ?? 'Locked keys' }}</p>
        <p class="page-intro-sub">{{ $tr['locked_sub'] ?? 'Keys protected from being overwritten by the AI.' }}</p>
      </div>
      <button class="btn btn--primary" type="button" @click="$dispatch('open-lock-modal')">
        <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        {{ $tr['lock_key_title'] ?? 'Lock key' }}
      </button>
    </div>
    <div class="locked-toolbar">
      <div class="search-wrap">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>
        <input class="text-input" x-model="search" type="search" placeholder="{{ $tr['search_language'] ?? 'Search key or value…' }}">
      </div>
      <select class="select-input" x-model="filterLang" @change="filterByLang()">
        <option value="">{{ $tr['all_languages'] ?? 'All languages' }}</option>
        @foreach(array_filter(config('ai-translator.languages',[]), fn($l) => $l !== config('ai-translator.default_language','en')) as $lang)
          <option value="{{ $lang }}">{{ strtoupper($lang) }}</option>
        @endforeach
      </select>
    </div>
    <div x-show="msg" x-text="msg" class="page-message" :class="msgOk ? 'page-message--ok' : 'page-message--err'"></div>
  </section>

  <section class="locked-table-panel glass">
    @if(count($locks) === 0)
      <div class="empty-state">
        <div class="empty-state-icon">🔒</div>
        <p class="empty-state-title">{{ $tr['no_locked_keys'] ?? 'No locked keys' }}</p>
        <p class="empty-state-text">{{ $tr['lock_hint'] ?? 'Lock a translation to protect it from being overwritten by AI.' }}</p>
        <button class="btn btn--primary" @click="$dispatch('open-lock-modal')">{{ $tr['lock_first'] ?? 'Lock your first key' }}</button>
      </div>
    @else
      <div class="locked-table-scroll">
        <table class="locked-table">
          <thead><tr>
            <th style="width:2.5rem"><button class="table-check" :class="allSelected ? 'is-checked' : ''" type="button" aria-label="Select all" @click="toggleAll()"></button></th>
            <th>{{ $tr['language'] ?? 'Language' }}</th>
            <th>{{ $tr['key'] ?? 'Key' }}</th>
            <th>{{ $tr['value'] ?? 'Value' }}</th>
            <th>{{ $tr['locked_by'] ?? 'Locked by' }}</th>
            <th>{{ $tr['reason'] ?? 'Reason' }}</th>
            <th></th>
          </tr></thead>
          <tbody>
          @foreach($locks as $i => $lock)
            @php
              $searchText = strtolower(($lock['lang'] ?? '').' '.($lock['key'] ?? '').' '.($lock['value'] ?? ''));
              $rowId = 'lock-'.md5(($lock['lang'] ?? '').'|'.($lock['key'] ?? '').'|'.$i);
            @endphp
            <tr id="{{ $rowId }}" data-lock-row data-lock-key="{{ addslashes($lock['lang'].'|'.$lock['key']) }}" data-search="{{ $searchText }}" style="display:table-row" x-show="matches('{{ addslashes($searchText) }}')">
              <td><button type="button" class="table-check" :class="selected.includes('{{ addslashes($lock['lang'].'|'.$lock['key']) }}') ? 'is-checked' : ''" aria-label="Select key" @click="toggle('{{ addslashes($lock['lang'].'|'.$lock['key']) }}')"></button></td>
              <td><span class="locked-badge badge">{{ strtoupper($lock['lang'] ?? '') }}</span></td>
              <td><span class="key-cell" dir="ltr">{{ $lock['key'] }}</span></td>
              <td><div class="value-cell">{{ $lock['value'] ?? '—' }}</div></td>
              <td><div class="locked-by"><span>⌁</span><span dir="ltr">{{ $lock['locked_by'] ?? 'system' }}</span></div>@if($lock['locked_at'])<div class="locked-by-date">{{ \Carbon\Carbon::parse($lock['locked_at'])->format('Y-m-d H:i') }}</div>@endif</td>
              <td class="reason-cell">{{ $lock['reason'] ?? '—' }}</td>
              <td class="locked-action-cell"><button class="table-btn" type="button" @click="unlock('{{ $lock['lang'] }}','{{ addslashes($lock['key']) }}')" :disabled="unlocking === '{{ $lock['lang'].'.'.$lock['key'] }}'"><span x-text="unlocking === '{{ $lock['lang'].'.'.$lock['key'] }}' ? '…' : '{{ addslashes($tr['unlock'] ?? 'Unlock') }}'"></span></button></td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>
      <div style="padding:.75rem 1rem;font-size:.6875rem;color:color-mix(in oklab,var(--ink) 50%,transparent)">{{ count($locks) }} {{ $tr['locked_keys'] ?? 'locked key(s)' }}</div>
    @endif
  </section>
</div>
<script>
function lockedPage(){
  return {
    search:'', filterLang:'{{ request('lang','') }}', unlocking:'', msg:'', msgOk:true, selected:[],
    get visibleKeys(){ return [...document.querySelectorAll('[data-lock-row]')].filter(r=>getComputedStyle(r).display!=='none').map(r=>r.dataset.lockKey); },
    get allSelected(){ const keys=this.visibleKeys; return keys.length>0 && keys.every(k=>this.selected.includes(k)); },
    matches(text){ return !this.search || text.toLowerCase().includes(this.search.toLowerCase()); },
    toggle(key){ this.selected=this.selected.includes(key)?this.selected.filter(x=>x!==key):[...this.selected,key]; },
    toggleAll(){ const keys=this.visibleKeys; this.selected=this.allSelected?this.selected.filter(k=>!keys.includes(k)):[...new Set([...this.selected,...keys])]; },
    filterByLang(){ const base='{{ route("ai-translator.locked") }}'; window.location.href=base+(this.filterLang?'?lang='+encodeURIComponent(this.filterLang):''); },
    async unlock(lang,key){
      this.unlocking=lang+'.'+key; this.msg='';
      try{
        const r=await fetch('{{ url("ai-translator/api/unlock") }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({lang,key})}).then(r=>r.json());
        this.msgOk=!!r.success; this.msg=r.message||(r.success?'Unlocked.':'Failed.'); if(r.success)setTimeout(()=>location.reload(),700);
      }catch(e){this.msgOk=false;this.msg='Error: '+e.message;} this.unlocking='';
    }
  }
}
</script>
@endsection
