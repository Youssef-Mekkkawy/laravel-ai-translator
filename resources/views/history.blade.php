@extends('ai-translator::layout')
@section('title', '{{ $_trans["history"] ?? "History" }}')
@section('content')
@php $tr=$_trans??[]; @endphp
<div class="history-list">

  {{-- Page intro with total cost --}}
  <section class="page-intro glass--strong" style="margin-bottom:.25rem">
    <div class="page-hero-row">
      <div>
        <p class="page-intro-title">{{ $tr['history'] ?? 'History' }}</p>
        <p class="page-intro-sub">{{ $tr['history_sub'] ?? 'Every sync run, with cost and diff.' }}</p>
      </div>
    </div>
    @if(count($runs) > 0)
    <div style="margin-top:.75rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:center">
      <span style="font-size:.6875rem;color:color-mix(in oklab,var(--ink) 50%,transparent)">
        {{ count($runs) }} {{ $tr['runs'] ?? 'run(s)' }}
      </span>
      <span style="display:inline-flex;align-items:center;gap:.375rem;font-size:.6875rem;font-weight:600;padding:.25rem .625rem;border-radius:999px;background:color-mix(in oklab,var(--sky) 15%,transparent);color:var(--sky-strong)" dir="ltr">
        <svg style="width:.75rem;height:.75rem" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        {{ $tr['total_cost'] ?? 'Total cost' }}: ${{ number_format($totalCost, 4) }}
      </span>
    </div>
    @endif
  </section>

  @if(count($runs)===0)
    <section class="glass empty-state">
      <div class="empty-state-icon">◷</div>
      <p class="empty-state-title">{{ $tr['no_history'] ?? 'No history yet' }}</p>
      <p class="empty-state-text">{{ $tr['run_translate'] ?? 'Run' }} <code>lang:translate</code> {{ $tr['to_start'] ?? 'to create your first history entry.' }}</p>
    </section>
  @else
    @foreach($runs as $i=>$run)
      @php
        $status=$run['status']??'success';
        $langs=is_array($run['languages']??null)?implode(' · ',$run['languages']):($run['languages']??'');
        $keys=$run['keys_translated']??0; $dMs=$run['duration_ms']??0;
        $durStr=$dMs>=60000?floor($dMs/60000).'m '.floor(($dMs%60000)/1000).'s':($dMs>=1000?round($dMs/1000,1).'s':$dMs.'ms');
        $provider=ucfirst($run['provider']??'ollama'); $model=$run['model']??''; $cost=$run['cost']??0; $changes=$run['changes']??[]; $errors=$run['errors']??[];
        $startedAt=$run['started_at']??''; $startFmt=$startedAt?\Carbon\Carbon::parse($startedAt)->format('Y-m-d H:i'):'—';
        $statusClass=$status==='success'?'badge-success':($status==='partial'?'badge-partial':'badge-failed');
      @endphp
      <article class="history-item glass" id="history-{{ $i }}">
        <button type="button" class="history-summary" onclick="toggleRun({{ $i }})">
          <span class="history-chevron">›</span>
          <span class="badge {{ $statusClass }}">{{ $status }}</span>
          <span class="summary-date" dir="ltr">{{ $startFmt }}</span>
          <span class="summary-value">{{ $keys ? number_format($keys).' '.($tr['keys']??'keys') : '—' }}</span>
          <span class="summary-value">{{ $langs ?: '—' }}</span>
          <span class="summary-provider">{{ $provider }}@if($model) · {{ $model }}@endif</span>
          <span class="summary-cost" dir="ltr">${{ number_format($cost,4) }}</span>
        </button>
        <div class="history-details" hidden>
          @if(!empty($changes))
            <div class="history-details-title">{{ $tr['changed_keys'] ?? 'Changed keys' }}</div>
            <div class="changed-list">
              @foreach($changes as $change)
                <div class="changed-item">
                  <span class="changed-lang">{{ $change['lang']??'??' }}</span>
                  <span class="changed-key">{{ $change['key']??'' }}</span>
                  @if(isset($change['old']))<span class="changed-old">{{ $change['old'] }}</span><span class="changed-arrow">→</span>@endif
                  <span class="changed-new">{{ $change['value']??'' }}</span>
                </div>
              @endforeach
            </div>
          @elseif(!empty($run['files_written']))
            <div class="history-details-title">{{ $tr['files_written'] ?? 'Files written' }}</div>
            <div class="changed-list">
              @foreach($run['files_written'] as $file)
                <div class="changed-item"><span class="changed-new">✓</span><span class="changed-key">{{ $file }}</span></div>
              @endforeach
            </div>
          @else
            <div style="font-size:.75rem;color:color-mix(in oklab,var(--ink) 50%,transparent)">{{ $tr['all_up_to_date'] ?? 'All keys were already up to date.' }}</div>
          @endif
          @if(!empty($errors))
            <div style="margin-top:1rem;display:flex;flex-direction:column;gap:.5rem">
              @foreach($errors as $lang=>$error)<div style="padding:.6rem .75rem;border-radius:.75rem;background:color-mix(in oklab,var(--coral) 8%,transparent);font-family:ui-monospace,monospace;font-size:.6875rem"><span style="color:var(--coral)">{{ $lang }}:</span> {{ $error }}</div>@endforeach
            </div>
          @endif
          <div class="run-footer">
            @if($startedAt)<span>{{ \Carbon\Carbon::parse($startedAt)->diffForHumans() }}</span>@endif
            @if($dMs>0)<span>{{ $tr['duration']??'Duration' }}: {{ $durStr }}</span>@endif
            @if(($run['total_keys']??0)>0)<span>{{ $run['total_keys'] }} {{ $tr['total_keys']??'total keys' }}</span>@endif
            @if(($run['skipped']??0)>0)<span>{{ $run['skipped'] }} {{ $tr['skipped']??'skipped' }}</span>@endif
            @if(($run['locked']??0)>0)<span>{{ $run['locked'] }} {{ $tr['locked_keys']??'locked' }}</span>@endif
          </div>
        </div>
      </article>
    @endforeach
  @endif
</div>
<script>
function toggleRun(i){const root=document.getElementById('history-'+i);if(!root)return;const d=root.querySelector('.history-details');const open=!root.classList.contains('is-open');root.classList.toggle('is-open',open);d.hidden=!open;}
</script>
@endsection
