@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Edit Shift</h1>
    <p class="mt-1 text-sm text-slate-500">Perbarui data shift kerja</p>
</div>

<form method="POST" action="{{ route('scheduling.shifts.update', $shift->shift_id) }}" class="space-y-6">
    @csrf
    @method('PUT')
    @include('scheduling.shifts.partials.form')
</form>
@endsection