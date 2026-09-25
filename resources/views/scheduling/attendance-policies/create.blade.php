@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Add Attendance Policy</h1>
    <p class="mt-1 text-sm text-slate-500">Tambah aturan absensi harian</p>
</div>

<form method="POST" action="{{ route('scheduling.attendance-policies.store') }}" class="space-y-6">
    @csrf
    @include('scheduling.attendance-policies.partials.form')
</form>
@endsection