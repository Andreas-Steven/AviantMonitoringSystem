@extends('layouts.app')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold tracking-tight">Edit Attendance Policy</h1>
    <p class="mt-1 text-sm text-slate-500">Perbarui aturan absensi harian</p>
</div>

<form method="POST" action="{{ route('scheduling.attendance-policies.update', $policy->policy_id) }}" class="space-y-6">
    @csrf
    @method('PUT')
    @include('scheduling.attendance-policies.partials.form')
</form>
@endsection