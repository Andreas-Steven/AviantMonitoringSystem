@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Add Work Pattern</h1>
    <p class="mt-1 text-sm text-slate-500">Tambah pola evaluasi periodik</p>
</div>

<form method="POST" action="{{ route('scheduling.work-patterns.store') }}" class="space-y-6">
    @csrf
    @include('scheduling.work-patterns.partials.form')
</form>
@endsection