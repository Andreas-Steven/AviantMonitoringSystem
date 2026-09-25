@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Edit Work Pattern</h1>
    <p class="mt-1 text-sm text-slate-500">Perbarui pola evaluasi periodik</p>
</div>

<form method="POST" action="{{ route('scheduling.work-patterns.update', $workPattern->work_pattern_id) }}" class="space-y-6">
    @csrf
    @method('PUT')
    @include('scheduling.work-patterns.partials.form')
</form>
@endsection