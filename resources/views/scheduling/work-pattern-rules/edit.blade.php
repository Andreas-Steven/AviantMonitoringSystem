@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Edit Work Pattern Rule</h1>
    <p class="mt-1 text-sm text-slate-500">Perbarui rule work pattern</p>
</div>

<form method="POST" action="{{ route('scheduling.work-pattern-rules.update', $rule->work_pattern_rule_id) }}" class="space-y-6">
    @csrf
    @method('PUT')
    @include('scheduling.work-pattern-rules.partials.form')
</form>
@endsection