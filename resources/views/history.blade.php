@extends('ai-translator::layout')
@section('title', '{{ $_trans["history"] ?? "History" }}')

@section('content')
@php $tr = $_trans ?? []; @endphp
<style>
.run-card{border:1px solid #1B2130;border-radius:14px;background:#101420;overflow:hidden;margin-bottom:10px}
.run-toggle{width:100%;display:flex;align-items:center;gap:14px;flex-wrap:wrap;padding:14px 20px;background:transparent;border:none;color:inherit;cursor:pointer;text-align:start}
.run-toggle:hover{background:rgba(255,255,255,.02)}
.caret{color:#5C6678;transition:.2s;flex-shrink:0}
.is-open .caret{transform:rotate(90deg)}
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:99px;font-size:11.5px;font-weight:600;flex-shrink:0}
.badge-success{background:rgba(110,231,183,.1);border:1px solid rgba(110,231,183,.25);color:#6EE7B7}
.badge-partial{background:rgba(251,191,36,.1);border:1px solid rgba(251,191,36,.25);color:#FBBF24}
.badge-failed{background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.25);color:#F87171}
.run-time{font-family:'JetBrains Mono',monospace;font-size:12.5px;color:#E6E9EF;min-width:140px}
.run-meta{font-size:12.5px;color:#8B93A5}
.run-dur{font-size:12.5px;color:#8B93A5}
.run-right{margin-inline-start:auto;display:flex;align-items:center;gap:14px}
.run-prov{font-size:12px;color:#8B93A5;white-space:nowrap}
.run-cost{font-family:'JetBrains Mono',monospace;font-size:12.5px;color:#6EE7B7}
.run-body{border-top:1px solid #161C27;display:none}
.is-open .run-body{display:block}
.change-row{display:flex;align-items:center;gap:12px;padding:10px 20px;border-bottom:1px solid #0E1219}
.change-row:last-child{border-bottom:none}
.code-chip{display:inline-flex;align-items:center;justify-content:center;border-radius:5px;background:#161C27;border:1px solid #2B3446;font-family:'JetBrains Mono',monospace;color:#8B93A5;font-weight:600;flex-shrink:0}
.code-chip.sm{width:28px;height:20px;font-size:9.5px}
.change-key{font-family:'JetBrains Mono',monospace;font-size:12px;color:#E6E9EF;flex-shrink:0;direction:ltr}
.change-val{font-size:13px;color:#8B93A5;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1}
.tag{display:inline-flex;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600;flex-shrink:0}
.tag-new{background:rgba(110,231,183,.12);color:#6EE7B7;border:1px solid rgba(110,231,183,.25)}
.tag-updated{background:rgba(56,189,248,.12);color:#38bdf8;border:1px solid rgba(56,189,248,.25)}
.run-footer{padding:10px 20px;border-top:1px solid #161C27;font-size:11.5px;color:#5C6678;display:flex;gap:16px;flex-wrap:wrap}
</style>

<div style="display:flex;flex-direction:column;gap:0">

  @if(count($runs) === 0)
  <div style="border:1px dashed #232B3B;border-radius:14px;background:#0E1219;padding:64px 24px;text-align:center">
    <div style="width:52px;height:52px;margin:0 auto 16px;border-radius:14px;background:#141A26;display:grid;place-items:center;color:#3A4761">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/><path d="M12 8v4.5l3 1.8"/></svg>
    </div>
    <div style="font-size:15px;font-weight:600;margin-bottom:6px">{{ $tr['no_history'] ?? 'No history yet' }}</div>
    <div style="font-size:13px;color:#5C6678">
      {{ $tr['run_translate'] ?? 'Run' }} <code style="font-family:'JetBrains Mono',monospace;color:#8B93A5;background:#161C27;padding:2px 6px;border-radius:4px">lang:translate</code> {{ $tr['to_start'] ?? 'to create your first history entry.' }}
    </div>
  </div>

  @else
  @foreach($runs as $i => $run)
  @php
    $status   = $run['status'] ?? 'success';
    $langs    = is_array($run['languages'] ?? null) ? implode(', ', $run['languages']) : ($run['languages'] ?? '');
    $keys     = $run['keys_translated'] ?? 0;
    $dMs      = $run['duration_ms'] ?? 0;
    $durStr   = $dMs >= 60000 ? floor($dMs/60000).'m '.floor(($dMs%60000)/1000).'s' : ($dMs >= 1000 ? round($dMs/1000,1).'s' : $dMs.'ms');
    $provider = ucfirst($run['provider'] ?? 'ollama');
    $model    = $run['model'] ?? '';
    $cost     = $run['cost'] ?? 0;
    $errors   = $run['errors'] ?? [];
    $changes  = $run['changes'] ?? [];
    $startedAt  = $run['started_at'] ?? '';
    $startFmt   = $startedAt ? \Carbon\Carbon::parse($startedAt)->format('Y-m-d H:i') : '—';
    $startAgo   = $startedAt ? \Carbon\Carbon::parse($startedAt)->diffForHumans() : '';
    $statusColor = match($status) {
      'success' => 'badge-success',
      'partial'  => 'badge-partial',
      default    => 'badge-failed',
    };
  @endphp

  <div class="run-card" id="run-{{ $i }}">
    <button class="run-toggle" onclick="toggleRun({{ $i }})">
      <span class="caret">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
      </span>
      <span class="badge {{ $statusColor }}">{{ $status }}</span>
      <span class="run-time">{{ $startFmt }}</span>
      @if($keys > 0)<span class="run-meta">{{ $keys }} {{ $tr['keys'] ?? 'keys' }}</span>@endif
      @if($langs)<span class="run-meta">{{ $langs }}</span>@endif
      @if($dMs > 0)<span class="run-dur">{{ $durStr }}</span>@endif
      <span class="run-right">
        <span class="run-prov">{{ $provider }}@if($model) {{ $model }}@endif</span>
        <span class="run-cost">${{ number_format($cost, 2) }}</span>
      </span>
    </button>

    <div class="run-body">
      @if(!empty($changes))
        @foreach($changes as $change)
        <div class="change-row">
          <span class="code-chip sm">{{ $change['lang'] ?? '??' }}</span>
          <span class="change-key">{{ $change['key'] ?? '' }}</span>
          <span class="change-val">{{ $change['value'] ?? '' }}</span>
          <span class="tag tag-{{ $change['tag'] ?? 'new' }}">{{ $change['tag'] ?? 'new' }}</span>
        </div>
        @endforeach
      @elseif(!empty($run['files_written']))
        @foreach($run['files_written'] as $file)
        <div class="change-row">
          <span style="color:#6EE7B7;font-size:10px;flex-shrink:0">✓</span>
          <span class="change-key" style="color:#8B93A5">{{ $file }}</span>
          <span class="tag tag-updated">{{ $tr['updated'] ?? 'updated' }}</span>
        </div>
        @endforeach
      @else
        <div style="padding:14px 20px;font-size:13px;color:#5C6678">
          {{ $tr['all_up_to_date'] ?? 'All keys were already up to date.' }}
        </div>
      @endif

      @if(!empty($errors))
      <div style="padding:14px 20px;border-top:1px solid #161C27">
        @foreach($errors as $lang => $error)
        <div style="font-size:12px;padding:8px 12px;background:#0D111A;border-radius:8px;border:1px solid rgba(248,113,113,.2);margin-bottom:6px;font-family:'JetBrains Mono',monospace">
          <span style="color:#F87171">{{ $lang }}:</span> <span style="color:#8B93A5">{{ $error }}</span>
        </div>
        @endforeach
      </div>
      @endif

      <div class="run-footer">
        @if($startAgo)<span>{{ $startAgo }}</span>@endif
        @if($dMs > 0)<span>{{ $tr['duration'] ?? 'Duration' }}: {{ $durStr }}</span>@endif
        @if(isset($run['total_keys']) && $run['total_keys'] > 0)<span>{{ $run['total_keys'] }} {{ $tr['total_keys'] ?? 'total keys' }}</span>@endif
        @if(isset($run['skipped']) && $run['skipped'] > 0)<span>{{ $run['skipped'] }} {{ $tr['skipped'] ?? 'skipped' }}</span>@endif
        @if(isset($run['locked']) && $run['locked'] > 0)<span>{{ $run['locked'] }} {{ $tr['locked_keys'] ?? 'locked' }}</span>@endif
      </div>
    </div>
  </div>
  @endforeach

  <div style="font-size:12px;color:#5C6678;margin-top:8px">{{ count($runs) }} {{ $tr['history'] ?? 'run(s)' }}</div>
  @endif
</div>

<script>
function toggleRun(i) {
  const card = document.getElementById('run-' + i);
  if (card) card.classList.toggle('is-open');
}
</script>
@endsection
