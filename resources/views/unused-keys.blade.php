@extends('ai-translator::layout')
@section('title', 'Unused keys')
@section('content')
<section class="glass--strong" style="padding:2rem;min-height:420px;display:grid;place-items:center;text-align:center">
  <div style="max-width:560px">
    <div style="width:64px;height:64px;margin:0 auto 1rem;display:grid;place-items:center;border-radius:20px;background:color-mix(in oklab,var(--sun) 14%,transparent);color:var(--sun);font-size:1.5rem">!</div>
    <p class="page-intro-title">Unused keys</p>
    <p class="page-intro-sub" style="margin-top:.5rem">Find translation keys that exist in your language files but are never referenced in your Laravel codebase.</p>
    <span class="badge" style="margin-top:1rem;background:color-mix(in oklab,var(--sun) 15%,transparent);color:var(--sun);font-size:.75rem">Coming soon</span>
    <p style="margin-top:1rem;font-size:.75rem;line-height:1.7;color:color-mix(in oklab,var(--ink) 55%,transparent)">This screen is intentionally frontend-only for now. The existing unused-key cleanup API has not been changed or connected.</p>
  </div>
</section>
@endsection
